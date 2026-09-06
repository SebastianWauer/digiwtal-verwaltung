# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Was das Projekt ist

DigiWtal Verwaltung — die zentrale Betreiberanwendung für Kunden, Zugangsdaten
(Vault), Rollouts, Monitoring und Support. Sie enthält **weder den CMS-Quellcode
noch ein Kundenfrontend**; beides liegt in eigenen Repositories (`CMS/`,
`Frontend/` sind bewusst in `.gitignore`).

Plain PHP 8.x, **kein Composer, kein Autoloader, kein Framework, keine
Testsuite, kein Build-Schritt.** Alle Abhängigkeiten sind handgeschrieben
(Router, .env-Loader, Logger, TOTP, Krypto).

Sprache: Code-Kommentare, UI, Doku und Commit-Messages sind Deutsch.

## Befehle

```bash
php scripts/migrate.php --dry-run      # offene Migrationen anzeigen
php scripts/migrate.php --baseline     # vorhandene Tabellen als angewendet markieren
php scripts/migrate.php                # offene Migrationen anwenden
php -S localhost:8003 -t public        # lokaler Dev-Server (Front-Controller: public/)
php scripts/health_check.php           # ein Prüflauf über alle Instanzen (Cron-Einstieg)
php scripts/check_admin_login.php <email> <passwort>   # Login-Diagnose
php agent/generate_cert.php            # einmalig: lokales HTTPS-Zertifikat für den Agenten
php agent/server.php                   # lokaler SFTP-Deploy-Agent auf https://127.0.0.1:8765
```

`.env.example` nach `.env` kopieren; ohne `VAULT_KEY_BASE64` (32 zufällige Bytes,
base64) schlägt jeder Vault-Zugriff fehl.

## Architektur

### Verdrahtung

[public/index.php](public/index.php) ist Front-Controller **und** DI-Container in
einem: es lädt `.env`, setzt Security-Header und CSP, baut die PDO-Verbindung,
`require_once`-t **jede** Klasse einzeln, instanziiert alle Repositories,
Services und Controller und registriert danach alle Routen.

Eine neue Klasse ist erst benutzbar, wenn sie dort ein `require_once` bekommt —
es gibt keinen Autoloader. CLI-Skripte laden stattdessen
[app/bootstrap.php](app/bootstrap.php) (`.env` + Fehlerbehandlung, keine Session)
und ihre Abhängigkeiten selbst.

[app/Router.php](app/Router.php) ersetzt jedes `{param}` durch `(\d+)` —
Routenparameter können **nur numerische IDs** sein.

### Schichten

- `controllers/` — beginnen mit `AdminAuth::requireAuth()`, prüfen bei POST
  `Csrf::verify($_POST['csrf_token'])`, schreiben Ergebnisse in
  `$_SESSION['flash_success'] / ['flash_errors'] / ['flash_old']`, laden am Ende
  `require __DIR__ . '/../views/...'`. Rollenprüfung inline über
  `$_SESSION['admin_role'] !== 'superadmin'` (`requireSuperadmin()` je Controller).
- `repositories/` — dünne PDO-Klassen, bekommen `PDO` im Konstruktor.
- `services/` — die Fachlogik: Deploy, Provisionierung, Health, Migrationen, Push.
- `views/` — PHP-Templates; setzen Variablen und schließen mit
  `require __DIR__ . '/../layout.php'` ab. [views/layout.php](views/layout.php)
  rendert Shell + Sidebar und cache-bustet Assets über `verwaltung_asset()`.

### Vault

[app/VaultCrypto.php](app/VaultCrypto.php): AES-256-GCM, Schlüssel aus
`VAULT_KEY_BASE64`, **AAD immer `'cust:' . $customerId`**. Jedes Geheimnis liegt
als Spaltentripel `<prefix>_enc` / `<prefix>_nonce` / `<prefix>_tag` — meist auf
`server_access`. Ein unvollständiges Tripel ist ein Fehler, kein Anlass zum
stillen Neuerzeugen (das würde eine laufende Instanz aussperren).

Betriebs-Tokens (`health`, `deploy`, `migration`, `setup`, `api` in
[CustomerRuntimeTokenRepository](repositories/CustomerRuntimeTokenRepository.php),
dazu `support` in [SupportTokenRepository](repositories/SupportTokenRepository.php))
werden **nie von Hand eingetragen**, sondern beim ersten Bedarf unter
`SELECT ... FOR UPDATE` erzeugt. Das ist Absicht: die gescheiterten ersten
Rollouts gingen alle auf leer gelassene Felder zurück.

### Rollout: warum über GitHub Actions

Der IONOS-Webspace der Verwaltung hat keine `ssh2`-Erweiterung, keine Shell und
kein offenes FTP — sie kann selbst nicht übertragen. Stattdessen startet sie
Workflows und stellt der Pipeline die Zugangsdaten bereit:

- `GET /api/ci/deploy-target?customer=<id>` in
  [CiController](controllers/CiController.php) gibt SSH-, DB- und Token-Klartext
  heraus. Deshalb: nur mit widerrufbarem CI-Token (Header `X-Ci-Token`), nur ein
  Kunde pro Aufruf, jeder Abruf im Audit-Log. Vorher prüft
  `ServerAccessRepository::rolloutReadiness()`, sonst `422`.
- **CMS-Rollout** (gemeinsames Repo): `/admin/ci-tokens` → `/admin/ci/dispatch`,
  gesteuert über `GITHUB_REPO`, `GITHUB_WORKFLOW`, `GITHUB_BRANCH`.
- **Frontend-Rollout** (pro Kunde ein eigenes Repo): Kunden → Deployments →
  *Frontend ausrollen*, gesteuert über `customers.frontend_repository` /
  `frontend_ref` und `GITHUB_FRONTEND_WORKFLOW`. Der Workflow bekommt nur
  `kunde=<id>` als Input und holt sich den Rest über `/api/ci/deploy-target`.
- Beides läuft über [GitHubWorkflowDispatcher](services/GitHubWorkflowDispatcher.php)
  (`workflow_dispatch`, Erfolg = HTTP 204). `GITHUB_TOKEN` braucht
  `Actions: Read and write` auf dem CMS- **und** jedem Frontend-Repository.

[services/DeployService.php](services/DeployService.php) ist der **andere** Weg:
direkte Übertragung per `ssh2`, Shell-SFTP oder cURL-FTP, für lokale
Provisionierung und den Agenten in `agent/`. Welche Dateien ein Kunde bekommt,
entscheidet [ModuleCombinator](services/ModuleCombinator.php) — Plugin-Ordner
unter `CMS/plugins/` müssen namensgleich mit `modules.key_name` sein.

### Monitoring

Gemessen wird an zwei Orten (Cron auf dem Webspace und `.github/workflows/health.yml`),
**bewertet und alarmiert nur an einer**: `HealthMonitor::evaluate()`. Auch die
Pipeline schickt nur ihre Rohmessung an `POST /api/ci/health-report`. Jeder Lauf
trägt ein Lebenszeichen in `monitor_runs` ein (Quelle `cron`, `ci` oder `rollout`);
bleibt es über 20 Minuten aus, meldet das Dashboard die Überwachung selbst als
ausgefallen. Health-URL ist immer `https://<cms-domain>/api.php/api/health` —
ohne `api.php` antwortet der Admin-Front-Controller mit 404. Details:
[docs/cron-setup.md](docs/cron-setup.md).

### Migrationen

`schema_migrations` führt den Stand **über den Dateinamen**, nicht über die
Nummer — `008` und `009` sind je doppelt vergeben. CLI und `/admin/migrations`
(nur Superadmin) benutzen dieselbe Klasse
[MigrationRunner](services/MigrationRunner.php); auf dem Webspace ist der Browser
der einzige Weg. Auf gewachsenen Installationen zuerst *Bestand markieren*, und
zwar nur die Dateien, deren Tabellen wirklich existieren — pauschales Markieren
würde echte Migrationen dauerhaft überspringen. Details:
[docs/migrationen.md](docs/migrationen.md).

### Support

Kunden-CMS → `POST /api/support/tickets` (Header `X-Support-Token`) → Posteingang
unter `/admin/support` → Antwort zurück über `GET /api/support/tickets`. Das
Token bestimmt, wer meldet; einen Weg, im Namen eines anderen Kunden zu
schreiben, gibt es nicht. Grenzen und Statusfluss:
[docs/hilfe-funktion.md](docs/hilfe-funktion.md).

## Konventionen

- `declare(strict_types=1);` in jeder PHP-Datei.
- Neue Route: `require_once` + Instanziierung + `$router->add(...)` in
  `public/index.php`, alle drei.
- Neue Migration: fortlaufend nummerierte `.sql` in `migrations/`, danach
  `/admin/migrations` → *Anwenden*. Der Deploy-Workflow ruft nach dem rsync
  automatisch `php scripts/migrate.php` auf dem Ziel auf.
- Alles Sicherheitsrelevante (Login, Rollen, Vault-Zugriff, Deploy, Migration,
  CI-Abruf, Support) geht durch [AuditLogger](services/AuditLogger.php).
- Logs unter `storage/logs/` über `FileLogger::channel(...)`; `storage/` ist
  Laufzeitverzeichnis und nicht eingecheckt.
- Die CSP in `public/index.php` erlaubt `connect-src` auf `https://127.0.0.1:8765`
  — das ist der lokale Deploy-Agent. Beim Anpassen der CSP nicht verlieren.

## Release-Prozess (verbindlich)

- Nach jeder abgeschlossenen Änderung den Benutzer **ausdrücklich fragen**, ob sie nach GitHub übernommen und ausgerollt werden soll.
- Ohne Zustimmung nicht committen, pushen, mergen oder deployen.
- Nach Zustimmung in `main` übernehmen und die zugehörige GitHub Action
  erfolgreich abschließen und prüfen.
- Geheimnisse, `.env`, Vault-Inhalte und Kundenzugangsdaten niemals einchecken.

# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@DESIGN.md

## Was das Projekt ist

DigiWtal Verwaltung — die zentrale Betreiberanwendung für Kunden, Zugangsdaten
(Vault), Rollouts, Monitoring und Support. Sie enthält **weder den CMS-Quellcode
noch ein Kundenfrontend**; beides liegt in eigenen Repositories (`CMS/`,
`Frontend/` sind bewusst in `.gitignore`).

Plain PHP 8.x, **kein Composer, kein Autoloader, kein Framework, keine
Testsuite, kein Build-Schritt.** Router, .env-Loader, Logger, TOTP und Krypto
sind handgeschrieben. Das ist historisch gewachsen und kein Grundsatz: so hat
das Projekt angefangen, und es gab bisher keinen Anlass umzustellen. Daher auch
die `require_once`-Kette in `public/index.php` — ohne Paketmanager kein
Autoloader, ohne Autoloader jede Klasse von Hand. Wer Composer einführt, löst
beides in einem Zug auf.

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
einem: `.env` laden, Security-Header und CSP setzen, PDO aufbauen, **jede** Klasse
einzeln `require_once`-en, alle Repositories, Services und Controller
instanziieren, danach die Routen registrieren.

Eine neue Klasse ist erst benutzbar, wenn sie dort ein `require_once` bekommt —
es gibt keinen Autoloader. CLI-Skripte laden stattdessen
[app/bootstrap.php](app/bootstrap.php) (`.env` + Fehlerbehandlung, keine Session)
und ihre Abhängigkeiten selbst.

[app/Router.php](app/Router.php) ersetzt jedes `{param}` durch `(\d+)` —
Routenparameter können **nur numerische IDs** sein.

### Konventionen in den Schichten

Controller beginnen mit `AdminAuth::requireAuth()`, prüfen bei POST
`Csrf::verify($_POST['csrf_token'])`, melden über
`$_SESSION['flash_success'] / ['flash_errors'] / ['flash_old']` und laden am Ende
`require __DIR__ . '/../views/...'`. Rollen werden inline geprüft
(`$_SESSION['admin_role'] !== 'superadmin'`), nicht über Middleware.

Views setzen Variablen und schließen mit `require __DIR__ . '/../layout.php'` ab.

### Vault

[app/VaultCrypto.php](app/VaultCrypto.php): AES-256-GCM, Schlüssel aus
`VAULT_KEY_BASE64`, **AAD immer `'cust:' . $customerId`**. Jedes Geheimnis liegt
als Spaltentripel `<prefix>_enc` / `<prefix>_nonce` / `<prefix>_tag`, meist auf
`server_access`.

Betriebs-Tokens (`health`, `deploy`, `migration`, `setup`, `api` in
[CustomerRuntimeTokenRepository](repositories/CustomerRuntimeTokenRepository.php),
dazu `support` in [SupportTokenRepository](repositories/SupportTokenRepository.php))
entstehen beim ersten Bedarf unter `SELECT ... FOR UPDATE` — nie durch
Handeintrag.

### Rollout

Die Verwaltung überträgt auf ihrem Webspace nicht selbst, sondern startet
Workflows und stellt der Pipeline die Zugangsdaten bereit:

- `GET /api/ci/deploy-target?customer=<id>` in
  [CiController](controllers/CiController.php) gibt SSH-, DB- und Token-Klartext
  heraus: nur mit widerrufbarem CI-Token (Header `X-Ci-Token`), nur ein Kunde pro
  Aufruf, jeder Abruf im Audit-Log. Vorher prüft
  `ServerAccessRepository::rolloutReadiness()`, sonst `422`.
- **CMS** (gemeinsames Repo): `/admin/ci-tokens` → `/admin/ci/dispatch`, gesteuert
  über `GITHUB_REPO`, `GITHUB_WORKFLOW`, `GITHUB_BRANCH`.
- **Frontend** (pro Kunde ein eigenes Repo): Kunden → Deployments → *Frontend
  ausrollen*, gesteuert über `customers.frontend_repository` / `frontend_ref` und
  `GITHUB_FRONTEND_WORKFLOW`. Der Workflow bekommt nur `kunde=<id>` und holt den
  Rest über `/api/ci/deploy-target`.
- `GITHUB_TOKEN` braucht `Actions: Read and write` auf dem CMS- **und** jedem
  Frontend-Repository.

[services/DeployService.php](services/DeployService.php) ist der andere Weg:
direkte Übertragung per `ssh2`, Shell-SFTP oder cURL-FTP, für lokale
Provisionierung und den Agenten in `agent/`. Welche Dateien ein Kunde bekommt,
entscheidet [ModuleCombinator](services/ModuleCombinator.php) — Plugin-Ordner
unter `CMS/plugins/` müssen namensgleich mit `modules.key_name` sein.

### Monitoring

Gemessen an zwei Orten (Webspace-Cron und `.github/workflows/health.yml`),
**bewertet und alarmiert nur in** `HealthMonitor::evaluate()`; die Pipeline
schickt nur ihre Rohmessung an `POST /api/ci/health-report`. Jeder Lauf trägt ein
Lebenszeichen in `monitor_runs` ein (`cron`, `ci` oder `rollout`); bleibt es über
20 Minuten aus, meldet das Dashboard die Überwachung selbst als ausgefallen.
Health-URL ist immer `https://<cms-domain>/api.php/api/health`. Details:
[docs/cron-setup.md](docs/cron-setup.md).

### Migrationen

`schema_migrations` führt den Stand **über den Dateinamen**. CLI und
`/admin/migrations` (nur Superadmin) benutzen dieselbe Klasse
[MigrationRunner](services/MigrationRunner.php); auf dem Webspace ist der Browser
der einzige Weg. Details: [docs/migrationen.md](docs/migrationen.md).

### Support

Kunden-CMS → `POST /api/support/tickets` (Header `X-Support-Token`) → Posteingang
`/admin/support` → Antwort zurück über `GET /api/support/tickets`. Das Token
bestimmt, wer meldet. Grenzen und Statusfluss:
[docs/hilfe-funktion.md](docs/hilfe-funktion.md).

## Festgelegte Entscheidungen

- **Rollout über GitHub Actions statt eigener Übertragung** — der IONOS-Webspace
  der Verwaltung hat keine `ssh2`-Erweiterung, keine nutzbare Shell und kein
  offenes FTP.
- **Die Verwaltung bleibt die einzige Quelle der Zugangsdaten** — sonst müssten
  dieselben Geheimnisse ein zweites Mal in GitHub gepflegt werden und könnten
  auseinanderlaufen.
- **Betriebs- und Support-Tokens entstehen automatisch** — die gescheiterten
  ersten Rollouts gingen sämtlich auf leer gelassene Formularfelder zurück.
- **Migrationsstand über den Dateinamen, nicht über die Nummer** — `008` und
  `009` sind je doppelt vergeben, weil früher von Hand eingespielt wurde.
- **Zwei unabhängige Prüfquellen, aber nur eine Bewertungsstelle** — Cron und
  Pipeline dürfen nicht auseinanderlaufen, deshalb entscheidet allein
  `HealthMonitor::evaluate()` über Status und Alarm.
- **Unvollständige Vault-Tripel sind ein Fehler, kein Anlass zum Neuerzeugen** —
  stilles Rotieren würde eine laufende Instanz aussperren.
- **Kundenfrontends werden nicht global vorbelegt** — sie werden pro Rollout
  ausgewählt oder über die Kundenzuordnung aufgelöst.
- **Dunkles Theme dauerhaft, System-Schriften, keine Akzentfarbe** — die primäre
  Aktion ist weiß und farblos, Blau bleibt der Information. Siehe
  [DESIGN.md](DESIGN.md).

## Verworfenes

- **Direktes SFTP/FTP aus der Verwaltung heraus** wurde versucht und verworfen,
  weil auf dem IONOS-Webspace `ssh2` fehlt, keine Shell existiert und Port 21
  geschlossen ist. [DeployService](services/DeployService.php) bleibt nur für
  lokale Provisionierung und den Agenten bestehen.
- **Der Webspace-Cron als alleinige Überwachung** wurde verworfen, weil am
  26.08.2026 ein Deploy ihn stoppte und es niemandem auffiel — das Dashboard
  zeigte einfach die zuletzt gespeicherten Werte weiter.
- **Der Health-Aufruf ohne `api.php`** wurde verworfen, weil er beim
  Admin-Front-Controller landet und mit 404 antwortet; die Instanz gilt dann
  fälschlich als down.
- **Pauschales Markieren aller offenen Migrationen als Bestand** wurde verworfen,
  weil eine wirklich neue Migration damit als erledigt gälte und nie liefe.
- **Tokens von Hand eintragen** wurde nach vier gescheiterten Rollout-Anläufen
  verworfen; leere Felder waren jedes Mal die Ursache.
- **Unicode-Glyphen als Icons** werden abgelöst, weil sie je nach Betriebssystem
  unterschiedlich breit rendern und `?` für Hilfe ein Zeichen ist, kein Icon.

## Konventionen

- `declare(strict_types=1);` in jeder PHP-Datei.
- Neue Route: `require_once` + Instanziierung + `$router->add(...)` in
  `public/index.php`, alle drei.
- Neue Migration: fortlaufend nummerierte `.sql` in `migrations/`, danach
  `/admin/migrations` → *Anwenden*. Der Deploy-Workflow ruft nach dem rsync
  automatisch `php scripts/migrate.php` auf dem Ziel auf.
- Alles Sicherheitsrelevante (Login, Rollen, Vault, Deploy, Migration, CI-Abruf,
  Support) geht durch [AuditLogger](services/AuditLogger.php).
- Logs unter `storage/logs/` über `FileLogger::channel(...)`.
- Die CSP in `public/index.php` erlaubt `connect-src` auf `https://127.0.0.1:8765`
  — das ist der lokale Deploy-Agent. Beim Anpassen der CSP nicht verlieren.

## Release-Prozess (verbindlich)

- Nach jeder abgeschlossenen Änderung den Benutzer **ausdrücklich fragen**, ob sie nach GitHub übernommen und ausgerollt werden soll.
- Ohne Zustimmung nicht committen, pushen, mergen oder deployen.
- Nach Zustimmung in `main` übernehmen und die zugehörige GitHub Action
  erfolgreich abschließen und prüfen.
- Geheimnisse, `.env`, Vault-Inhalte und Kundenzugangsdaten niemals einchecken.

# DigiWtal Verwaltung

Zentrale Betreiberanwendung fuer Kunden, Zugangsdaten, Rollouts, Monitoring und
Support. Die Verwaltung ist ein eigenstaendiges Projekt und enthaelt weder den
CMS-Quellcode noch ein Kundenfrontend.

## Lokal starten

1. `.env.example` nach `.env` kopieren und Datenbank sowie Vault konfigurieren.
2. `php scripts/migrate.php` ausfuehren.
3. `php -S localhost:8003 -t public` starten.

`CMS_SOURCE_DIR` kann fuer lokale Provisionierung auf das separate CMS-Projekt
zeigen. Kundenfrontends werden bewusst nicht global vorbelegt; sie werden beim
Frontend-Rollout explizit ausgewaehlt beziehungsweise spaeter ueber die
Kundenzuordnung aufgeloest.

## Kundenspezifisches Frontend ausrollen

Unter **Kunden → Deployments** startet die Aktion **Frontend ausrollen** den
Workflow des beim Kunden hinterlegten Frontend-Repositories. Verwendet werden
`frontend_repository`, `frontend_ref` und standardmäßig der Workflow
`deploy.yml`. Ein abweichender Workflow-Dateiname kann in der Verwaltung über
`GITHUB_FRONTEND_WORKFLOW` gesetzt werden.

`GITHUB_TOKEN` benötigt dafür `Actions: Read and write` auf dem jeweiligen
Frontend-Repository. Der gemeinsame CMS-Rollout bleibt davon getrennt und wird
weiterhin unter **Rollout über GitHub** verwaltet.

Secrets, Vault-Schluessel und `.env` duerfen nicht eingecheckt werden.

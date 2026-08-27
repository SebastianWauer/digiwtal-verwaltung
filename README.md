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

Secrets, Vault-Schluessel und `.env` duerfen nicht eingecheckt werden.

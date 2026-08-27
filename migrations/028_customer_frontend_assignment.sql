-- Eindeutige Zuordnung eines Kunden zu seinem eigenstaendigen Frontend-Repo.

ALTER TABLE customers
    ADD COLUMN frontend_key VARCHAR(100) NULL DEFAULT NULL AFTER domain,
    ADD COLUMN frontend_repository VARCHAR(255) NOT NULL DEFAULT '' AFTER frontend_key,
    ADD COLUMN frontend_ref VARCHAR(100) NOT NULL DEFAULT 'main' AFTER frontend_repository,
    ADD UNIQUE INDEX uq_customers_frontend_key (frontend_key);

-- Bekannte bestehende Kunden erhalten ihre eindeutige lokale Identitaet. Das
-- GitHub-Repository bleibt leer, bis das jeweilige Remote-Repo angelegt ist.
UPDATE customers
SET frontend_key = 'sport-voice',
    frontend_repository = 'SebastianWauer/frontend-sport-voice',
    frontend_ref = 'main'
WHERE LOWER(TRIM(name)) IN ('sport voice', 'sport-voice')
  AND frontend_key IS NULL
ORDER BY id
LIMIT 1;

UPDATE customers
SET frontend_key = 'digiwtal',
    frontend_repository = 'SebastianWauer/frontend-digiwtal',
    frontend_ref = 'main'
WHERE LOWER(TRIM(name)) IN ('digiwtal', 'digiwtal cms')
  AND frontend_key IS NULL
ORDER BY id
LIMIT 1;

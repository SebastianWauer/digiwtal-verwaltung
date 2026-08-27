-- Getrennte, verschluesselte Laufzeit-Tokens fuer jede Kundeninstanz.

ALTER TABLE server_access
    ADD COLUMN migration_token_enc TEXT NULL AFTER deploy_token_tag,
    ADD COLUMN migration_token_nonce VARCHAR(64) NOT NULL DEFAULT '' AFTER migration_token_enc,
    ADD COLUMN migration_token_tag VARCHAR(64) NOT NULL DEFAULT '' AFTER migration_token_nonce,
    ADD COLUMN setup_token_enc TEXT NULL AFTER migration_token_tag,
    ADD COLUMN setup_token_nonce VARCHAR(64) NOT NULL DEFAULT '' AFTER setup_token_enc,
    ADD COLUMN setup_token_tag VARCHAR(64) NOT NULL DEFAULT '' AFTER setup_token_nonce,
    ADD COLUMN cms_api_token_enc TEXT NULL AFTER setup_token_tag,
    ADD COLUMN cms_api_token_nonce VARCHAR(64) NOT NULL DEFAULT '' AFTER cms_api_token_enc,
    ADD COLUMN cms_api_token_tag VARCHAR(64) NOT NULL DEFAULT '' AFTER cms_api_token_nonce;

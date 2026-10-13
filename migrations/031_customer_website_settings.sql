CREATE TABLE customer_website_settings (
    customer_id INT PRIMARY KEY,
    website_mode VARCHAR(16) NOT NULL DEFAULT 'multipage',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_website_settings_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bestehenden Sport-Voice-Onepager erhalten; alle anderen Kunden bleiben mehrseitig.
INSERT INTO customer_website_settings (customer_id, website_mode)
SELECT id, 'onepager' FROM customers WHERE frontend_key = 'sport-voice';

<?php
declare(strict_types=1);

class CustomerWebsiteRepository
{
    public function __construct(private PDO $pdo) {}

    public static function validateMode(string $mode): string
    {
        if (!in_array($mode, ['multipage', 'onepager'], true)) throw new InvalidArgumentException('Ungueltiger Website-Modus.');
        return $mode;
    }

    public function findMode(int $customerId): string
    {
        $stmt = $this->pdo->prepare('SELECT website_mode FROM customer_website_settings WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        return $stmt->fetchColumn() === 'onepager' ? 'onepager' : 'multipage';
    }

    public function save(int $customerId, string $mode): void
    {
        self::validateMode($mode);
        $stmt = $this->pdo->prepare('INSERT INTO customer_website_settings (customer_id, website_mode) VALUES (?, ?) ON DUPLICATE KEY UPDATE website_mode = VALUES(website_mode)');
        $stmt->execute([$customerId, $mode]);
    }
}

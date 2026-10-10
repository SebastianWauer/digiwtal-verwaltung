<?php
declare(strict_types=1);

class CustomerBlockRepository
{
    public function __construct(private PDO $pdo) {}

    public static function catalog(): array
    {
        return json_decode((string)file_get_contents(__DIR__ . '/../app/pagebuilder-blocks.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function available(string $customerKey): array
    {
        return array_filter(self::catalog(), static fn(array $block): bool => $block['customer_key'] === '' || $block['customer_key'] === $customerKey);
    }

    public static function defaults(): array
    {
        return array_keys(array_filter(self::catalog(), static fn(array $block): bool => $block['customer_key'] === ''));
    }

    public function findByCustomer(int $customerId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT allowed_blocks FROM customer_pagebuilder_blocks WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $json = $stmt->fetchColumn();
        if ($json === false) return null;
        $blocks = json_decode((string)$json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($blocks) || !array_is_list($blocks)) throw new RuntimeException('Ungueltige Blockfreigaben.');
        return $blocks;
    }

    public function save(int $customerId, string $customerKey, array $blocks): void
    {
        $available = self::available($customerKey);
        foreach ($blocks as $type) {
            if (!is_string($type) || !isset($available[$type])) throw new InvalidArgumentException('Ein Block ist fuer diesen Kunden nicht verfuegbar.');
        }
        $blocks = array_values(array_unique($blocks));
        $stmt = $this->pdo->prepare('INSERT INTO customer_pagebuilder_blocks (customer_id, allowed_blocks) VALUES (?, ?) ON DUPLICATE KEY UPDATE allowed_blocks = VALUES(allowed_blocks)');
        $stmt->execute([$customerId, json_encode($blocks, JSON_THROW_ON_ERROR)]);
    }
}

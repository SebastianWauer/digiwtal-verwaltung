<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__ . '/../repositories/CustomerBlockRepository.php';
require_once __DIR__ . '/../services/CmsProvisioningService.php';

$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
// Nur im Test: die MySQL-Upsert-Anweisung auf SQLite abbilden.
$pdo = new class('sqlite::memory:') extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $query = str_replace('ON DUPLICATE KEY UPDATE allowed_blocks = VALUES(allowed_blocks)', 'ON CONFLICT(customer_id) DO UPDATE SET allowed_blocks = excluded.allowed_blocks', $query);
        return parent::prepare($query, $options);
    }
};
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE customer_pagebuilder_blocks (customer_id INTEGER PRIMARY KEY, allowed_blocks TEXT NOT NULL)');
$repo = new CustomerBlockRepository($pdo);
$check($repo->findByCustomer(1) === null, 'Bestandskunde muss den Standardfallback erhalten.');
$available = CustomerBlockRepository::available('sport-voice');
$other = CustomerBlockRepository::available('anderer-kunde');
$check(count($available) === 27 && count($other) === 18, 'Kundenkatalog ist nicht getrennt.');
$own = array_keys(array_filter($available, static fn($block) => $block['customer_key'] !== ''));
$repo->save(1, 'sport-voice', $own);
$check($repo->findByCustomer(1) === $own, 'Kundenfreigaben verlieren Daten.');
$repo->save(2, 'anderer-kunde', []);
$check($repo->findByCustomer(2) === [], 'Leere Auswahl darf nicht den Standardbestand freigeben.');
foreach ([['sport_voice_hero'], ['unknown'], [123]] as $forbidden) {
    $rejected = false;
    try { $repo->save(2, 'anderer-kunde', $forbidden); }
    catch (InvalidArgumentException) { $rejected = true; }
    $check($rejected, 'Fremde oder ungueltige Bloecke wurden zugewiesen.');
}
$check($repo->findByCustomer(2) === [], 'Fehlerhafte Anfrage veraendert die Freigaben.');
$repo->save(1, 'sport-voice', ['hero', 'hero']);
$check($repo->findByCustomer(1) === ['hero'], 'Upsert/Deduplizierung fehlgeschlagen.');
$env = (new CmsProvisioningService())->buildEnvContent([], '', [], null, null, 'sport-voice', $own);
$check(str_contains($env, 'PAGEBUILDER_CUSTOMER_KEY="sport-voice"'), 'Kundenkennung fehlt beim lokalen Rollout.');
$check(str_contains($env, 'PAGEBUILDER_ALLOWED_BLOCKS='), 'Freigaben fehlen beim lokalen Rollout.');
if (isset($argv[1])) {
    $cmsCatalog = json_decode((string)file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    $check($cmsCatalog === CustomerBlockRepository::catalog(), 'Verwaltung und CMS haben unterschiedliche Blockkataloge.');
}
echo "PASS: Kundenauswahl; getrennte Kataloge; persistierte/leere Freigaben; fremde Bloecke abgewiesen; Upsert; Rollout-Konfiguration.\n";

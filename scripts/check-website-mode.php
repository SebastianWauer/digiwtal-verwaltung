<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__ . '/../repositories/CustomerWebsiteRepository.php';
require_once __DIR__ . '/../services/CmsProvisioningService.php';
$pdo = new class('sqlite::memory:') extends PDO {
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace('ON DUPLICATE KEY UPDATE website_mode = VALUES(website_mode)', 'ON CONFLICT(customer_id) DO UPDATE SET website_mode = excluded.website_mode', $query), $options);
    }
};
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE customer_website_settings (customer_id INTEGER PRIMARY KEY, website_mode TEXT)');
$repo = new CustomerWebsiteRepository($pdo);
$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};
$check($repo->findMode(1) === 'multipage', 'Unkonfigurierter Kunde darf nicht automatisch Onepager werden.');
$repo->save(1, 'onepager');
$check($repo->findMode(1) === 'onepager' && $repo->findMode(2) === 'multipage', 'Kundenmodi sind nicht getrennt.');
$failed = false;
try { $repo->save(1, 'invalid'); } catch (InvalidArgumentException) { $failed = true; }
$check($failed && $repo->findMode(1) === 'onepager', 'Ungueltiger Modus veraendert Konfiguration.');
$repo->save(1, 'multipage');
$check($repo->findMode(1) === 'multipage', 'Zurueckschalten fehlgeschlagen.');
$env = (new CmsProvisioningService())->buildEnvContent([], '', [], null, null, '', null, 'onepager');
$check(str_contains($env, 'WEBSITE_MODE="onepager"'), 'Modus fehlt beim lokalen Rollout.');
echo "PASS: getrennte Kundenmodi; Standard mehrseitig; Validierung; Zurueckschalten; Rollout-Konfiguration.\n";

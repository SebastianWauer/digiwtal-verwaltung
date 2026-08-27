<?php
declare(strict_types=1);

/**
 * Verwaltet die Betriebs-Tokens einer Kundeninstanz im verschluesselten Vault.
 *
 * Ein Token wird nur erzeugt, wenn fuer seinen Zweck noch keinerlei Vault-Daten
 * vorhanden sind. Teilweise oder nicht mehr entschluesselbare Datensaetze werden
 * bewusst nicht still rotiert, weil das eine laufende Instanz aussperren wuerde.
 */
class CustomerRuntimeTokenRepository
{
    /** @var array<string,string> API-Schluessel => Spaltenpraefix */
    private const TOKEN_PREFIXES = [
        'health'    => 'health_token',
        'deploy'    => 'deploy_token',
        'migration' => 'migration_token',
        'setup'     => 'setup_token',
        'api'       => 'cms_api_token',
    ];

    public function __construct(private PDO $pdo) {}

    /**
     * @return array{tokens:array<string,string>,created:list<string>}|null
     */
    public function ensureFor(int $customerId): ?array
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $columns = [];
            foreach (self::TOKEN_PREFIXES as $prefix) {
                $columns[] = $prefix . '_enc';
                $columns[] = $prefix . '_nonce';
                $columns[] = $prefix . '_tag';
            }

            // Verhindert, dass zwei zeitgleiche Erstdeployments verschiedene
            // Tokens erzeugen und jeweils einen anderen Klartext verwenden.
            $stmt = $this->pdo->prepare(
                'SELECT ' . implode(', ', $columns) . '
                 FROM server_access WHERE customer_id = :cid LIMIT 1 FOR UPDATE'
            );
            $stmt->execute([':cid' => $customerId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                if ($ownsTransaction) {
                    $this->pdo->commit();
                }
                return null;
            }

            $tokens = [];
            $created = [];
            $updates = [];
            $params = [':cid' => $customerId];
            $aad = 'cust:' . $customerId;

            foreach (self::TOKEN_PREFIXES as $name => $prefix) {
                $enc = trim((string)($row[$prefix . '_enc'] ?? ''));
                $nonce = trim((string)($row[$prefix . '_nonce'] ?? ''));
                $tag = trim((string)($row[$prefix . '_tag'] ?? ''));
                $parts = array_filter([$enc, $nonce, $tag], static fn(string $value): bool => $value !== '');

                if (count($parts) === 3) {
                    $plain = VaultCrypto::decrypt($enc, $nonce, $tag, $aad);
                    if ($plain === '') {
                        throw new RuntimeException('Leeres ' . $name . '-Token im Vault.');
                    }
                    $tokens[$name] = $plain;
                    continue;
                }

                if ($parts !== []) {
                    throw new RuntimeException('Unvollstaendiger Vault-Datensatz fuer ' . $name . '.');
                }

                $plain = bin2hex(random_bytes(32));
                $encrypted = VaultCrypto::encrypt($plain, $aad);
                $tokens[$name] = $plain;
                $created[] = $name;

                foreach (['enc' => 'ciphertext_b64', 'nonce' => 'nonce_b64', 'tag' => 'tag_b64'] as $suffix => $source) {
                    $column = $prefix . '_' . $suffix;
                    $parameter = ':' . $column;
                    $updates[] = $column . ' = ' . $parameter;
                    $params[$parameter] = $encrypted[$source];
                }
            }

            if ($updates !== []) {
                $update = $this->pdo->prepare(
                    'UPDATE server_access SET ' . implode(', ', $updates) . ' WHERE customer_id = :cid LIMIT 1'
                );
                $update->execute($params);
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return ['tokens' => $tokens, 'created' => $created];
        } catch (Throwable $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}

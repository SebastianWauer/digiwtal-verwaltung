<?php
declare(strict_types=1);

final class GitHubWorkflowDispatcher
{
    /**
     * @param array<string,string> $inputs
     * @return array{ok:bool,status:int,detail:string}
     */
    public function dispatch(
        string $repository,
        string $token,
        string $workflow,
        string $ref,
        array $inputs = []
    ): array {
        $repository = trim($repository);
        $token = trim($token);
        $workflow = trim($workflow);
        $ref = trim($ref);

        if (preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repository) !== 1) {
            return ['ok' => false, 'status' => 0, 'detail' => 'GitHub-Repository ist ungültig.'];
        }
        if ($token === '') {
            return ['ok' => false, 'status' => 0, 'detail' => 'GITHUB_TOKEN fehlt in der Verwaltung.'];
        }
        if (preg_match('/^[A-Za-z0-9_.-]+$/', $workflow) !== 1) {
            return ['ok' => false, 'status' => 0, 'detail' => 'GitHub-Workflow ist ungültig.'];
        }
        if (preg_match('#^[A-Za-z0-9][A-Za-z0-9._/-]{0,99}$#', $ref) !== 1
            || str_contains($ref, '..')
            || str_contains($ref, '//')) {
            return ['ok' => false, 'status' => 0, 'detail' => 'GitHub-Branch oder -Tag ist ungültig.'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'detail' => 'Die PHP-cURL-Erweiterung fehlt.'];
        }

        try {
            $body = json_encode([
                'ref' => $ref,
                'inputs' => $inputs,
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ['ok' => false, 'status' => 0, 'detail' => 'Workflow-Anfrage konnte nicht erstellt werden.'];
        }

        [$owner, $name] = explode('/', $repository, 2);
        $url = 'https://api.github.com/repos/'
            . rawurlencode($owner) . '/' . rawurlencode($name)
            . '/actions/workflows/' . rawurlencode($workflow) . '/dispatches';

        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'detail' => 'GitHub-Verbindung konnte nicht initialisiert werden.'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'X-GitHub-Api-Version: 2022-11-28',
                'Content-Type: application/json',
                'User-Agent: Digiwtal-Verwaltung',
            ],
        ]);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($status === 204) {
            return ['ok' => true, 'status' => $status, 'detail' => ''];
        }

        $detail = $curlError;
        if ($detail === '' && is_string($response)) {
            $decoded = json_decode($response, true);
            $detail = is_array($decoded)
                ? trim((string)($decoded['message'] ?? ''))
                : '';
        }

        return [
            'ok' => false,
            'status' => $status,
            'detail' => 'GitHub HTTP ' . $status . ($detail !== '' ? ': ' . $detail : ''),
        ];
    }
}

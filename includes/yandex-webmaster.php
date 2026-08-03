<?php
/**
 * Yandex Webmaster API helper
 */

function getYandexWebmasterConfigPath(): string {
    return __DIR__ . '/../data/yandex-webmaster.json';
}

function yandexWebmasterAvailable(): bool {
    return file_exists(getYandexWebmasterConfigPath());
}

function getYandexWebmasterConfig(): ?array {
    $path = getYandexWebmasterConfigPath();
    if (!file_exists($path)) return null;
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function saveYandexWebmasterConfig(array $config): bool {
    $path = getYandexWebmasterConfigPath();
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $ok = file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false;
    if ($ok) @chmod($path, 0600);
    return $ok;
}

function yandexWebmasterApiRequest(string $method, string $url, ?array $body = null): array {
    $cfg = getYandexWebmasterConfig();
    if (!$cfg || empty($cfg['oauth_token'])) {
        return ['success' => false, 'status' => 0, 'error' => 'Missing OAuth token'];
    }
    $headers = [
        'Authorization: OAuth ' . $cfg['oauth_token'],
        'Content-Type: application/json',
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $data = json_decode((string)$response, true);
    return [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => is_array($data) ? $data : ['raw' => $response],
        'error' => $error ?: null,
    ];
}

function yandexWebmasterTestConnection(): array {
    $cfg = getYandexWebmasterConfig();
    if (!$cfg) return ['success' => false, 'error' => 'No config'];
    $userId = $cfg['user_id'] ?? '';
    if (!$userId) return ['success' => false, 'error' => 'No user_id'];
    return yandexWebmasterApiRequest('GET', 'https://api.webmaster.yandex.net/v4/user/' . rawurlencode((string)$userId) . '/hosts');
}

function yandexSubmitRecrawl(string $fullUrl): array {
    $cfg = getYandexWebmasterConfig();
    if (!$cfg) return ['success' => false, 'error' => 'No config'];
    if (empty($cfg['user_id']) || empty($cfg['host_id'])) {
        return ['success' => false, 'error' => 'Missing user_id or host_id'];
    }
    $url = 'https://api.webmaster.yandex.net/v4/user/' . rawurlencode((string)$cfg['user_id'])
         . '/hosts/' . rawurlencode((string)$cfg['host_id']) . '/recrawl/queue';
    return yandexWebmasterApiRequest('POST', $url, ['url' => $fullUrl]);
}

function yandexSubmitBatch(array $urls): array {
    $results = [];
    $success = 0;
    $failed = 0;
    foreach ($urls as $url) {
        $result = yandexSubmitRecrawl($url);
        $results[] = [
            'url' => $url,
            'success' => $result['success'],
            'status' => $result['status'] ?? 0,
            'data' => $result['data'] ?? [],
            'error' => $result['error'] ?? null,
        ];
        if ($result['success']) $success++; else $failed++;
        usleep(150000);
    }
    return [
        'total' => count($urls),
        'success' => $success,
        'failed' => $failed,
        'results' => $results,
    ];
}

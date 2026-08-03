<?php
/**
 * Google Indexing API — автоматическая отправка URL на индексацию
 * Использует Service Account для JWT-авторизации
 */

function getGoogleServiceAccountPath(): string {
    return __DIR__ . '/../data/google-service-account.json';
}

function googleIndexingAvailable(): bool {
    return file_exists(getGoogleServiceAccountPath());
}

/**
 * Генерация JWT токена для Google API
 */
function generateGoogleJWTWithScope(string $scope): ?string {
    $keyFile = getGoogleServiceAccountPath();
    if (!file_exists($keyFile)) return null;

    $sa = json_decode(file_get_contents($keyFile), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) return null;

    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $claims = base64_encode(json_encode([
        'iss' => $sa['client_email'],
        'scope' => $scope,
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ]));

    $toSign = str_replace(['+', '/', '='], ['-', '_', ''], $header) . '.' . str_replace(['+', '/', '='], ['-', '_', ''], $claims);

    $privateKey = openssl_pkey_get_private($sa['private_key']);
    if (!$privateKey) return null;

    $signature = '';
    if (!openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) return null;

    $jwt = $toSign . '.' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function generateGoogleSearchConsoleToken(): ?string {
    return generateGoogleJWTWithScope('https://www.googleapis.com/auth/webmasters.readonly');
}

function generateGoogleJWT(): ?string {
    $keyFile = getGoogleServiceAccountPath();
    if (!file_exists($keyFile)) return null;

    $sa = json_decode(file_get_contents($keyFile), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) return null;

    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $claims = base64_encode(json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ]));

    $toSign = str_replace(['+', '/', '='], ['-', '_', ''], $header) . '.' . str_replace(['+', '/', '='], ['-', '_', ''], $claims);

    $privateKey = openssl_pkey_get_private($sa['private_key']);
    if (!$privateKey) return null;

    $signature = '';
    if (!openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA256)) return null;

    $jwt = $toSign . '.' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    // Exchange JWT for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Отправить URL на индексацию в Google
 * @param string $url Полный URL
 * @param string $type URL_UPDATED или URL_DELETED
 * @return array ['success' => bool, 'status' => int, 'response' => array]
 */
function googleIndexUrl(string $url, string $type = 'URL_UPDATED'): array {
    static $token = null;
    if (!$token) {
        $token = generateGoogleJWT();
    }
    if (!$token) {
        return ['success' => false, 'error' => 'Failed to get access token'];
    }

    $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'url' => $url,
            'type' => $type,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true) ?: [];

    return [
        'success' => $httpCode === 200,
        'status' => $httpCode,
        'response' => $data,
    ];
}

/**
 * Пакетная отправка URL на индексацию
 * @param array $urls Массив полных URL
 * @return array Результат
 */
function googleIndexBatch(array $urls): array {
    $results = [];
    $success = 0;
    $failed = 0;

    foreach ($urls as $url) {
        $result = googleIndexUrl($url);
        $results[] = [
            'url' => $url,
            'success' => $result['success'],
            'status' => $result['status'],
        ];
        if ($result['success']) $success++;
        else $failed++;

        // Google rate limit: ~200 requests per day
        usleep(100000); // 100ms between requests
    }

    return [
        'total' => count($urls),
        'success' => $success,
        'failed' => $failed,
        'results' => $results,
    ];
}

/**
 * Проверить статус индексации URL
 */
function googleGetIndexStatus(string $url): array {
    $token = generateGoogleJWT();
    if (!$token) return ['error' => 'No token'];

    $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications/metadata?url=' . urlencode($url));
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'data' => json_decode($response, true) ?: [],
    ];
}

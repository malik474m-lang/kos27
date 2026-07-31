<?php
/**
 * Система лицензирования KosmoEngine
 * Проверка лицензии каждые 4 часа
 */

// Зашифрованный адрес сервера лицензий (base64 + reverse)
define('_LS_ENC', 'dXIuYW1pemFvbXNvay52cmVz'); // serv.kosmozaim.ru в base64 reversed

function _lsUrl(): string {
    return 'https://' . strrev(base64_decode(_LS_ENC));
}

function getLicenseFile(): string {
    return __DIR__ . '/../data/license.json';
}

function getLicenseData(): array {
    $file = getLicenseFile();
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveLicenseData(array $data): bool {
    $file = getLicenseFile();
    $dir = dirname($file);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function getLicenseKey(): string {
    return getLicenseData()['license_key'] ?? '';
}

function getCurrentDomain(): string {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $host = strtolower(trim($host));
    $host = preg_replace('#^www\.#', '', $host);
    return $host;
}

/**
 * Проверка лицензии на сервере
 */
function verifyLicenseRemote(string $key, string $domain): array {
    $url = _lsUrl() . '/api/check';
    
    $payload = json_encode([
        'license_key' => $key,
        'domain' => $domain
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error || $httpCode === 0) {
        // Ошибка соединения — даём grace period
        return ['valid' => null, 'error' => 'Connection failed: ' . $error, 'code' => 'CONNECTION_ERROR'];
    }
    
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['valid' => false, 'error' => 'Invalid response', 'code' => 'INVALID_RESPONSE'];
    }
    
    return $data;
}

/**
 * Активация лицензии
 */
function activateLicense(string $key): array {
    $domain = getCurrentDomain();
    
    if (!$key) {
        return ['success' => false, 'error' => 'Введите ключ лицензии'];
    }
    
    // Проверяем на сервере
    $result = verifyLicenseRemote($key, $domain);
    
    if (isset($result['valid']) && $result['valid'] === true) {
        // Сохраняем
        $licenseData = [
            'license_key' => $key,
            'domain' => $domain,
            'activated_at' => date('Y-m-d H:i:s'),
            'last_check' => time(),
            'status' => 'active',
            'plan' => $result['license']['plan'] ?? 'unknown',
            'expires_at' => $result['license']['expires_at'] ?? null,
        ];
        saveLicenseData($licenseData);
        
        return ['success' => true, 'message' => 'Лицензия активирована!', 'license' => $result['license'] ?? []];
    }
    
    $errorMsg = $result['error'] ?? 'Неверный ключ лицензии';
    $code = $result['code'] ?? 'UNKNOWN';
    
    $messages = [
        'INVALID_KEY' => 'Неверный ключ лицензии',
        'DOMAIN_MISMATCH' => 'Лицензия привязана к другому домену',
        'EXPIRED' => 'Срок действия лицензии истёк',
        'BLOCKED' => 'Лицензия заблокирована',
        'SUSPENDED' => 'Лицензия приостановлена',
    ];
    
    return ['success' => false, 'error' => $messages[$code] ?? $errorMsg, 'code' => $code];
}

/**
 * Проверка статуса лицензии (с кэшированием на 4 часа)
 */
function checkLicenseStatus(): array {
    $data = getLicenseData();
    $key = $data['license_key'] ?? '';
    
    if (!$key) {
        return ['valid' => false, 'reason' => 'NO_LICENSE'];
    }
    
    $lastCheck = $data['last_check'] ?? 0;
    $now = time();
    $checkInterval = 4 * 3600; // 4 часа
    
    // Если прошло меньше 4 часов — используем кэш
    if (($now - $lastCheck) < $checkInterval) {
        $status = $data['status'] ?? 'unknown';
        if ($status === 'active') {
            // Проверяем срок действия локально
            $expiresAt = $data['expires_at'] ?? null;
            if ($expiresAt && strtotime($expiresAt) < $now) {
                return ['valid' => false, 'reason' => 'EXPIRED'];
            }
            return ['valid' => true, 'cached' => true, 'plan' => $data['plan'] ?? ''];
        }
        return ['valid' => false, 'reason' => strtoupper($status)];
    }
    
    // Пора проверить на сервере
    $domain = getCurrentDomain();
    $result = verifyLicenseRemote($key, $domain);
    
    // Ошибка соединения — grace period (даём работать ещё сутки)
    if ($result['valid'] === null || ($result['code'] ?? '') === 'CONNECTION_ERROR') {
        $gracePeriod = 24 * 3600; // 24 часа
        if (($now - $lastCheck) < $gracePeriod) {
            return ['valid' => true, 'grace' => true, 'plan' => $data['plan'] ?? ''];
        }
        return ['valid' => false, 'reason' => 'CONNECTION_FAILED'];
    }
    
    // Обновляем данные
    if ($result['valid'] === true) {
        $data['last_check'] = $now;
        $data['status'] = 'active';
        $data['plan'] = $result['license']['plan'] ?? $data['plan'];
        $data['expires_at'] = $result['license']['expires_at'] ?? $data['expires_at'];
        saveLicenseData($data);
        return ['valid' => true, 'plan' => $data['plan']];
    }
    
    // Лицензия невалидна
    $data['status'] = 'invalid';
    $data['last_check'] = $now;
    saveLicenseData($data);
    
    return ['valid' => false, 'reason' => $result['code'] ?? 'INVALID'];
}

/**
 * Проверка и блокировка сайта если нет лицензии
 * Вызывается в начале index.php
 */
function requireLicense(): void {
    // Пропускаем API и статику
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)(\?|$)#i', $uri)) {
        return;
    }
    
    // Пропускаем страницу лицензии в админке
    if (strpos($uri, '/admin') === 0) {
        // Проверяем только если это не страница ввода лицензии
        $adminPath = substr(parse_url($uri, PHP_URL_PATH), 6);
        if ($adminPath === '/license' || $adminPath === '/license/') {
            return;
        }
        // Для других страниц админки — проверяем лицензию
    }
    
    $status = checkLicenseStatus();
    
    if ($status['valid']) {
        return; // Всё ОК
    }
    
    // Для админки — редирект на страницу лицензии
    if (strpos($uri, '/admin') === 0) {
        if (isAdmin()) {
            header('Location: /admin/license');
            exit;
        }
    }
    
    // Для публичных страниц — показываем заглушку
    showLicenseError($status['reason'] ?? 'NO_LICENSE');
}

/**
 * Показать страницу ошибки лицензии
 */
function showLicenseError(string $reason): void {
    http_response_code(503);
    
    $messages = [
        'NO_LICENSE' => 'Лицензия не активирована',
        'EXPIRED' => 'Срок действия лицензии истёк',
        'BLOCKED' => 'Лицензия заблокирована',
        'SUSPENDED' => 'Лицензия приостановлена',
        'DOMAIN_MISMATCH' => 'Лицензия недействительна для этого домена',
        'INVALID' => 'Лицензия недействительна',
        'CONNECTION_FAILED' => 'Ошибка проверки лицензии',
    ];
    
    $message = $messages[$reason] ?? 'Лицензия не активна';
    
    echo '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сайт временно недоступен</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .container {
            text-align: center;
            padding: 40px;
            max-width: 500px;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #fff;
        }
        p {
            font-size: 16px;
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
        }
        .code {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 4px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
            color: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔐</div>
        <h1>' . htmlspecialchars($message) . '</h1>
        <p>Сайт временно недоступен.<br>Пожалуйста, обратитесь к администратору.</p>
        <div class="code">' . htmlspecialchars($reason) . '</div>
    </div>
</body>
</html>';
    exit;
}

/**
 * Получить информацию о лицензии для отображения
 */
function getLicenseInfo(): array {
    $data = getLicenseData();
    
    if (!($data['license_key'] ?? '')) {
        return ['active' => false, 'message' => 'Лицензия не установлена'];
    }
    
    $status = checkLicenseStatus();
    
    return [
        'active' => $status['valid'] ?? false,
        'key' => maskLicenseKey($data['license_key'] ?? ''),
        'key_full' => $data['license_key'] ?? '',
        'domain' => $data['domain'] ?? '',
        'plan' => $data['plan'] ?? 'unknown',
        'expires_at' => $data['expires_at'] ?? null,
        'activated_at' => $data['activated_at'] ?? null,
        'last_check' => $data['last_check'] ?? 0,
        'status' => $data['status'] ?? 'unknown',
        'cached' => $status['cached'] ?? false,
        'grace' => $status['grace'] ?? false,
    ];
}

/**
 * Маскирование ключа для отображения
 */
function maskLicenseKey(string $key): string {
    if (strlen($key) < 8) return '****';
    return substr($key, 0, 4) . '-****-****-' . substr($key, -4);
}

/**
 * Удалить лицензию
 */
function removeLicense(): bool {
    $file = getLicenseFile();
    if (file_exists($file)) {
        return unlink($file);
    }
    return true;
}

<?php
/**
 * Система лицензирования KosmoEngine
 * Проверка лицензии каждые 4 часа
 */

// Зашифрованный адрес сервера лицензий (base64 reversed)
define('_LS_ENC', 'dXIubWlhem9tc29rLnZyZXM=');

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
    
    $result = verifyLicenseRemote($key, $domain);
    
    if (isset($result['valid']) && $result['valid'] === true) {
        $licenseData = [
            'license_key' => $key,
            'domain' => $domain,
            'activated_at' => date('Y-m-d H:i:s'),
            'last_check' => time(),
            'last_server_status' => 'active',
            'status' => 'active',
            'plan' => $result['license']['plan'] ?? 'unknown',
            'expires_at' => $result['license']['expires_at'] ?? null,
        ];
        saveLicenseData($licenseData);
        
        return ['success' => true, 'message' => 'Лицензия активирована!', 'license' => $result['license'] ?? []];
    }
    
    $code = $result['code'] ?? 'UNKNOWN';
    $messages = [
        'INVALID_KEY' => 'Неверный ключ лицензии',
        'DOMAIN_MISMATCH' => 'Лицензия привязана к другому домену',
        'EXPIRED' => 'Срок действия лицензии истёк',
        'BLOCKED' => 'Лицензия заблокирована',
        'SUSPENDED' => 'Лицензия приостановлена',
    ];
    
    return ['success' => false, 'error' => $messages[$code] ?? ($result['error'] ?? 'Ошибка'), 'code' => $code];
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
    $needsCheck = ($now - $lastCheck) >= $checkInterval;
    
    // Если ещё не время проверять — используем кэш
    if (!$needsCheck) {
        $serverStatus = $data['last_server_status'] ?? 'unknown';
        
        // Если сервер ответил blocked/suspended/expired — сразу блокируем
        if (in_array($serverStatus, ['blocked', 'suspended', 'expired', 'domain_mismatch', 'invalid'])) {
            return ['valid' => false, 'reason' => strtoupper($serverStatus)];
        }
        
        if ($data['status'] === 'active') {
            // Проверяем срок действия локально
            $expiresAt = $data['expires_at'] ?? null;
            if ($expiresAt && strtotime($expiresAt) < $now) {
                return ['valid' => false, 'reason' => 'EXPIRED'];
            }
            return ['valid' => true, 'cached' => true, 'plan' => $data['plan'] ?? ''];
        }
        
        return ['valid' => false, 'reason' => strtoupper($data['status'] ?? 'UNKNOWN')];
    }
    
    // Пора проверить на сервере
    $domain = getCurrentDomain();
    $result = verifyLicenseRemote($key, $domain);
    
    // Ошибка соединения — grace period (24 часа)
    if ($result['valid'] === null || ($result['code'] ?? '') === 'CONNECTION_ERROR') {
        $gracePeriod = 24 * 3600;
        if (($now - $lastCheck) < $gracePeriod && ($data['last_server_status'] ?? '') === 'active') {
            return ['valid' => true, 'grace' => true, 'plan' => $data['plan'] ?? ''];
        }
        return ['valid' => false, 'reason' => 'CONNECTION_FAILED'];
    }
    
    // Сервер ответил — лицензия валидна
    if ($result['valid'] === true) {
        $data['last_check'] = $now;
        $data['status'] = 'active';
        $data['last_server_status'] = 'active';
        $data['plan'] = $result['license']['plan'] ?? $data['plan'];
        $data['expires_at'] = $result['license']['expires_at'] ?? $data['expires_at'];
        saveLicenseData($data);
        return ['valid' => true, 'plan' => $data['plan']];
    }
    
    // Сервер ответил — лицензия НЕвалидна (заблокирована, истекла и т.д.)
    $serverCode = strtolower($result['code'] ?? 'invalid');
    $data['status'] = 'invalid';
    $data['last_check'] = $now;
    $data['last_server_status'] = $serverCode;
    saveLicenseData($data);
    
    return ['valid' => false, 'reason' => strtoupper($serverCode)];
}

/**
 * Принудительная проверка (сбрасывает кэш)
 */
function forceLicenseCheck(): array {
    $data = getLicenseData();
    $data['last_check'] = 0; // Сбрасываем кэш
    saveLicenseData($data);
    return checkLicenseStatus();
}

/**
 * Проверка и блокировка сайта если нет лицензии
 */
function requireLicense(): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Пропускаем статику
    if (preg_match('#\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)(\?|$)#i', $uri)) {
        return;
    }
    
    // Пропускаем страницу лицензии и логин
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === '/admin/license' || $path === '/admin/license/' || $path === '/admin/login' || $path === '/admin/login/') {
        return;
    }
    
    $status = checkLicenseStatus();
    
    if ($status['valid']) {
        return;
    }
    
    // Для админки — редирект на страницу лицензии
    if (strpos($uri, '/admin') === 0) {
        if (isAdmin()) {
            header('Location: /admin/license');
            exit;
        }
    }
    
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
    
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Сайт временно недоступен</title>
<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#1a1a2e,#16213e);min-height:100vh;display:flex;align-items:center;justify-content:center;color:#fff}.c{text-align:center;padding:40px;max-width:500px}.i{font-size:80px;margin-bottom:30px}h1{font-size:28px;margin-bottom:15px}p{font-size:16px;color:rgba(255,255,255,.7);line-height:1.6}.code{display:inline-block;background:rgba(255,255,255,.1);padding:4px 12px;border-radius:4px;font-family:monospace;font-size:12px;margin-top:20px;color:rgba(255,255,255,.5)}</style>
</head><body><div class="c"><div class="i">🔐</div><h1>' . htmlspecialchars($message) . '</h1><p>Сайт временно недоступен.<br>Обратитесь к администратору.</p><div class="code">' . htmlspecialchars($reason) . '</div></div></body></html>';
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
        'server_status' => $data['last_server_status'] ?? 'unknown',
        'cached' => $status['cached'] ?? false,
        'grace' => $status['grace'] ?? false,
        'reason' => $status['reason'] ?? null,
    ];
}

function maskLicenseKey(string $key): string {
    if (strlen($key) < 8) return '****';
    return substr($key, 0, 4) . '-****-****-' . substr($key, -4);
}

function removeLicense(): bool {
    $file = getLicenseFile();
    if (file_exists($file)) return unlink($file);
    return true;
}

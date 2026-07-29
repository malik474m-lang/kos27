<?php
/**
 * Конфигурация сервера лицензирования
 * serv.kosmozaim.ru
 */

// UTF-8
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Подключение к MySQL
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    
    $host = getenv('LIC_DB_HOST') ?: 'localhost';
    $port = getenv('LIC_DB_PORT') ?: '3306';
    $name = getenv('LIC_DB_NAME') ?: 'license_server';
    $user = getenv('LIC_DB_USER') ?: 'root';
    $pass = getenv('LIC_DB_PASS') ?: '';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    return $pdo;
}

// === БЕЗОПАСНОСТЬ ===

// Секретный ключ для подписи ответов (СМЕНИТЬ!)
define('LICENSE_SIGN_KEY', getenv('LICENSE_SIGN_KEY') ?: 'KzM!s3rv#2024$xQ9pLw&vR7');

// Секретный ключ для шифрования данных между клиентом и сервером
define('LICENSE_ENCRYPT_KEY', getenv('LICENSE_ENCRYPT_KEY') ?: 'Ks28#mNx$7qPdL!wR4vE9jYz');

// Соль для хэширования
define('LICENSE_SALT', getenv('LICENSE_SALT') ?: 'k0sm0za1m_l1c_s4lt_2024');

// Админский токен для API (СМЕНИТЬ!)
define('ADMIN_API_TOKEN', getenv('LIC_ADMIN_TOKEN') ?: 'lac_admin_t0k3n_ch4ng3_m3');

/**
 * Подписать данные HMAC-SHA256
 */
function signResponse(array $data): string {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    return hash_hmac('sha256', $json, LICENSE_SIGN_KEY);
}

/**
 * Зашифровать строку (AES-256-CBC)
 */
function encryptData(string $plaintext): string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Расшифровать строку
 */
function decryptData(string $ciphertext): ?string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $raw = base64_decode($ciphertext);
    if (strlen($raw) < 17) return null;
    $iv = substr($raw, 0, 16);
    $encrypted = substr($raw, 16);
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : null;
}

/**
 * Генерировать лицензионный ключ
 */
function generateLicenseKey(): string {
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $parts[] = strtoupper(bin2hex(random_bytes(3)));
    }
    return 'KZM-' . implode('-', $parts);
}

/**
 * Нормализовать домен: убрать www, протокол, слэш
 */
function normalizeDomain(string $domain): string {
    $domain = trim(strtolower($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = rtrim($domain, '/');
    return $domain;
}

/**
 * Получить IP клиента
 */
function getClientIp(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return trim(explode(',', $ip)[0]);
}

/**
 * Rate-limit: проверить и посчитать
 * @return array ['allowed' => bool, 'remaining' => int]
 */
function checkRateLimit(string $endpoint, int $maxAttempts = 30, int $windowSec = 60): array {
    $ip = getClientIp();
    $db = getDB();
    
    // Очистка старых записей
    $db->prepare("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)")
       ->execute([$windowSec]);
    
    $stmt = $db->prepare("SELECT attempts, window_start FROM rate_limits WHERE ip = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
    $row = $stmt->fetch();
    
    if (!$row) {
        $db->prepare("INSERT INTO rate_limits (ip, endpoint, attempts) VALUES (?, ?, 1)")->execute([$ip, $endpoint]);
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }
    
    $attempts = (int)$row['attempts'];
    
    if ($attempts >= $maxAttempts) {
        return ['allowed' => false, 'remaining' => 0];
    }
    
    $db->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE ip = ? AND endpoint = ?")
       ->execute([$ip, $endpoint]);
    
    return ['allowed' => true, 'remaining' => $maxAttempts - $attempts - 1];
}

/**
 * Записать лог
 */
function logAction(string $action, ?int $licenseId, ?string $licenseKey, ?string $domain, int $responseCode, ?string $message = null): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO license_log (license_id, license_key, action, domain, ip, user_agent, response_code, message) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([
               $licenseId,
               $licenseKey,
               $action,
               $domain,
               getClientIp(),
               mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
               $responseCode,
               $message ? mb_substr($message, 0, 500) : null,
           ]);
    } catch (Exception $e) {}
}

/**
 * JSON-ответ с подписью
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    $data['timestamp'] = time();
    $data['signature'] = signResponse($data);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Ошибка
 */
function jsonError(string $message, int $code = 400): void {
    jsonResponse(['error' => $message, 'valid' => false], $code);
}

/**
 * htmlspecialchars helper
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

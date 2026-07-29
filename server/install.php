<?php
/**
 * ============================================================
 * Установщик сервера лицензирования KZM
 * ============================================================
 * 
 * Использование:
 *   1. Загрузить этот файл в корень домена serv.kosmozaim.ru
 *   2. Открыть в браузере: https://serv.kosmozaim.ru/install.php
 *   3. Заполнить форму
 *   4. Нажать «Установить»
 *   5. УДАЛИТЬ install.php после установки!
 * 
 * Или через SSH:
 *   php install.php --db-host=localhost --db-name=license_server --db-user=root --db-pass=secret --admin-pass=MyPass123
 */

// Защита: если уже установлен — не показываем
if (file_exists(__DIR__ . '/config.php') && file_exists(__DIR__ . '/index.php')) {
    if (php_sapi_name() !== 'cli') {
        echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:60px">';
        echo '<h1>⚠️ Сервер уже установлен</h1>';
        echo '<p>Удалите <code>install.php</code> для безопасности.</p>';
        echo '<p><a href="/admin">Перейти в админку →</a></p>';
        echo '</body></html>';
        exit;
    }
}

// ============================================================
// CLI-режим
// ============================================================
if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "╔══════════════════════════════════════════╗\n";
    echo "║   KZM License Server — Установка         ║\n";
    echo "╚══════════════════════════════════════════╝\n\n";
    
    $opts = getopt('', ['db-host:', 'db-name:', 'db-user:', 'db-pass:', 'db-port:', 'admin-user:', 'admin-pass:', 'help']);
    
    if (isset($opts['help'])) {
        echo "Параметры:\n";
        echo "  --db-host     Хост MySQL (default: localhost)\n";
        echo "  --db-name     Имя базы данных\n";
        echo "  --db-user     Пользователь MySQL\n";
        echo "  --db-pass     Пароль MySQL\n";
        echo "  --db-port     Порт MySQL (default: 3306)\n";
        echo "  --admin-user  Логин админа (default: admin)\n";
        echo "  --admin-pass  Пароль админа\n\n";
        echo "Пример:\n";
        echo "  php install.php --db-host=localhost --db-name=license_server --db-user=root --db-pass=secret --admin-pass=MyPass123\n\n";
        exit;
    }
    
    $dbHost = $opts['db-host'] ?? 'localhost';
    $dbPort = $opts['db-port'] ?? '3306';
    $dbName = $opts['db-name'] ?? '';
    $dbUser = $opts['db-user'] ?? '';
    $dbPass = $opts['db-pass'] ?? '';
    $adminUser = $opts['admin-user'] ?? 'admin';
    $adminPass = $opts['admin-pass'] ?? '';
    
    if (!$dbName || !$dbUser || !$adminPass) {
        echo "❌ Обязательные параметры: --db-name, --db-user, --admin-pass\n";
        echo "   Используйте --help для справки\n\n";
        exit(1);
    }
    
    echo "📋 Параметры:\n";
    echo "   MySQL: {$dbUser}@{$dbHost}:{$dbPort}/{$dbName}\n";
    echo "   Админ: {$adminUser}\n\n";
    
    $result = doInstall($dbHost, $dbPort, $dbName, $dbUser, $dbPass, $adminUser, $adminPass);
    
    if ($result['success']) {
        echo "✅ Установка завершена!\n\n";
        foreach ($result['steps'] as $step) {
            echo "   ✓ {$step}\n";
        }
        echo "\n🔑 Ключи безопасности сохранены в config.php\n";
        echo "🔐 Админка: /admin\n";
        echo "⚠️  УДАЛИТЕ install.php!\n\n";
    } else {
        echo "❌ Ошибка: {$result['error']}\n\n";
        exit(1);
    }
    exit;
}

// ============================================================
// Веб-режим
// ============================================================
$installResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installResult = doInstall(
        trim($_POST['db_host'] ?? 'localhost'),
        trim($_POST['db_port'] ?? '3306'),
        trim($_POST['db_name'] ?? ''),
        trim($_POST['db_user'] ?? ''),
        $_POST['db_pass'] ?? '',
        trim($_POST['admin_user'] ?? 'admin'),
        $_POST['admin_pass'] ?? ''
    );
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Установка — KZM License Server</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.1);width:100%;max-width:520px;overflow:hidden}
.header{background:linear-gradient(135deg,#1e40af,#7c3aed);color:#fff;padding:32px;text-align:center}
.header h1{font-size:24px;font-weight:800;margin-top:12px}
.header p{opacity:.85;font-size:14px;margin-top:6px}
.body{padding:28px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px}
input,select{width:100%;border:2px solid #e5e7eb;border-radius:10px;padding:10px 14px;font-size:14px;margin-bottom:16px;transition:border .2s}
input:focus{outline:none;border-color:#3b82f6}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{width:100%;background:#1e40af;color:#fff;border:none;padding:14px;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px;transition:background .2s}
.btn:hover{background:#1d4ed8}
.btn:disabled{opacity:.5;cursor:not-allowed}
.warn{background:#fef3c7;border:1px solid #fbbf24;border-radius:10px;padding:12px;font-size:12px;color:#92400e;margin-bottom:20px}
.ok{background:#ecfdf5;border:1px solid #10b981;border-radius:10px;padding:16px;margin-bottom:16px}
.ok h3{color:#065f46;font-size:16px;margin-bottom:8px}
.ok p{color:#047857;font-size:13px;margin-bottom:4px}
.ok code{background:#d1fae5;padding:2px 6px;border-radius:4px;font-size:12px}
.err{background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px;color:#991b1b;font-size:13px;margin-bottom:16px}
.check{font-size:13px;color:#6b7280;margin-bottom:16px}
.check span{display:block;padding:3px 0}
.check .yes{color:#059669}
.check .no{color:#dc2626}
</style>
</head>
<body>
<div class="card">
<div class="header">
<div style="font-size:48px">🔑</div>
<h1>KZM License Server</h1>
<p>Установка сервера лицензирования</p>
</div>
<div class="body">

<?php
// Проверка требований
$checks = [
    ['PHP ≥ 8.0', version_compare(PHP_VERSION, '8.0.0', '>=')],
    ['PDO MySQL', extension_loaded('pdo_mysql')],
    ['OpenSSL', extension_loaded('openssl')],
    ['JSON', extension_loaded('json')],
    ['mbstring', extension_loaded('mbstring')],
    ['Запись в директорию', is_writable(__DIR__)],
];
$allOk = true;
echo '<div class="check">';
foreach ($checks as [$name, $ok]) {
    echo '<span class="' . ($ok ? 'yes' : 'no') . '">' . ($ok ? '✅' : '❌') . " $name</span>";
    if (!$ok) $allOk = false;
}
echo '</div>';

if (!$allOk) {
    echo '<div class="err">❌ Не все требования выполнены. Исправьте ошибки выше.</div>';
}
?>

<?php if ($installResult && $installResult['success']): ?>
<div class="ok">
<h3>✅ Установка завершена!</h3>
<?php foreach ($installResult['steps'] as $step): ?>
<p>✓ <?= htmlspecialchars($step) ?></p>
<?php endforeach; ?>
<br>
<p><strong>🔐 Админка:</strong> <a href="/admin">/admin</a></p>
<p><strong>⚠️ Удалите этот файл:</strong> <code>install.php</code></p>
</div>

<?php elseif ($installResult && !$installResult['success']): ?>
<div class="err">❌ <?= htmlspecialchars($installResult['error']) ?></div>
<?php endif; ?>

<?php if (!$installResult || !$installResult['success']): ?>
<div class="warn">⚠️ После установки обязательно удалите файл <strong>install.php</strong></div>

<form method="POST">
<label>Хост MySQL</label>
<input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>

<div class="row">
<div>
<label>Имя базы данных</label>
<input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required placeholder="license_server">
</div>
<div>
<label>Порт</label>
<input type="text" name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>">
</div>
</div>

<div class="row">
<div>
<label>Пользователь MySQL</label>
<input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
</div>
<div>
<label>Пароль MySQL</label>
<input type="password" name="db_pass" value="">
</div>
</div>

<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">

<div class="row">
<div>
<label>Логин администратора</label>
<input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" required>
</div>
<div>
<label>Пароль администратора</label>
<input type="password" name="admin_pass" required minlength="6" placeholder="Минимум 6 символов">
</div>
</div>

<button type="submit" class="btn" <?= $allOk ? '' : 'disabled' ?>>🚀 Установить</button>
</form>
<?php endif; ?>

</div>
</div>
</body>
</html>
<?php

// ============================================================
// ЛОГИКА УСТАНОВКИ
// ============================================================
function doInstall(string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPass, string $adminUser, string $adminPass): array {
    $steps = [];
    $baseDir = __DIR__;
    $sourceDir = null;
    
    // Ищем файлы сервера
    $possiblePaths = [
        $baseDir . '/license-server',
        dirname($baseDir) . '/license-server',
        $baseDir,
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path . '/api/activate.php')) {
            $sourceDir = $path;
            break;
        }
    }
    
    // === 1. Проверка подключения к БД ===
    try {
        $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $steps[] = "Подключение к MySQL: OK";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Ошибка подключения к MySQL: ' . $e->getMessage()];
    }
    
    // === 2. Создание БД если не существует ===
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$dbName`");
        $steps[] = "База данных '$dbName': создана/найдена";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Ошибка создания БД: ' . $e->getMessage()];
    }
    
    // === 3. Создание таблиц ===
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `licenses` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `license_key` varchar(64) NOT NULL,
              `domain` varchar(255) NOT NULL DEFAULT '',
              `product` varchar(100) NOT NULL DEFAULT 'kosmozaim',
              `plan` enum('trial','basic','pro','enterprise') NOT NULL DEFAULT 'basic',
              `status` enum('active','suspended','expired','revoked') NOT NULL DEFAULT 'active',
              `owner_name` varchar(255) DEFAULT NULL,
              `owner_email` varchar(255) DEFAULT NULL,
              `issued_at` timestamp NULL DEFAULT current_timestamp(),
              `expires_at` timestamp NULL DEFAULT NULL,
              `last_check_at` timestamp NULL DEFAULT NULL,
              `last_check_ip` varchar(45) DEFAULT NULL,
              `activations_count` int(11) NOT NULL DEFAULT 0,
              `max_activations` int(11) NOT NULL DEFAULT 1,
              `features` text DEFAULT NULL,
              `notes` text DEFAULT NULL,
              `hardware_hash` varchar(64) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_license_key` (`license_key`),
              KEY `idx_domain` (`domain`),
              KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `license_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `license_id` int(11) DEFAULT NULL,
              `license_key` varchar(64) DEFAULT NULL,
              `action` varchar(20) NOT NULL,
              `domain` varchar(255) DEFAULT NULL,
              `ip` varchar(45) DEFAULT NULL,
              `user_agent` varchar(500) DEFAULT NULL,
              `request_data` text DEFAULT NULL,
              `response_code` int(11) DEFAULT NULL,
              `message` varchar(500) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_license` (`license_id`),
              KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `rate_limits` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `ip` varchar(45) NOT NULL,
              `endpoint` varchar(100) NOT NULL,
              `attempts` int(11) NOT NULL DEFAULT 1,
              `window_start` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_ip_endpoint` (`ip`, `endpoint`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(100) NOT NULL,
              `password_hash` varchar(255) NOT NULL,
              `totp_secret` varchar(64) DEFAULT NULL,
              `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uk_username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        
        $steps[] = "Таблицы: licenses, license_log, rate_limits, admins";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Ошибка создания таблиц: ' . $e->getMessage()];
    }
    
    // === 4. Создание админа ===
    try {
        $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
        $check = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
        $check->execute([$adminUser]);
        if ($check->fetch()) {
            $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?")->execute([$hash, $adminUser]);
            $steps[] = "Админ '$adminUser': пароль обновлён";
        } else {
            $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")->execute([$adminUser, $hash]);
            $steps[] = "Админ '$adminUser': создан";
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Ошибка создания админа: ' . $e->getMessage()];
    }
    
    // === 5. Генерация ключей безопасности ===
    $signKey = bin2hex(random_bytes(24));
    $encryptKey = bin2hex(random_bytes(24));
    $salt = bin2hex(random_bytes(16));
    $adminToken = 'lac_' . bin2hex(random_bytes(16));
    
    // === 6. Копирование файлов сервера ===
    if ($sourceDir && $sourceDir !== $baseDir) {
        $filesToCopy = [
            'index.php', '.htaccess',
            'api/activate.php', 'api/verify.php', 'api/deactivate.php', 'api/heartbeat.php',
            'admin/index.php', 'admin/api.php',
        ];
        @mkdir($baseDir . '/api', 0755, true);
        @mkdir($baseDir . '/admin', 0755, true);
        
        foreach ($filesToCopy as $file) {
            $src = $sourceDir . '/' . $file;
            $dst = $baseDir . '/' . $file;
            if (file_exists($src)) {
                copy($src, $dst);
            }
        }
        $steps[] = "Файлы сервера: скопированы";
    } else {
        $steps[] = "Файлы сервера: уже на месте";
    }
    
    // === 7. Генерация config.php с реальными данными ===
    $configContent = <<<PHPCONFIG
<?php
/**
 * Конфигурация сервера лицензирования
 * Сгенерировано установщиком: {$date}
 */

mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

function getDB(): PDO {
    static \$pdo = null;
    if (\$pdo) return \$pdo;
    \$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    \$pdo = new PDO(\$dsn, '{$dbUser}', '{$dbPass}', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    \$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    return \$pdo;
}

define('LICENSE_SIGN_KEY', '{$signKey}');
define('LICENSE_ENCRYPT_KEY', '{$encryptKey}');
define('LICENSE_SALT', '{$salt}');
define('ADMIN_API_TOKEN', '{$adminToken}');

PHPCONFIG;
    
    // Добавляем функции из оригинального config.php
    $configContent .= <<<'PHPFUNCS'

function signResponse(array $data): string {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    return hash_hmac('sha256', $json, LICENSE_SIGN_KEY);
}

function encryptData(string $plaintext): string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function decryptData(string $ciphertext): ?string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $raw = base64_decode($ciphertext);
    if (strlen($raw) < 17) return null;
    $iv = substr($raw, 0, 16);
    $encrypted = substr($raw, 16);
    $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : null;
}

function generateLicenseKey(): string {
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $parts[] = strtoupper(bin2hex(random_bytes(3)));
    }
    return 'KZM-' . implode('-', $parts);
}

function normalizeDomain(string $domain): string {
    $domain = trim(strtolower($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    return rtrim($domain, '/');
}

function getClientIp(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return trim(explode(',', $ip)[0]);
}

function checkRateLimit(string $endpoint, int $maxAttempts = 30, int $windowSec = 60): array {
    $ip = getClientIp();
    $db = getDB();
    $db->prepare("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([$windowSec]);
    $stmt = $db->prepare("SELECT attempts FROM rate_limits WHERE ip = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
    $row = $stmt->fetch();
    if (!$row) {
        $db->prepare("INSERT INTO rate_limits (ip, endpoint, attempts) VALUES (?, ?, 1)")->execute([$ip, $endpoint]);
        return ['allowed' => true, 'remaining' => $maxAttempts - 1];
    }
    if ((int)$row['attempts'] >= $maxAttempts) return ['allowed' => false, 'remaining' => 0];
    $db->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE ip = ? AND endpoint = ?")->execute([$ip, $endpoint]);
    return ['allowed' => true, 'remaining' => $maxAttempts - (int)$row['attempts'] - 1];
}

function logAction(string $action, ?int $licenseId, ?string $licenseKey, ?string $domain, int $responseCode, ?string $message = null): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO license_log (license_id, license_key, action, domain, ip, user_agent, response_code, message) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$licenseId, $licenseKey, $action, $domain, getClientIp(), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500), $responseCode, $message ? mb_substr($message, 0, 500) : null]);
    } catch (Exception $e) {}
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    $data['timestamp'] = time();
    $data['signature'] = signResponse($data);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): void {
    jsonResponse(['error' => $message, 'valid' => false], $code);
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
PHPFUNCS;
    
    $date = date('Y-m-d H:i:s');
    $configContent = str_replace('{$date}', $date, $configContent);
    
    if (file_put_contents($baseDir . '/config.php', $configContent) === false) {
        return ['success' => false, 'error' => 'Не удалось записать config.php'];
    }
    $steps[] = "config.php: сгенерирован с уникальными ключами";
    
    // === 8. Проверка работоспособности ===
    try {
        require_once $baseDir . '/config.php';
        $testDb = getDB();
        $testDb->query("SELECT 1");
        $steps[] = "Проверка БД через config.php: OK";
    } catch (Exception $e) {
        $steps[] = "⚠️ Проверка БД: " . $e->getMessage();
    }
    
    return ['success' => true, 'steps' => $steps];
}

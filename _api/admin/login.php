<?php
/**
 * Авторизация администратора с rate-limit и 2FA
 */
require_once __DIR__ . '/../../includes/totp.php';
require_once __DIR__ . '/../../includes/audit-log.php';

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$totpCode = trim($data['totp_code'] ?? '');
$backupCode = trim($data['backup_code'] ?? '');
$ip = getClientIp();

// === ПРОГРЕССИВНЫЙ RATE-LIMIT ===
$db = getDB();

// Считаем неудачные попытки за разные периоды
$fails5min = 0;
$fails15min = 0;
$fails1h = 0;
try {
    $stmt = $db->prepare("SELECT 
        SUM(success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)) as f5,
        SUM(success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)) as f15,
        SUM(success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)) as f1h
        FROM admin_login_log WHERE ip = ?");
    $stmt->execute([$ip]);
    $row = $stmt->fetch();
    $fails5min = (int)($row['f5'] ?? 0);
    $fails15min = (int)($row['f15'] ?? 0);
    $fails1h = (int)($row['f1h'] ?? 0);
} catch (Exception $e) {}

// Прогрессивная блокировка
if ($fails5min >= 5) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много попыток. Подождите 5 минут.', 'blocked' => true, 'wait' => 300]);
    exit;
}
if ($fails15min >= 10) {
    http_response_code(429);
    echo json_encode(['error' => 'IP временно заблокирован. Подождите 15 минут.', 'blocked' => true, 'wait' => 900]);
    exit;
}
if ($fails1h >= 20) {
    http_response_code(429);
    echo json_encode(['error' => 'IP заблокирован на 1 час.', 'blocked' => true, 'wait' => 3600]);
    exit;
}

// Проверка IP whitelist
if (!checkIpWhitelist($ip)) {
    http_response_code(403);
    logLoginAttempt($username, $ip, false);
    echo json_encode(['error' => 'Доступ с вашего IP запрещён']);
    exit;
}

// Seed admin if not exists
$check = $db->query("SELECT COUNT(*) as cnt FROM admin_users")->fetch();
if ($check['cnt'] == 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")->execute(['admin', $hash]);
}

// Проверка логина/пароля
$stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    logLoginAttempt($username, $ip, false);
    $remaining = max(0, 5 - $fails5min - 1);
    http_response_code(401);
    echo json_encode(['error' => "Неверные данные. Осталось попыток: {$remaining}"]);
    exit;
}

// === ПРОВЕРКА 2FA ===
$totp_enabled = false;
try {
    $totp_enabled = !empty($user['totp_enabled']) && !empty($user['totp_secret']);
} catch (Exception $e) {}

if ($totp_enabled) {
    // Если код не передан — требуем ввести
    if (!$totpCode && !$backupCode) {
        echo json_encode([
            'success' => false,
            'require_2fa' => true,
            'requires_2fa' => true,
            'message' => 'Введите код из приложения-аутентификатора'
        ]);
        exit;
    }
    
    $verified = false;
    
    // Проверяем TOTP-код
    if ($totpCode) {
        $verified = TOTP::verify($user['totp_secret'], $totpCode);
    }
    
    // Проверяем резервный код
    if (!$verified && $backupCode) {
        $backupCodes = json_decode($user['totp_backup_codes'] ?? '[]', true) ?: [];
        if (TOTP::verifyBackupCode($backupCode, $backupCodes)) {
            $verified = true;
            // Обновляем список резервных кодов (использованный удалён)
            $db->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?")
               ->execute([json_encode($backupCodes), $user['id']]);
        }
    }
    
    if (!$verified) {
        logLoginAttempt($username, $ip, false);
        http_response_code(401);
        echo json_encode(['error' => 'Неверный код 2FA', 'require_2fa' => true, 'requires_2fa' => true]);
        exit;
    }
}

// === УСПЕШНЫЙ ВХОД ===
logLoginAttempt($username, $ip, true);

startAdminSession();
session_regenerate_id(true);
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_user'] = $user['username'];
$_SESSION['admin_username'] = $user['username'];
$_SESSION['admin_name'] = $user['username'];
$_SESSION['admin_ip'] = $ip;
$_SESSION['admin_login_time'] = time();

auditLog('login', 'admin', (int)$user['id'], $user['username']);

echo json_encode(['success' => true]);

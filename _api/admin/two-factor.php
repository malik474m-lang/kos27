<?php
/**
 * Управление двухфакторной авторизацией
 * GET  /api/admin/two-factor — статус 2FA
 * POST /api/admin/two-factor — действия: setup, enable, disable
 */
require_once __DIR__ . '/../../includes/totp.php';
require_once __DIR__ . '/../../includes/audit-log.php';

header('Content-Type: application/json; charset=UTF-8');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$adminId = $_SESSION['admin_id'] ?? 0;

// Проверяем что колонки 2FA существуют
try {
    $db->query("SELECT totp_enabled FROM admin_users LIMIT 1");
} catch (Exception $e) {
    // Колонок нет — создаём
    try {
        $db->exec("ALTER TABLE admin_users ADD COLUMN totp_secret varchar(64) DEFAULT NULL");
        $db->exec("ALTER TABLE admin_users ADD COLUMN totp_enabled tinyint(1) NOT NULL DEFAULT 0");
        $db->exec("ALTER TABLE admin_users ADD COLUMN totp_backup_codes text DEFAULT NULL");
    } catch (Exception $e2) {}
}

$user = $db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
$user->execute([$adminId]);
$admin = $user->fetch();

if (!$admin) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован']);
    exit;
}

// === GET: статус 2FA ===
if ($method === 'GET') {
    $enabled = !empty($admin['totp_enabled']);
    $backupCodes = json_decode($admin['totp_backup_codes'] ?? '[]', true) ?: [];
    echo json_encode([
        'enabled' => $enabled,
        'backup_codes_remaining' => count($backupCodes),
    ]);
    exit;
}

// === POST: действия ===
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// --- Шаг 1: Создать секрет и показать QR ---
if ($action === 'setup') {
    $secret = TOTP::generateSecret();
    $label = $admin['username'] ?? 'admin';
    $otpauthUrl = TOTP::getQrUrl($secret, $label);
    $qrImageUrl = TOTP::getQrImageUrl($otpauthUrl, 250);
    
    // Сохраняем секрет (ещё не активирован)
    $db->prepare("UPDATE admin_users SET totp_secret = ? WHERE id = ?")
       ->execute([$secret, $adminId]);
    
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qrImageUrl,
        'otpauth_url' => $otpauthUrl,
        'message' => 'Отсканируйте QR-код в Google Authenticator / Яндекс.Ключ',
    ]);
    exit;
}

// --- Шаг 2: Подтвердить и активировать ---
if ($action === 'enable') {
    $code = trim($data['code'] ?? '');
    $secret = $admin['totp_secret'] ?? '';
    
    if (!$secret) {
        echo json_encode(['error' => 'Сначала выполните setup']);
        exit;
    }
    
    if (!TOTP::verify($secret, $code)) {
        echo json_encode(['error' => 'Неверный код. Проверьте время на телефоне.']);
        exit;
    }
    
    // Генерируем резервные коды
    $backupCodes = TOTP::generateBackupCodes(10);
    
    $db->prepare("UPDATE admin_users SET totp_enabled = 1, totp_backup_codes = ? WHERE id = ?")
       ->execute([json_encode($backupCodes), $adminId]);
    
    auditLog('enable', 'admin', $adminId, '2FA включена');
    
    echo json_encode([
        'success' => true,
        'message' => '2FA успешно включена!',
        'backup_codes' => $backupCodes,
    ]);
    exit;
}

// --- Отключить 2FA ---
if ($action === 'disable') {
    $db->prepare("UPDATE admin_users SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = ?")
       ->execute([$adminId]);
    
    auditLog('disable', 'admin', $adminId, '2FA отключена');
    
    echo json_encode(['success' => true, 'message' => '2FA отключена']);
    exit;
}

// --- Перегенерировать резервные коды ---
if ($action === 'regenerate-backup') {
    $backupCodes = TOTP::generateBackupCodes(10);
    $db->prepare("UPDATE admin_users SET totp_backup_codes = ? WHERE id = ?")
       ->execute([json_encode($backupCodes), $adminId]);
    
    echo json_encode([
        'success' => true,
        'backup_codes' => $backupCodes,
        'message' => 'Новые резервные коды сгенерированы',
    ]);
    exit;
}

echo json_encode(['error' => 'Неизвестное действие']);

<?php
/**
 * API админки сервера лицензий
 * Бэкап БД, Rate-limit, 2FA
 */
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../totp.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// === ЛОГИН (без авторизации) ===
if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $totpCode = trim($data['totp_code'] ?? '');
    $backupCode = trim($data['backup_code'] ?? '');
    $ip = getClientIp();

    // Rate-limit: 5 попыток за 5 мин
    $rate = checkRateLimit('admin_login', 5, 300);
    if (!$rate['allowed']) {
        http_response_code(429);
        logAction('denied', null, null, null, 429, "Login rate limit: $ip");
        echo json_encode(['error' => 'Слишком много попыток. Подождите 5 минут.', 'blocked' => true]);
        exit;
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        logAction('denied', null, null, null, 401, "Failed login: $username from $ip");
        http_response_code(401);
        echo json_encode(['error' => 'Неверные данные. Осталось попыток: ' . max(0, $rate['remaining'] - 1)]);
        exit;
    }

    // 2FA проверка
    $tfa = !empty($admin['totp_enabled']) && !empty($admin['totp_secret']);
    if ($tfa) {
        if (!$totpCode && !$backupCode) {
            echo json_encode(['success' => false, 'require_2fa' => true]);
            exit;
        }
        $ok2fa = false;
        if ($totpCode) $ok2fa = TOTP::verify($admin['totp_secret'], $totpCode);
        if (!$ok2fa && $backupCode) {
            $codes = json_decode($admin['totp_backup_codes'] ?? '[]', true) ?: [];
            if (TOTP::verifyBackupCode($backupCode, $codes)) {
                $ok2fa = true;
                $db->prepare("UPDATE admins SET totp_backup_codes = ? WHERE id = ?")
                   ->execute([json_encode($codes), $admin['id']]);
            }
        }
        if (!$ok2fa) {
            http_response_code(401);
            echo json_encode(['error' => 'Неверный код 2FA', 'require_2fa' => true]);
            exit;
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['lic_admin_id'] = $admin['id'];
    $_SESSION['lic_admin_user'] = $admin['username'];
    $_SESSION['lic_admin_ip'] = $ip;

    logAction('activate', null, null, null, 200, "Admin login: $username from $ip");
    echo json_encode(['success' => true]);
    exit;
}

// === ЛОГАУТ ===
if ($action === 'logout' && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// === Проверка авторизации для остальных ===
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_GET['token'] ?? '';
if ($token !== ADMIN_API_TOKEN) {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    if (empty($_SESSION['lic_admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    // Привязка сессии к IP
    if (isset($_SESSION['lic_admin_ip']) && $_SESSION['lic_admin_ip'] !== getClientIp()) {
        session_destroy();
        http_response_code(401);
        echo json_encode(['error' => 'Сессия привязана к другому IP']);
        exit;
    }
}

// === Список лицензий ===
if ($action === 'list') {
    $licenses = $db->query("SELECT l.*, 
        (SELECT COUNT(*) FROM license_log ll WHERE ll.license_id = l.id AND ll.action = 'verify' AND ll.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as checks_24h
        FROM licenses l ORDER BY l.id DESC")->fetchAll();
    echo json_encode(['licenses' => $licenses]);
    exit;
}

// === Создать лицензию ===
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $key = generateLicenseKey();
    $domain = normalizeDomain($data['domain'] ?? '');
    $plan = in_array($data['plan'] ?? '', ['trial','basic','pro','enterprise']) ? $data['plan'] : 'basic';
    $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
    $features = $data['features'] ?? null;
    if ($features && is_array($features)) $features = json_encode($features);
    $db->prepare("INSERT INTO licenses (license_key, domain, product, plan, status, owner_name, owner_email, expires_at, features, notes, max_activations) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$key, $domain, $data['product'] ?? 'kosmozaim', $plan, 'active',
           $data['owner_name'] ?? null, $data['owner_email'] ?? null,
           $expiresAt, $features, $data['notes'] ?? null, (int)($data['max_activations'] ?? 1)]);
    echo json_encode(['success' => true, 'license_key' => $key, 'id' => $db->lastInsertId()]);
    exit;
}

// === Обновить лицензию ===
if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id required']); exit; }
    $sets = []; $params = [];
    foreach (['domain','plan','status','owner_name','owner_email','expires_at','notes','max_activations'] as $f) {
        if (array_key_exists($f, $data)) {
            if ($f === 'domain') $data[$f] = normalizeDomain($data[$f]);
            $sets[] = "`$f` = ?"; $params[] = $data[$f];
        }
    }
    if (!empty($data['features'])) {
        $sets[] = "`features` = ?";
        $params[] = is_array($data['features']) ? json_encode($data['features']) : $data['features'];
    }
    if ($sets) { $params[] = $id; $db->prepare("UPDATE licenses SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params); }
    echo json_encode(['success' => true]);
    exit;
}

// === Удалить лицензию ===
if ($action === 'delete' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM licenses WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM license_log WHERE license_id = ?")->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// === Логи ===
if ($action === 'logs') {
    $licId = (int)($_GET['license_id'] ?? 0);
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $where = $licId ? "WHERE license_id = $licId" : '';
    $logs = $db->query("SELECT * FROM license_log $where ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    echo json_encode(['logs' => $logs]);
    exit;
}

// === Статистика ===
if ($action === 'stats') {
    echo json_encode([
        'total' => (int)$db->query("SELECT COUNT(*) FROM licenses")->fetchColumn(),
        'active' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'active'")->fetchColumn(),
        'expired' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'expired'")->fetchColumn(),
        'suspended' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'suspended'")->fetchColumn(),
        'checks_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action IN ('verify','heartbeat') AND created_at > CURDATE()")->fetchColumn(),
        'activations_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = 'activate' AND created_at > CURDATE()")->fetchColumn(),
        'denied_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = 'denied' AND created_at > CURDATE()")->fetchColumn(),
    ]);
    exit;
}

// === Смена пароля ===
if ($action === 'change-password' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $adminId = $_SESSION['lic_admin_id'] ?? 0;
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?"); $stmt->execute([$adminId]); $adm = $stmt->fetch();
    if (!$adm || !password_verify($data['current_password'] ?? '', $adm['password_hash'])) {
        echo json_encode(['error' => 'Неверный текущий пароль']); exit;
    }
    if (mb_strlen($data['new_password'] ?? '') < 6) { echo json_encode(['error' => 'Минимум 6 символов']); exit; }
    $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")
       ->execute([password_hash($data['new_password'], PASSWORD_BCRYPT, ['cost' => 12]), $adminId]);
    echo json_encode(['success' => true, 'message' => 'Пароль изменён']);
    exit;
}

// === 2FA ===
if ($action === '2fa-status') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $adm = $db->prepare("SELECT totp_enabled, totp_backup_codes FROM admins WHERE id = ?");
    $adm->execute([$_SESSION['lic_admin_id'] ?? 0]); $a = $adm->fetch();
    $codes = json_decode($a['totp_backup_codes'] ?? '[]', true) ?: [];
    echo json_encode(['enabled' => !empty($a['totp_enabled']), 'backup_codes_remaining' => count($codes)]);
    exit;
}
if ($action === '2fa' && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $data = json_decode(file_get_contents('php://input'), true);
    $act = $data['action'] ?? '';
    $adminId = $_SESSION['lic_admin_id'] ?? 0;
    $adm = $db->prepare("SELECT * FROM admins WHERE id = ?"); $adm->execute([$adminId]); $admin = $adm->fetch();
    if (!$admin) { echo json_encode(['error' => 'Not found']); exit; }

    if ($act === 'setup') {
        $secret = TOTP::generateSecret();
        $url = TOTP::getQrUrl($secret, $admin['username'], 'KZM License');
        $qr = TOTP::getQrImageUrl($url, 250);
        $db->prepare("UPDATE admins SET totp_secret = ? WHERE id = ?")->execute([$secret, $adminId]);
        echo json_encode(['success' => true, 'secret' => $secret, 'qr_url' => $qr]);
        exit;
    }
    if ($act === 'enable') {
        $code = trim($data['code'] ?? '');
        if (!TOTP::verify($admin['totp_secret'] ?? '', $code)) {
            echo json_encode(['error' => 'Неверный код']); exit;
        }
        $backup = TOTP::generateBackupCodes(10);
        $db->prepare("UPDATE admins SET totp_enabled = 1, totp_backup_codes = ? WHERE id = ?")
           ->execute([json_encode($backup), $adminId]);
        echo json_encode(['success' => true, 'backup_codes' => $backup]);
        exit;
    }
    if ($act === 'disable') {
        if (!password_verify($data['password'] ?? '', $admin['password_hash'])) {
            echo json_encode(['error' => 'Неверный пароль']); exit;
        }
        $db->prepare("UPDATE admins SET totp_enabled = 0, totp_secret = NULL, totp_backup_codes = NULL WHERE id = ?")
           ->execute([$adminId]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($act === 'regen-backup') {
        if (!password_verify($data['password'] ?? '', $admin['password_hash'])) {
            echo json_encode(['error' => 'Неверный пароль']); exit;
        }
        $backup = TOTP::generateBackupCodes(10);
        $db->prepare("UPDATE admins SET totp_backup_codes = ? WHERE id = ?")->execute([json_encode($backup), $adminId]);
        echo json_encode(['success' => true, 'backup_codes' => $backup]);
        exit;
    }
    echo json_encode(['error' => 'Unknown 2fa action']);
    exit;
}

// === Бэкап БД ===
if ($action === 'backup-create' && $method === 'POST') {
    $backupDir = __DIR__ . '/../backups';
    if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);

    // Получаем параметры БД из config
    $ref = new ReflectionFunction('getDB');
    $src = file_get_contents($ref->getFileName());
    preg_match('/dbname=([^;]+)/', $src, $m); $dbName = $m[1] ?? 'license_server';

    $ts = date('Y-m-d_H-i-s');
    $sqlFile = "$backupDir/backup_$ts.sql";

    // Экспорт через PDO
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $dump = "-- KZM License Server Backup\n-- Date: $ts\n-- Tables: " . count($tables) . "\n\n";
    foreach ($tables as $tbl) {
        $create = $db->query("SHOW CREATE TABLE `$tbl`")->fetch();
        $dump .= "DROP TABLE IF EXISTS `$tbl`;\n" . $create['Create Table'] . ";\n\n";
        $rows = $db->query("SELECT * FROM `$tbl`")->fetchAll();
        foreach ($rows as $row) {
            $vals = array_map(function($v) use ($db) {
                return $v === null ? 'NULL' : $db->quote($v);
            }, $row);
            $dump .= "INSERT INTO `$tbl` VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\n";
    }

    if (file_put_contents($sqlFile, $dump) === false) {
        echo json_encode(['error' => 'Не удалось записать файл']); exit;
    }

    // ZIP
    $zipFile = "$backupDir/backup_$ts.zip";
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            @unlink($sqlFile);
            $size = round(filesize($zipFile) / 1024, 1);
            echo json_encode(['success' => true, 'file' => basename($zipFile), 'size' => "$size KB"]);
            exit;
        }
    }
    $size = round(filesize($sqlFile) / 1024, 1);
    echo json_encode(['success' => true, 'file' => basename($sqlFile), 'size' => "$size KB"]);
    exit;
}

if ($action === 'backup-list') {
    $backupDir = __DIR__ . '/../backups';
    $files = [];
    if (is_dir($backupDir)) {
        foreach (glob("$backupDir/backup_*") as $f) {
            $files[] = ['name' => basename($f), 'size' => round(filesize($f) / 1024, 1) . ' KB', 'date' => date('Y-m-d H:i', filemtime($f))];
        }
    }
    usort($files, fn($a, $b) => strcmp($b['date'], $a['date']));
    echo json_encode(['backups' => $files]);
    exit;
}

if ($action === 'backup-download') {
    $name = basename($_GET['name'] ?? '');
    $path = __DIR__ . '/../backups/' . $name;
    if (!$name || !file_exists($path)) { echo json_encode(['error' => 'Not found']); exit; }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

if ($action === 'backup-delete' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = basename($data['name'] ?? '');
    $path = __DIR__ . '/../backups/' . $name;
    if ($name && file_exists($path)) @unlink($path);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

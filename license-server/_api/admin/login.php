<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$totp = trim($input['totp_code'] ?? '');
$backup = trim($input['backup_code'] ?? '');
$ip = getClientIp();

$block = isIpBlocked($ip);
if ($block['blocked']) jsonResponse(['success' => false, 'blocked' => true, 'remaining' => $block['remaining']], 429);
if (!$username || !$password) jsonResponse(['success' => false, 'error' => 'Credentials required'], 400);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        logLoginAttempt($username, $ip, false);
        jsonResponse(['success' => false, 'error' => 'Invalid credentials'], 401);
    }
    
    if ($admin['totp_enabled']) {
        if (!$totp && !$backup) jsonResponse(['success' => false, 'requires_2fa' => true]);
        $ok = false;
        if ($totp) $ok = verifyTotp($admin['totp_secret'], $totp);
        elseif ($backup) {
            $codes = json_decode($admin['backup_codes'] ?: '[]', true);
            $key = array_search(strtoupper($backup), $codes);
            if ($key !== false) { $ok = true; unset($codes[$key]); $db->prepare("UPDATE admins SET backup_codes=? WHERE id=?")->execute([json_encode(array_values($codes)), $admin['id']]); }
        }
        if (!$ok) { logLoginAttempt($username, $ip, false); jsonResponse(['success' => false, 'error' => 'Invalid 2FA'], 401); }
    }
    
    logLoginAttempt($username, $ip, true);
    $db->prepare("UPDATE admins SET last_login=NOW(), last_ip=? WHERE id=?")->execute([$ip, $admin['id']]);
    startSession();
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_ip'] = $ip;
    $_SESSION['admin_login_time'] = time();
    auditLog('login', 'admin', $admin['id']);
    jsonResponse(['success' => true, 'admin' => ['id' => $admin['id'], 'username' => $admin['username']]]);
} catch (Exception $e) { jsonResponse(['success' => false, 'error' => 'Error'], 500); }

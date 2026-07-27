<?php
$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$ip = getClientIp();

// Проверка блокировки IP
if (isIpBlocked($ip)) {
    http_response_code(429);
    echo json_encode(['error' => 'Слишком много попыток. Подождите 15 минут.']);
    exit;
}

// Проверка IP whitelist
if (!checkIpWhitelist($ip)) {
    http_response_code(403);
    logLoginAttempt($username, $ip, false);
    echo json_encode(['error' => 'Доступ с вашего IP запрещён']);
    exit;
}

$db = getDB();

// Seed admin if not exists
$check = $db->query("SELECT COUNT(*) as cnt FROM admin_users")->fetch();
if ($check['cnt'] == 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")->execute(['admin', $hash]);
}

$stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    logLoginAttempt($username, $ip, false);

    // Считаем оставшиеся попытки
    $failStmt = $db->prepare("SELECT COUNT(*) as cnt FROM admin_login_log WHERE ip = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $failStmt->execute([$ip]);
    $fails = (int)$failStmt->fetch()['cnt'];
    $remaining = max(0, 10 - $fails);

    http_response_code(401);
    echo json_encode(['error' => "Неверные данные. Осталось попыток: {$remaining}"]);
    exit;
}

// Успешный вход
logLoginAttempt($username, $ip, true);

startAdminSession();
session_regenerate_id(true); // Защита от session fixation
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_user'] = $user['username'];
$_SESSION['admin_ip'] = $ip;
$_SESSION['admin_login_time'] = time();

echo json_encode(['success' => true]);

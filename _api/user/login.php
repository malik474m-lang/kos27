<?php
require_once __DIR__ . '/../../includes/user-auth.php';
header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';

if (!$email || !$password) { http_response_code(400); echo json_encode(['error' => 'Заполните все поля']); exit; }

$db = getDB();
$ip = getClientIp();
$user = $db->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 1 LIMIT 1");
$user->execute([$email]);
$row = $user->fetch();

if (!$row || !password_verify($password, $row['password_hash'])) {
    $db->prepare("INSERT INTO user_login_log (user_id, email, ip, success) VALUES (?,?,?,0)")->execute([$row['id'] ?? null, $email, $ip]);
    http_response_code(401); echo json_encode(['error' => 'Неверный email или пароль']); exit;
}

$db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")->execute([$ip, $row['id']]);
$db->prepare("INSERT INTO user_login_log (user_id, email, ip, success) VALUES (?,?,?,1)")->execute([$row['id'], $email, $ip]);
setUser($row);
echo json_encode(['success' => true, 'name' => $row['name'] ?: $row['email']]);

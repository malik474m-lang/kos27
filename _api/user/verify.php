<?php
require_once __DIR__ . '/../../includes/user-auth.php';
header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$code = trim($data['code'] ?? '');

if (!$email || !$code) { http_response_code(400); echo json_encode(['error' => 'Укажите email и код']); exit; }

$db = getDB();
$user = $db->prepare("SELECT * FROM users WHERE email = ? AND verify_code = ? AND verify_code_expires > NOW() LIMIT 1");
$user->execute([$email, $code]);
$row = $user->fetch();

if (!$row) { http_response_code(400); echo json_encode(['error' => 'Неверный или просроченный код']); exit; }

$db->prepare("UPDATE users SET is_verified = 1, verify_code = NULL WHERE id = ?")->execute([$row['id']]);
$ip = getClientIp();
$db->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?")->execute([$ip, $row['id']]);
$db->prepare("INSERT INTO user_login_log (user_id, email, ip, success) VALUES (?,?,?,1)")->execute([$row['id'], $email, $ip]);
setUser($row);
echo json_encode(['success' => true]);

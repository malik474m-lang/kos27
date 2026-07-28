<?php
require_once __DIR__ . '/../../includes/user-auth.php';
header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = $data['password'] ?? '';
$name = trim($data['name'] ?? '');
$agreedTerms = !empty($data['agreedTerms']);
$agreedMarketing = !empty($data['agreedMarketing']);
$agreedFinance = !empty($data['agreedFinance']);

if (!$email) { http_response_code(400); echo json_encode(['error' => 'Некорректный email']); exit; }
if (mb_strlen($password) < 6) { http_response_code(400); echo json_encode(['error' => 'Пароль минимум 6 символов']); exit; }
if (!$agreedTerms) { http_response_code(400); echo json_encode(['error' => 'Необходимо принять условия']); exit; }
if (!$agreedFinance) { http_response_code(400); echo json_encode(['error' => 'Необходимо принять правила финансовой платформы']); exit; }

$db = getDB();
$exists = $db->prepare("SELECT id, is_verified FROM users WHERE email = ?"); $exists->execute([$email]);
$row = $exists->fetch();
if ($row && $row['is_verified']) { http_response_code(400); echo json_encode(['error' => 'Email уже зарегистрирован']); exit; }

$hash = password_hash($password, PASSWORD_BCRYPT);
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$codeExpires = date('Y-m-d H:i:s', time() + 900);

if ($row) {
    $db->prepare("UPDATE users SET password_hash=?, name=?, verify_code=?, verify_code_expires=?, agreed_terms=?, agreed_marketing=?, agreed_finance=? WHERE id=?")
       ->execute([$hash, $name, $code, $codeExpires, 1, $agreedMarketing ? 1 : 0, 1, $row['id']]);
} else {
    $db->prepare("INSERT INTO users (email, password_hash, name, verify_code, verify_code_expires, agreed_terms, agreed_marketing, agreed_finance) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$email, $hash, $name, $code, $codeExpires, 1, $agreedMarketing ? 1 : 0, 1]);
}

sendVerifyCode($email, $code);
echo json_encode(['success' => true, 'message' => 'Код отправлен на ' . $email]);

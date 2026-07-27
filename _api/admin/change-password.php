<?php
$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['currentPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';

if (!$currentPassword || !$newPassword) {
    http_response_code(400);
    echo json_encode(['error' => 'Все поля обязательны']);
    exit;
}

if (mb_strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Новый пароль должен быть не менее 6 символов']);
    exit;
}

startAdminSession();
$adminId = $_SESSION['admin_id'] ?? 0;

$db = getDB();
$stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Текущий пароль неверный']);
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$newHash, $adminId]);

echo json_encode(['success' => true, 'message' => 'Пароль успешно изменён']);

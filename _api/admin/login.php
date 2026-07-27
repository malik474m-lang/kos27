<?php
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

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
    http_response_code(401);
    echo json_encode(['error' => 'Неверные данные']);
    exit;
}

startAdminSession();
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_user'] = $user['username'];

echo json_encode(['success' => true]);

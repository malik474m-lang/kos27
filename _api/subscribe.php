<?php
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный email']);
    exit;
}

$db = getDB();
$check = $db->prepare("SELECT id FROM subscribers WHERE email = ?");
$check->execute([$email]);
if ($check->fetch()) {
    echo json_encode(['success' => true, 'message' => 'Вы уже подписаны']);
    exit;
}

$db->prepare("INSERT INTO subscribers (email) VALUES (?)")->execute([$email]);
echo json_encode(['success' => true]);

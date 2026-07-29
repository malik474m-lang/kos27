<?php
$data = json_decode(file_get_contents('php://input'), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$source = trim($data['source'] ?? 'footer');

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

$token = md5($email . time() . mt_rand());

// Проверяем есть ли колонка source
try {
    $db->prepare("INSERT INTO subscribers (email, unsubscribe_token, source) VALUES (?, ?, ?)")->execute([$email, $token, $source]);
} catch (Exception $e) {
    // Если колонки source нет — вставляем без неё
    $db->prepare("INSERT INTO subscribers (email, unsubscribe_token) VALUES (?, ?)")->execute([$email, $token]);
}

echo json_encode(['success' => true, 'message' => 'Подписка оформлена! Проверьте почту.']);

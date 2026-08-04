<?php
requireAdmin();
require_once __DIR__ . '/../../includes/article-image.php';
header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$provider = trim((string)($data['provider'] ?? ''));
$title = trim((string)($data['title'] ?? 'тест картинки'));
if (!in_array($provider, ['yandex','stability','gigachat'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown provider']);
    exit;
}
$result = testArticleImageProvider($provider, $title);
echo json_encode($result);

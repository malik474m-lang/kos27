<?php
$data = json_decode(file_get_contents('php://input'), true);
$offerId = (int)($data['offerId'] ?? 0);
$authorName = trim($data['authorName'] ?? '');
$rating = max(1, min(5, (int)($data['rating'] ?? 5)));
$comment = trim($data['comment'] ?? '');

if (!$offerId || !$authorName || !$comment) {
    http_response_code(400);
    echo json_encode(['error' => 'Все поля обязательны']);
    exit;
}

$db = getDB();
$reviewTextColumn = function_exists('dbFirstExistingColumn') ? dbFirstExistingColumn('reviews', ['comment', 'text']) : 'comment';
$db->prepare("INSERT INTO reviews (offer_id, author_name, rating, {$reviewTextColumn}, is_approved) VALUES (?, ?, ?, ?, 0)")
   ->execute([$offerId, $authorName, $rating, $comment]);
echo json_encode(['success' => true, 'message' => 'Отзыв отправлен на модерацию']);

<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("UPDATE reviews SET is_approved = ? WHERE id = ?")->execute([$data['isApproved'] ?? false, $itemId]);

// Пересчитать рейтинг оффера
$review = $db->prepare("SELECT offer_id FROM reviews WHERE id = ?");
$review->execute([$itemId]);
$rev = $review->fetch();
if ($rev) {
    $db->prepare("UPDATE offers SET rating = (SELECT COALESCE(ROUND(AVG(r.rating),1),0) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1), review_count = (SELECT COUNT(*) FROM reviews r WHERE r.offer_id = ? AND r.is_approved = 1) WHERE id = ?")->execute([$rev['offer_id'], $rev['offer_id'], $rev['offer_id']]);
}

echo json_encode(['success' => true]);

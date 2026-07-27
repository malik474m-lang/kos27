<?php
// Сохранить привязки: {offerId: 1, tagIds: [2,3]} или {tagId: 1, offerIds: [2,3]}
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

if (isset($data['offerId'])) {
    // Сохраняем теги для оффера
    $offerId = (int)$data['offerId'];
    $tagIds = array_map('intval', $data['tagIds'] ?? []);
    $db->prepare("DELETE FROM offer_tag_links WHERE offer_id = ?")->execute([$offerId]);
    $stmt = $db->prepare("INSERT INTO offer_tag_links (offer_id, tag_id) VALUES (?, ?)");
    foreach ($tagIds as $tid) { $stmt->execute([$offerId, $tid]); }
    echo json_encode(['success' => true]);
} elseif (isset($data['tagId'])) {
    // Сохраняем офферы для тега
    $tagId = (int)$data['tagId'];
    $offerIds = array_map('intval', $data['offerIds'] ?? []);
    $db->prepare("DELETE FROM offer_tag_links WHERE tag_id = ?")->execute([$tagId]);
    $stmt = $db->prepare("INSERT INTO offer_tag_links (offer_id, tag_id) VALUES (?, ?)");
    foreach ($offerIds as $oid) { $stmt->execute([$oid, $tagId]); }
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'offerId или tagId обязателен']);
}

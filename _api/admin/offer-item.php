<?php
header('Content-Type: application/json; charset=UTF-8');
$db = getDB();
$itemId = (int)($_GET['id'] ?? $itemId ?? 0);
if ($itemId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid offer id']);
    exit;
}
$stmt = $db->prepare("SELECT * FROM offers WHERE id = ? LIMIT 1");
$stmt->execute([$itemId]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Offer not found']);
    exit;
}
echo json_encode($row);

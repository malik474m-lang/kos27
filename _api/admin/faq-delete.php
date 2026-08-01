<?php
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'id required']); exit; }
$db = getDB();
$db->prepare("DELETE FROM offer_faqs WHERE id = ?")->execute([$id]);
echo json_encode(['success' => true]);

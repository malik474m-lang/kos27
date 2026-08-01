<?php
$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'id required']); exit; }
$db = getDB();
$db->prepare("UPDATE offer_faqs SET question=?, answer=?, is_active=? WHERE id=?")
   ->execute([$data['question'] ?? '', $data['answer'] ?? '', $data['is_active'] ?? 1, $id]);
echo json_encode(['success' => true]);

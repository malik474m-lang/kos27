<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$table = $data['table'] ?? '';
$ids = $data['ids'] ?? [];

$allowed = ['offers', 'offer_tags', 'articles'];
if (!in_array($table, $allowed) || !is_array($ids) || !$ids) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверные параметры']);
    exit;
}

$stmt = $db->prepare("UPDATE `{$table}` SET sort_order = ? WHERE id = ?");
foreach ($ids as $i => $id) {
    $stmt->execute([$i, (int)$id]);
}

echo json_encode(['success' => true, 'updated' => count($ids)]);

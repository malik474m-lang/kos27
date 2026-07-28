<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$items = $data['items'] ?? [];
if (!is_array($items) || !$items) {
    http_response_code(400);
    echo json_encode(['error' => 'Нет элементов для сортировки']);
    exit;
}
$stmt = $db->prepare("UPDATE categories SET sort_order = ?, parent_id = ? WHERE id = ?");
foreach ($items as $i => $item) {
    $id = (int)($item['id'] ?? 0);
    $parentId = isset($item['parent_id']) && $item['parent_id'] !== '' ? (int)$item['parent_id'] : null;
    if ($id > 0) $stmt->execute([$i, $parentId, $id]);
}
echo json_encode(['success' => true, 'updated' => count($items)]);

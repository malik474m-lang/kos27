<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$items = $data['items'] ?? [];
if (!is_array($items) || !$items) {
    http_response_code(400);
    echo json_encode(['error' => 'Нет элементов для сортировки']);
    exit;
}
$stmt = $db->prepare("UPDATE categories SET sort_order = ?, parent_id = ?, footer_section = ? WHERE id = ?");
foreach ($items as $i => $item) {
    $id = (int)($item['id'] ?? 0);
    $parentId = isset($item['parent_id']) && $item['parent_id'] !== '' ? (int)$item['parent_id'] : null;
    $footerSection = $item['footer_section'] ?? 'products';
    $sortOrder = isset($item['sort_hint']) ? (int)$item['sort_hint'] : $i;
    if ($id > 0) $stmt->execute([$sortOrder, $parentId, $footerSection, $id]);
}
echo json_encode(['success' => true, 'updated' => count($items)]);

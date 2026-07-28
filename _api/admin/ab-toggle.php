<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
// Деактивируем все тесты если активируем новый
if (!empty($data['isActive'])) {
    $db->exec("UPDATE ab_tests SET is_active = 0");
}
$db->prepare("UPDATE ab_tests SET is_active = ? WHERE id = ?")->execute([$data['isActive'] ? 1 : 0, $itemId]);
echo json_encode(['success' => true]);

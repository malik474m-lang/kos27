<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("UPDATE subscribers SET is_active = ? WHERE id = ?")->execute([$data['isActive'] ? 1 : 0, $itemId]);
echo json_encode(['success' => true]);

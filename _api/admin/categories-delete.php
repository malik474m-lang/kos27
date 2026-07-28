<?php
$db = getDB();
$db->prepare("DELETE FROM categories WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

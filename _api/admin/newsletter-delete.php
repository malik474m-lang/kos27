<?php
$db = getDB();
$db->prepare("DELETE FROM newsletters WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

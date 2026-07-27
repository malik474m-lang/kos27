<?php
$db = getDB();
$db->prepare("DELETE FROM offer_tags WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

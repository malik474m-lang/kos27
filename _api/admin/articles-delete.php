<?php
$db = getDB();
$db->prepare("DELETE FROM articles WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

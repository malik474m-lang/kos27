<?php
$db = getDB();
$db->prepare("DELETE FROM subscribers WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

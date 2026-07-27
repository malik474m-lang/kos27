<?php
$db = getDB();
$db->prepare("DELETE FROM offers WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

<?php
$db = getDB();
$db->prepare("DELETE FROM geo_redirects WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

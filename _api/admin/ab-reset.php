<?php
$db = getDB();
$db->prepare("UPDATE ab_variants SET impressions = 0, clicks = 0 WHERE test_id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

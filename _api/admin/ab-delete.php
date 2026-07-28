<?php
$db = getDB();
$db->prepare("DELETE FROM ab_variants WHERE test_id = ?")->execute([$itemId]);
$db->prepare("DELETE FROM ab_tests WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

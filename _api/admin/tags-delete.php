<?php
require_once __DIR__ . '/../../includes/page-cache.php';
$db = getDB();
$db->prepare("DELETE FROM offer_tags WHERE id = ?")->execute([$itemId]);
@unlink(__DIR__ . '/../../data/tag-links-cache.json');
pageCacheClear();
echo json_encode(['success' => true]);

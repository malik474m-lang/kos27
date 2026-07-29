<?php
$db = getDB();
$db->prepare("DELETE FROM offer_tags WHERE id = ?")->execute([$itemId]);
@unlink(__DIR__ . '/../../data/tag-links-cache.json');
@unlink(__DIR__ . '/../../data/page_cache/' . md5('/zajmy') . '.html');
echo json_encode(['success' => true]);

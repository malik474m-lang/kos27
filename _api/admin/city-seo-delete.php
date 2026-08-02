<?php
require_once __DIR__ . '/../../includes/page-cache.php';
$db = getDB();
$db->prepare("DELETE FROM city_seo_texts WHERE id = ?")->execute([$itemId]);
apiCacheClear();
pageCacheClear();
echo json_encode(['success' => true]);

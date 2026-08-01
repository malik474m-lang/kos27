<?php
require_once __DIR__ . '/../../includes/city-seo.php';
ensureCityTagSeoTable();
$db = getDB();
$db->prepare("DELETE FROM city_tag_seo_texts WHERE id = ?")->execute([$itemId]);
apiCacheClear();
echo json_encode(['success' => true]);

<?php
require_once __DIR__ . '/../../includes/city-seo.php';
ensureCityTagSeoTable();
if (apiCacheStart('admin_citytagseo', 60)) exit;
$db = getDB();
$category = $_GET['category'] ?? 'microloans';
$stmt = $db->prepare("SELECT * FROM city_tag_seo_texts WHERE category = ? ORDER BY city_slug ASC, tag_slug ASC");
$stmt->execute([$category]);
apiCacheEnd($stmt->fetchAll());

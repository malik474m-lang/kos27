<?php
if (apiCacheStart('admin_cityseo', 60)) exit;
$db = getDB();
$category = $_GET['category'] ?? 'microloans';
$all = $db->prepare("SELECT * FROM city_seo_texts WHERE category = ? ORDER BY city_slug ASC");
$all->execute([$category]);
apiCacheEnd($all->fetchAll());

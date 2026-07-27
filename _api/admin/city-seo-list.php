<?php
$db = getDB();
$category = $_GET['category'] ?? 'microloans';

$all = $db->prepare("SELECT * FROM city_seo_texts WHERE category = ? ORDER BY city_slug ASC");
$all->execute([$category]);
echo json_encode($all->fetchAll());

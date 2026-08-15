<?php
if (apiCacheStart('public_articles', 120)) exit;

$db = getDB();
$stmt = $db->query("SELECT id, title, slug, excerpt, cover_image, created_at FROM articles WHERE is_published = 1 ORDER BY created_at DESC");
apiCacheEnd($stmt->fetchAll());

<?php
if (apiCacheStart('admin_reviews', 30)) exit;
$db = getDB();
$rows = $db->query("SELECT r.*, o.title as offer_title FROM reviews r LEFT JOIN offers o ON r.offer_id = o.id ORDER BY r.created_at DESC")->fetchAll();
apiCacheEnd($rows);

<?php
if (apiCacheStart('admin_categories', 15)) exit;
$db = getDB();
try {
    apiCacheEnd($db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll());
} catch (Exception $e) { apiCacheEnd([]); }

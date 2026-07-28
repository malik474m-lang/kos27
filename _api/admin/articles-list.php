<?php
if (apiCacheStart('admin_articles', 30)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll());

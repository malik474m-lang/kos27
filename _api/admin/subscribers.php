<?php
if (apiCacheStart('admin_subscribers', 20)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll());

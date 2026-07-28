<?php
if (apiCacheStart('admin_newsletters', 20)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM newsletters ORDER BY created_at DESC")->fetchAll());

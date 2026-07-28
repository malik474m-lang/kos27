<?php
if (apiCacheStart('admin_geo', 60)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM geo_redirects ORDER BY created_at DESC")->fetchAll());

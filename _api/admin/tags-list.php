<?php
if (apiCacheStart('admin_tags', 60)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM offer_tags ORDER BY sort_order ASC, id ASC")->fetchAll());

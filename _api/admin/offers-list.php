<?php
if (apiCacheStart('admin_offers', 30)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM offers ORDER BY sort_order ASC, id DESC")->fetchAll());

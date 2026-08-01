<?php
if (apiCacheStart('admin_subscribers', 20)) exit;
$db = getDB();
$dateColumn = dbDateColumn('subscribers', ['subscribed_at', 'created_at']);
apiCacheEnd($db->query("SELECT * FROM subscribers ORDER BY {$dateColumn} DESC")->fetchAll());

<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM subscribers ORDER BY subscribed_at DESC")->fetchAll());

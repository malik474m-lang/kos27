<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM geo_redirects ORDER BY created_at DESC")->fetchAll());

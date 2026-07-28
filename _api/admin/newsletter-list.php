<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM newsletters ORDER BY created_at DESC")->fetchAll());

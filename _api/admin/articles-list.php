<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll());

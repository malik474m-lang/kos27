<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM offers ORDER BY sort_order ASC, id DESC")->fetchAll());

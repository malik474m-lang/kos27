<?php
$db = getDB();
echo json_encode($db->query("SELECT * FROM offer_tags ORDER BY sort_order ASC, id ASC")->fetchAll());

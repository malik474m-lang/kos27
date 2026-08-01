<?php
$db = getDB();
$reviewTextColumn = function_exists('dbFirstExistingColumn') ? dbFirstExistingColumn('reviews', ['comment', 'text']) : 'comment';
$rows = $db->query("SELECT r.*, r.`{$reviewTextColumn}` AS comment, o.title as offer_title FROM reviews r LEFT JOIN offers o ON r.offer_id = o.id ORDER BY r.created_at DESC")->fetchAll();
echo json_encode($rows, JSON_UNESCAPED_UNICODE);

<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("INSERT INTO newsletters (subject, body_html) VALUES (?, ?)")
   ->execute([trim($data['subject'] ?? ''), $data['bodyHtml'] ?? '']);
echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

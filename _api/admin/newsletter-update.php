<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("UPDATE newsletters SET subject = ?, body_html = ? WHERE id = ? AND status = 'draft'")
   ->execute([trim($data['subject'] ?? ''), $data['bodyHtml'] ?? '', $itemId]);
echo json_encode(['success' => true]);

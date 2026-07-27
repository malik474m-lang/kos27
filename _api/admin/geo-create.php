<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("INSERT INTO geo_redirects (country_code, country_name, redirect_url, is_active) VALUES (?,?,?,?)")
   ->execute([strtoupper($data['countryCode'] ?? ''), $data['countryName'] ?? '', $data['redirectUrl'] ?? '', $data['isActive'] ?? true]);
echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

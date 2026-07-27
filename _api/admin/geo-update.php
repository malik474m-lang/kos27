<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("UPDATE geo_redirects SET country_code=?, country_name=?, redirect_url=?, is_active=? WHERE id=?")
   ->execute([strtoupper($data['countryCode'] ?? ''), $data['countryName'] ?? '', $data['redirectUrl'] ?? '', $data['isActive'] ?? true, $itemId]);
echo json_encode(['success' => true]);

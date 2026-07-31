<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$key = trim($input['license_key'] ?? '');
if (!$key) jsonResponse(['error' => 'Missing key'], 400);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT l.*, p.name as plan_name FROM licenses l JOIN plans p ON l.plan_id = p.id WHERE l.license_key = ?");
    $stmt->execute([$key]);
    $lic = $stmt->fetch();
    if (!$lic) jsonResponse(['error' => 'Not found'], 404);
    jsonResponse(['license' => ['key' => $lic['license_key'], 'domain' => $lic['domain'], 'status' => $lic['status'], 'plan' => $lic['plan_name'], 'expires_at' => $lic['expires_at']]]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

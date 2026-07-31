<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(['error' => 'ID required'], 400);
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM licenses WHERE id = ?"); $stmt->execute([$id]); $lic = $stmt->fetch();
    if (!$lic) jsonResponse(['error' => 'Not found'], 404);
    $db->prepare("DELETE FROM licenses WHERE id = ?")->execute([$id]);
    auditLog('license_deleted', 'license', $id, $lic, null);
    jsonResponse(['success' => true]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

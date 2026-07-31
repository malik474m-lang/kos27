<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    if (!password_verify($input['password'] ?? '', $stmt->fetch()['password_hash'])) jsonResponse(['error' => 'Wrong password'], 400);
    $db->prepare("UPDATE admins SET totp_enabled=0, totp_secret=NULL, backup_codes=NULL WHERE id=?")->execute([$_SESSION['admin_id']]);
    auditLog('2fa_disabled', 'admin', $_SESSION['admin_id']);
    jsonResponse(['success' => true]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

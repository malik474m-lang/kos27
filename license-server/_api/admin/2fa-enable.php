<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT totp_secret FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $secret = $stmt->fetch()['totp_secret'];
    if (!$secret || !verifyTotp($secret, $code)) jsonResponse(['error' => 'Invalid code'], 400);
    $backup = generateBackupCodes();
    $db->prepare("UPDATE admins SET totp_enabled=1, backup_codes=? WHERE id=?")->execute([json_encode($backup), $_SESSION['admin_id']]);
    auditLog('2fa_enabled', 'admin', $_SESSION['admin_id']);
    jsonResponse(['success' => true, 'backup_codes' => $backup]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

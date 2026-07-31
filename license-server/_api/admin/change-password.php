<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
$cur = $input['current_password'] ?? '';
$new = $input['new_password'] ?? '';
if (strlen($new) < 8) jsonResponse(['error' => 'Min 8 chars'], 400);
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    if (!password_verify($cur, $stmt->fetch()['password_hash'])) jsonResponse(['error' => 'Wrong password'], 400);
    $db->prepare("UPDATE admins SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
    auditLog('password_changed', 'admin', $_SESSION['admin_id']);
    jsonResponse(['success' => true]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

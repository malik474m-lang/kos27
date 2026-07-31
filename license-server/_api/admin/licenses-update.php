<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if (!$id) jsonResponse(['error' => 'ID required'], 400);
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM licenses WHERE id = ?"); $stmt->execute([$id]); $old = $stmt->fetch();
    if (!$old) jsonResponse(['error' => 'Not found'], 404);
    $upd = []; $params = [];
    foreach (['plan_id','domain','owner_name','owner_email','status','expires_at','notes','block_reason'] as $f) {
        if (array_key_exists($f, $input)) { $v = $input[$f]; if ($f === 'domain' && $v) $v = normalizeDomain($v); $upd[] = "$f=?"; $params[] = $v ?: null; }
    }
    if (!$upd) jsonResponse(['error' => 'Nothing to update'], 400);
    $params[] = $id;
    $db->prepare("UPDATE licenses SET " . implode(",", $upd) . " WHERE id=?")->execute($params);
    auditLog('license_updated', 'license', $id, $old, $input);
    jsonResponse(['success' => true]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

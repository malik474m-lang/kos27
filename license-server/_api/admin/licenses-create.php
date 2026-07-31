<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true);
$planId = (int)($input['plan_id'] ?? 0);
if (!$planId) jsonResponse(['error' => 'Plan required'], 400);
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?"); $stmt->execute([$planId]); $plan = $stmt->fetch();
    if (!$plan) jsonResponse(['error' => 'Plan not found'], 404);
    do { $key = generateLicenseKey(); $c = $db->prepare("SELECT id FROM licenses WHERE license_key = ?"); $c->execute([$key]); } while ($c->fetch());
    $expires = $input['expires_at'] ?? null;
    if (!$expires && $plan['duration_days']) $expires = date('Y-m-d H:i:s', strtotime("+{$plan['duration_days']} days"));
    $domain = ($input['domain'] ?? '') ? normalizeDomain($input['domain']) : null;
    $db->prepare("INSERT INTO licenses (license_key, plan_id, domain, owner_name, owner_email, status, expires_at, notes) VALUES (?,?,?,?,?,?,?,?)")->execute([
        $key, $planId, $domain, $input['owner_name'] ?? null, $input['owner_email'] ?? null, $input['status'] ?? 'pending', $expires, $input['notes'] ?? null
    ]);
    $id = $db->lastInsertId();
    auditLog('license_created', 'license', $id, null, ['key' => $key, 'plan' => $plan['name']]);
    jsonResponse(['success' => true, 'license' => ['id' => $id, 'license_key' => $key, 'plan_name' => $plan['name'], 'expires_at' => $expires]]);
} catch (Exception $e) { jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500); }

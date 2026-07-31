<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$key = trim($input['license_key'] ?? '');
$domain = normalizeDomain($input['domain'] ?? '');
if (!$key || !$domain) jsonResponse(['success' => false, 'error' => 'Missing params'], 400);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ?");
    $stmt->execute([$key]);
    $lic = $stmt->fetch();
    if (!$lic) jsonResponse(['success' => false, 'code' => 'INVALID_KEY'], 404);
    if ($lic['status'] === 'blocked') jsonResponse(['success' => false, 'code' => 'BLOCKED'], 403);
    if ($lic['domain'] && normalizeDomain($lic['domain']) !== $domain) jsonResponse(['success' => false, 'code' => 'ALREADY_ACTIVATED'], 403);
    
    $db->prepare("UPDATE licenses SET domain=?, status='active', activated_at=COALESCE(activated_at,NOW()), last_check=NOW(), last_check_ip=? WHERE id=?")->execute([$domain, getClientIp(), $lic['id']]);
    auditLog('license_activated_api', 'license', $lic['id'], null, ['domain' => $domain]);
    jsonResponse(['success' => true, 'domain' => $domain]);
} catch (Exception $e) { jsonResponse(['success' => false, 'error' => 'Error'], 500); }

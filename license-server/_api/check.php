<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$key = trim($input['license_key'] ?? '');
$domain = normalizeDomain($input['domain'] ?? '');
$ip = getClientIp();

if (!$key || !$domain) jsonResponse(['valid' => false, 'code' => 'MISSING_PARAMS'], 400);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT l.*, p.name as plan_name, p.slug as plan_slug FROM licenses l JOIN plans p ON l.plan_id = p.id WHERE l.license_key = ?");
    $stmt->execute([$key]);
    $lic = $stmt->fetch();
    
    if (!$lic) { logCheck(null, $key, $domain, $ip, 'invalid_key'); jsonResponse(['valid' => false, 'code' => 'INVALID_KEY'], 403); }
    
    if ($lic['status'] === 'blocked') { logCheck($lic['id'], $key, $domain, $ip, 'blocked'); jsonResponse(['valid' => false, 'code' => 'BLOCKED', 'reason' => $lic['block_reason']], 403); }
    if ($lic['status'] === 'suspended') { logCheck($lic['id'], $key, $domain, $ip, 'suspended'); jsonResponse(['valid' => false, 'code' => 'SUSPENDED'], 403); }
    if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) { $db->prepare("UPDATE licenses SET status='expired' WHERE id=?")->execute([$lic['id']]); logCheck($lic['id'], $key, $domain, $ip, 'expired'); jsonResponse(['valid' => false, 'code' => 'EXPIRED'], 403); }
    
    if ($lic['domain']) {
        if (normalizeDomain($lic['domain']) !== $domain) {
            $db->prepare("UPDATE licenses SET status='blocked', block_reason=? WHERE id=?")->execute(["Domain mismatch: {$lic['domain']} vs $domain", $lic['id']]);
            logCheck($lic['id'], $key, $domain, $ip, 'domain_mismatch');
            auditLog('license_blocked_domain', 'license', $lic['id'], null, ['expected' => $lic['domain'], 'got' => $domain]);
            jsonResponse(['valid' => false, 'code' => 'DOMAIN_MISMATCH'], 403);
        }
    } else {
        $db->prepare("UPDATE licenses SET domain=?, status='active', activated_at=NOW() WHERE id=?")->execute([$domain, $lic['id']]);
        auditLog('license_activated', 'license', $lic['id'], null, ['domain' => $domain]);
    }
    
    $db->prepare("UPDATE licenses SET last_check=NOW(), last_check_ip=?, check_count=check_count+1, status=IF(status='pending','active',status) WHERE id=?")->execute([$ip, $lic['id']]);
    logCheck($lic['id'], $key, $domain, $ip, 'success');
    
    $resp = ['valid' => true, 'license' => ['key' => $key, 'domain' => $lic['domain'] ?: $domain, 'plan' => $lic['plan_name'], 'expires_at' => $lic['expires_at']], 'next_check' => time() + 14400];
    $resp['signature'] = hash_hmac('sha256', json_encode($resp['license']), API_SECRET);
    jsonResponse($resp);
} catch (Exception $e) { jsonResponse(['valid' => false, 'code' => 'ERROR'], 500); }

function logCheck($lid, $key, $dom, $ip, $st) { try { getDB()->prepare("INSERT INTO license_checks (license_id, license_key, domain, ip, status, response_code) VALUES (?,?,?,?,?,?)")->execute([$lid, $key, $dom, $ip, $st, strtoupper($st)]); } catch (Exception $e) {} }

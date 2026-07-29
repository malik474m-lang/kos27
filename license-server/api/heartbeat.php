<?php
/**
 * Heartbeat — фоновая проверка (из крона клиента)
 * POST /api/heartbeat
 */
$rate = checkRateLimit('heartbeat', 120, 60);
if (!$rate['allowed']) { jsonError('Rate limit', 429); }

$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = trim($data['license_key'] ?? '');
$domain = normalizeDomain($data['domain'] ?? '');

if (!$licenseKey || !$domain) { jsonError('Missing params'); }

$db = getDB();
$stmt = $db->prepare("SELECT id, domain, status, expires_at FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$lic = $stmt->fetch();

if (!$lic || $lic['domain'] !== $domain || $lic['status'] !== 'active') {
    logAction('heartbeat', $lic['id'] ?? null, $licenseKey, $domain, 403, 'Invalid');
    jsonResponse(['valid' => false], 403);
}

if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) {
    $db->prepare("UPDATE licenses SET status = 'expired' WHERE id = ?")->execute([$lic['id']]);
    jsonResponse(['valid' => false, 'reason' => 'expired'], 403);
}

$db->prepare("UPDATE licenses SET last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([getClientIp(), $lic['id']]);

logAction('heartbeat', (int)$lic['id'], $licenseKey, $domain, 200, 'OK');
jsonResponse(['valid' => true]);

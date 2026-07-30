<?php
/**
 * Heartbeat — фоновая проверка (из крона клиента)
 * POST /api/heartbeat
 * 
 * При несовпадении домена — автоматическая блокировка лицензии
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

if (!$lic) {
    logAction('heartbeat', null, $licenseKey, $domain, 404, 'Key not found');
    jsonResponse(['valid' => false], 404);
}

// Домен изменился — БЛОКИРОВКА
if ($lic['domain'] && $lic['domain'] !== $domain) {
    $db->prepare("UPDATE licenses SET status = 'suspended' WHERE id = ? AND status = 'active'")
       ->execute([$lic['id']]);
    
    logAction('heartbeat', (int)$lic['id'], $licenseKey, $domain, 403, 
        'DOMAIN CHANGED: ' . $lic['domain'] . ' → ' . $domain . '. License SUSPENDED.');
    
    jsonResponse(['valid' => false, 'reason' => 'domain_changed', 'action' => 'suspended'], 403);
}

// Статус не active
if ($lic['status'] !== 'active') {
    logAction('heartbeat', (int)$lic['id'], $licenseKey, $domain, 403, 'Status: ' . $lic['status']);
    jsonResponse(['valid' => false, 'reason' => $lic['status']], 403);
}

// Срок истёк
if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) {
    $db->prepare("UPDATE licenses SET status = 'expired' WHERE id = ?")->execute([$lic['id']]);
    logAction('heartbeat', (int)$lic['id'], $licenseKey, $domain, 403, 'Expired');
    jsonResponse(['valid' => false, 'reason' => 'expired'], 403);
}

$db->prepare("UPDATE licenses SET last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([getClientIp(), $lic['id']]);

logAction('heartbeat', (int)$lic['id'], $licenseKey, $domain, 200, 'OK');
jsonResponse(['valid' => true]);

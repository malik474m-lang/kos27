<?php
/**
 * Проверка лицензии (периодическая)
 * POST /api/verify
 * Body: {"license_key": "KZM-...", "domain": "example.com"}
 */

$rate = checkRateLimit('verify', 60, 60); // 60 запросов в минуту
if (!$rate['allowed']) {
    jsonError('Rate limit', 429);
}

$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = trim($data['license_key'] ?? '');
$domain = normalizeDomain($data['domain'] ?? '');

if (!$licenseKey || !$domain) {
    jsonError('Укажите license_key и domain');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$license = $stmt->fetch();

if (!$license) {
    logAction('denied', null, $licenseKey, $domain, 404, 'Key not found');
    jsonError('Лицензия не найдена', 404);
}

// Проверка домена
if ($license['domain'] && $license['domain'] !== $domain) {
    logAction('denied', (int)$license['id'], $licenseKey, $domain, 403, 'Domain mismatch');
    jsonResponse(['valid' => false, 'error' => 'Домен не совпадает', 'expected_domain' => $license['domain']], 403);
}

// Проверка срока
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    $db->prepare("UPDATE licenses SET status = 'expired' WHERE id = ?")->execute([$license['id']]);
    logAction('verify', (int)$license['id'], $licenseKey, $domain, 403, 'Expired');
    jsonResponse(['valid' => false, 'error' => 'Срок лицензии истёк', 'expired_at' => $license['expires_at']], 403);
}

// Проверка статуса
if ($license['status'] !== 'active') {
    logAction('verify', (int)$license['id'], $licenseKey, $domain, 403, 'Status: ' . $license['status']);
    jsonResponse(['valid' => false, 'error' => 'Лицензия неактивна', 'status' => $license['status']], 403);
}

// Обновляем last_check
$db->prepare("UPDATE licenses SET last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([getClientIp(), $license['id']]);

logAction('verify', (int)$license['id'], $licenseKey, $domain, 200, 'Valid');

$daysLeft = null;
if ($license['expires_at']) {
    $daysLeft = max(0, (int)ceil((strtotime($license['expires_at']) - time()) / 86400));
}

jsonResponse([
    'valid' => true,
    'license' => [
        'plan' => $license['plan'],
        'status' => $license['status'],
        'domain' => $license['domain'],
        'features' => json_decode($license['features'] ?? '{}', true),
        'expires_at' => $license['expires_at'],
        'days_left' => $daysLeft,
    ],
]);

<?php
/**
 * Деактивация лицензии
 * POST /api/deactivate
 */
$rate = checkRateLimit('deactivate', 5, 300);
if (!$rate['allowed']) { jsonError('Rate limit', 429); }

$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = trim($data['license_key'] ?? '');
$domain = normalizeDomain($data['domain'] ?? '');

if (!$licenseKey || !$domain) { jsonError('Missing params'); }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? AND domain = ? LIMIT 1");
$stmt->execute([$licenseKey, $domain]);
$lic = $stmt->fetch();

if (!$lic) {
    logAction('denied', null, $licenseKey, $domain, 404, 'Not found for deactivation');
    jsonError('Лицензия не найдена для этого домена', 404);
}

$db->prepare("UPDATE licenses SET domain = '', hardware_hash = NULL, activations_count = GREATEST(activations_count - 1, 0) WHERE id = ?")
   ->execute([$lic['id']]);

logAction('deactivate', (int)$lic['id'], $licenseKey, $domain, 200, 'Deactivated');
jsonResponse(['valid' => true, 'message' => 'Лицензия деактивирована. Можно привязать к другому домену.']);

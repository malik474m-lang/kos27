<?php
/**
 * Активация лицензии
 * POST /api/activate
 * Body: {"license_key": "KZM-...", "domain": "example.com", "hardware_hash": "..."}
 */

$rate = checkRateLimit('activate', 10, 300); // 10 попыток за 5 мин
if (!$rate['allowed']) {
    logAction('denied', null, null, null, 429, 'Rate limit exceeded');
    jsonError('Слишком много запросов. Подождите.', 429);
}

$data = json_decode(file_get_contents('php://input'), true);
$licenseKey = trim($data['license_key'] ?? '');
$domain = normalizeDomain($data['domain'] ?? '');
$hardwareHash = trim($data['hardware_hash'] ?? '');

if (!$licenseKey || !$domain) {
    logAction('error', null, $licenseKey, $domain, 400, 'Missing params');
    jsonError('Укажите license_key и domain');
}

// Валидация формата ключа
if (!preg_match('/^KZM-[A-F0-9]{6}-[A-F0-9]{6}-[A-F0-9]{6}-[A-F0-9]{6}$/', $licenseKey)) {
    logAction('denied', null, $licenseKey, $domain, 400, 'Invalid key format');
    jsonError('Неверный формат лицензионного ключа');
}

$db = getDB();

// Находим лицензию
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$license = $stmt->fetch();

if (!$license) {
    logAction('denied', null, $licenseKey, $domain, 404, 'Key not found');
    jsonError('Лицензионный ключ не найден', 404);
}

// Проверка статуса
if ($license['status'] !== 'active') {
    logAction('denied', (int)$license['id'], $licenseKey, $domain, 403, 'License ' . $license['status']);
    jsonError('Лицензия ' . match($license['status']) {
        'suspended' => 'приостановлена',
        'expired' => 'истекла',
        'revoked' => 'отозвана',
        default => 'неактивна'
    }, 403);
}

// Проверка срока
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    $db->prepare("UPDATE licenses SET status = 'expired' WHERE id = ?")->execute([$license['id']]);
    logAction('denied', (int)$license['id'], $licenseKey, $domain, 403, 'Expired');
    jsonError('Срок лицензии истёк', 403);
}

// Проверка домена
if ($license['domain'] && $license['domain'] !== $domain) {
    logAction('denied', (int)$license['id'], $licenseKey, $domain, 403, 'Domain mismatch: expected ' . $license['domain']);
    jsonError('Лицензия привязана к другому домену: ' . $license['domain'], 403);
}

// Проверка количества активаций
if ((int)$license['activations_count'] >= (int)$license['max_activations'] && $license['domain'] !== $domain) {
    logAction('denied', (int)$license['id'], $licenseKey, $domain, 403, 'Max activations reached');
    jsonError('Достигнут лимит активаций', 403);
}

// Активируем — привязываем домен
$db->prepare("UPDATE licenses SET domain = ?, hardware_hash = ?, activations_count = activations_count + 1, last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([$domain, $hardwareHash ?: null, getClientIp(), $license['id']]);

logAction('activate', (int)$license['id'], $licenseKey, $domain, 200, 'Activated successfully');

// Генерируем зашифрованный токен активации
$activationData = json_encode([
    'license_id' => $license['id'],
    'key' => $licenseKey,
    'domain' => $domain,
    'plan' => $license['plan'],
    'features' => json_decode($license['features'] ?? '{}', true),
    'expires' => $license['expires_at'],
    'activated_at' => date('Y-m-d H:i:s'),
]);
$encryptedToken = encryptData($activationData);

jsonResponse([
    'valid' => true,
    'license' => [
        'key' => $licenseKey,
        'domain' => $domain,
        'plan' => $license['plan'],
        'features' => json_decode($license['features'] ?? '{}', true),
        'expires_at' => $license['expires_at'],
    ],
    'activation_token' => $encryptedToken,
    'message' => 'Лицензия активирована',
]);

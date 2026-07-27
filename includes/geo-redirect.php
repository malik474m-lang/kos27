<?php
// Гео-редирект с агрессивным кэшированием
// IP кэш: 1 час. Правила БД: 5 минут. Минимум запросов.

$geoUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Пропускаем статику, API, админку
if (
    str_starts_with($geoUri, '/api/') ||
    str_starts_with($geoUri, '/admin') ||
    str_starts_with($geoUri, '/images/') ||
    str_starts_with($geoUri, '/css/') ||
    str_starts_with($geoUri, '/js/') ||
    str_contains($geoUri, '.')
) {
    return;
}

// IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = trim(explode(',', $ip)[0]);

if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168') || str_starts_with($ip, '10.')) {
    return;
}

// Кэш IP → страна (файловый, 1 час)
$cacheDir = __DIR__ . '/../data/geo_cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

$cacheFile = $cacheDir . '/' . md5($ip) . '.txt';
$country = null;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
    $country = trim(file_get_contents($cacheFile));
} else {
    // Используем curl вместо file_get_contents
    $ch = curl_init("http://ip-api.com/json/{$ip}?fields=countryCode");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        $country = $data['countryCode'] ?? null;
        if ($country) {
            file_put_contents($cacheFile, $country);
        }
    }
}

if (!$country) return;

// Кэш правил из БД (5 минут)
$rulesCacheFile = __DIR__ . '/../data/geo_rules.json';
$rules = null;

if (file_exists($rulesCacheFile) && (time() - filemtime($rulesCacheFile)) < 300) {
    $rules = json_decode(file_get_contents($rulesCacheFile), true);
}

if (!$rules) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT country_code, redirect_url FROM geo_redirects WHERE is_active = 1");
        $rules = $stmt->fetchAll();
        file_put_contents($rulesCacheFile, json_encode($rules));
    } catch (Exception $e) {
        return;
    }
}

// Точное совпадение
foreach ($rules as $rule) {
    if ($rule['country_code'] === $country) {
        if ($rule['redirect_url'] && trim($rule['redirect_url'])) {
            header('Location: ' . $rule['redirect_url'], true, 302);
            exit;
        }
        return; // Исключение
    }
}

// Wildcard *
foreach ($rules as $rule) {
    if ($rule['country_code'] === '*' && $rule['redirect_url']) {
        header('Location: ' . $rule['redirect_url'], true, 302);
        exit;
    }
}

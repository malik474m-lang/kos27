<?php
if (apiCacheStart('public_geo', 3600)) exit;

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = trim(explode(',', $ip)[0]);

if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168') || str_starts_with($ip, '10.')) {
    apiCacheEnd(['city' => 'Москва', 'region' => 'Московская область', 'country' => 'Россия', 'detected' => false]);
    exit;
}

$ctx = stream_context_create(['http' => ['timeout' => 3]]);
$response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,regionName,country&lang=ru", false, $ctx);
if ($response) {
    $data = json_decode($response, true);
    apiCacheEnd(['city' => $data['city'] ?? 'Москва', 'region' => $data['regionName'] ?? '', 'country' => $data['country'] ?? 'Россия', 'detected' => true]);
} else {
    apiCacheEnd(['city' => 'Москва', 'region' => 'Московская область', 'country' => 'Россия', 'detected' => false]);
}

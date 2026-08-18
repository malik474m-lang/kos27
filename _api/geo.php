<?php
require_once __DIR__ . '/../data/cities.php';

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = trim(explode(',', $ip)[0]);
if (apiCacheStart('public_geo', 3600, $ip)) exit;

if (in_array($ip, ['127.0.0.1', '::1']) || str_starts_with((string)($ip), '192.168') || str_starts_with((string)($ip), '10.')) {
    $city = findCityByName('Москва');
    apiCacheEnd([
        'city' => 'Москва',
        'slug' => $city['slug'] ?? 'moskva',
        'region' => 'Московская область',
        'country' => 'Россия',
        'detected' => false
    ]);
    exit;
}

$ctx = stream_context_create(['http' => ['timeout' => 3]]);
$response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,regionName,country&lang=ru", false, $ctx);
if ($response) {
    $data = json_decode($response, true);
    $cityName = $data['city'] ?? 'Москва';
    $matched = findCityByName($cityName);
    apiCacheEnd([
        'city' => $cityName,
        'slug' => $matched['slug'] ?? null,
        'region' => $data['regionName'] ?? '',
        'country' => $data['country'] ?? 'Россия',
        'detected' => true
    ]);
} else {
    $city = findCityByName('Москва');
    apiCacheEnd([
        'city' => 'Москва',
        'slug' => $city['slug'] ?? 'moskva',
        'region' => 'Московская область',
        'country' => 'Россия',
        'detected' => false
    ]);
}

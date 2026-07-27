<?php
require_once __DIR__ . '/../../data/cities.php';
require_once __DIR__ . '/../../includes/city-seo.php';

$data = json_decode(file_get_contents('php://input'), true);
$category = $data['category'] ?? 'microloans';
$useGPT = !empty($data['useGPT']);
$citySlug = $data['citySlug'] ?? null; // null = все города

$db = getDB();
$generated = 0;
$errors = 0;

$targetCities = $citySlug ? [findCityBySlug($citySlug)] : $cities;
$targetCities = array_filter($targetCities);

foreach ($targetCities as $city) {
    // Проверяем есть ли уже текст
    $existing = getCitySeoText($city, $category);
    if ($existing && !($data['overwrite'] ?? false)) continue;

    $seo = null;

    // Пробуем GPT
    if ($useGPT) {
        $seo = generateCitySeoGPT($city, $category);
        if ($seo) {
            saveCitySeo($city['slug'], $category, $seo);
            $generated++;
            // Пауза между запросами к API
            if (!$citySlug) usleep(500000); // 0.5 сек
            continue;
        }
    }

    // Fallback на шаблон
    $seo = generateCitySeoTemplate($city, $category);
    saveCitySeo($city['slug'], $category, $seo);
    $generated++;
}

echo json_encode([
    'success' => true,
    'generated' => $generated,
    'total' => count($targetCities),
]);

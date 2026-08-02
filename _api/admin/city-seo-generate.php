<?php
require_once __DIR__ . '/../../data/cities.php';
require_once __DIR__ . '/../../includes/city-seo.php';

$data = json_decode(file_get_contents('php://input'), true);
$category = $data['category'] ?? 'microloans';
$useGPT = !empty($data['useGPT']);
$citySlug = $data['citySlug'] ?? null;
$citySlugs = is_array($data['citySlugs'] ?? null) ? array_values(array_filter($data['citySlugs'])) : [];
$overwrite = !empty($data['overwrite']);

$db = getDB();
$generated = 0;
$skipped = 0;
$errors = 0;

if ($citySlugs) {
    $targetCities = [];
    foreach ($citySlugs as $slug) {
        $city = findCityBySlug($slug);
        if ($city) $targetCities[] = $city;
    }
} else {
    $targetCities = $citySlug ? [findCityBySlug($citySlug)] : $cities;
}
$targetCities = array_values(array_filter($targetCities));

foreach ($targetCities as $city) {
    $existing = getCitySeoText($city, $category);
    if ($existing && !$overwrite) { $skipped++; continue; }

    try {
        $seo = null;
        if ($useGPT) {
            $seo = generateCitySeoGPT($city, $category);
            if ($seo) {
                saveCitySeo($city['slug'], $category, $seo);
                $generated++;
                if (!$citySlug && !$citySlugs) usleep(500000);
                continue;
            }
        }

        $seo = generateCitySeoTemplate($city, $category);
        saveCitySeo($city['slug'], $category, $seo);
        $generated++;
    } catch (Throwable $e) {
        $errors++;
    }
}

apiCacheClear();
require_once __DIR__ . '/../../includes/page-cache.php';
pageCacheClear();
echo json_encode([
    'success' => true,
    'generated' => $generated,
    'skipped' => $skipped,
    'errors' => $errors,
    'total' => count($targetCities),
], JSON_UNESCAPED_UNICODE);

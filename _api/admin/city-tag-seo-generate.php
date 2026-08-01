<?php
require_once __DIR__ . '/../../includes/city-seo.php';
require_once __DIR__ . '/../../data/cities.php';
ensureCityTagSeoTable();

$data = json_decode(file_get_contents('php://input'), true);
$category = $data['category'] ?? 'microloans';
$useGPT = !empty($data['useGPT']);
$citySlug = $data['citySlug'] ?? null;
$tagSlug = $data['tagSlug'] ?? null;
$overwrite = !empty($data['overwrite']);

$db = getDB();
$generated = 0;
$skipped = 0;
$errors = 0;

$targetCities = $citySlug ? [findCityBySlug($citySlug)] : getCities();
$targetCities = array_values(array_filter($targetCities));

$tagStmt = $db->prepare("SELECT * FROM offer_tags WHERE is_active = 1 AND category = ?" . ($tagSlug ? " AND slug = ?" : "") . " ORDER BY sort_order ASC");
$tagStmt->execute($tagSlug ? [$category, $tagSlug] : [$category]);
$tags = $tagStmt->fetchAll();

foreach ($targetCities as $city) {
    foreach ($tags as $tag) {
        $existing = getCityTagSeoText($city, $tag, $category);
        if ($existing && !$overwrite) { $skipped++; continue; }
        try {
            $seo = null;
            if ($useGPT) {
                $seo = generateCityTagSeoGPT($city, $tag, $category);
                if ($seo) {
                    saveCityTagSeo($city['slug'], $category, $tag['slug'], $seo);
                    $generated++;
                    if (!$citySlug || !$tagSlug) usleep(400000);
                    continue;
                }
            }
            $seo = generateCityTagSeoTemplate($city, $tag, $category);
            saveCityTagSeo($city['slug'], $category, $tag['slug'], $seo);
            $generated++;
        } catch (Throwable $e) {
            $errors++;
        }
    }
}

apiCacheClear();
echo json_encode([
    'success' => true,
    'generated' => $generated,
    'skipped' => $skipped,
    'errors' => $errors,
    'total' => count($targetCities) * count($tags),
], JSON_UNESCAPED_UNICODE);

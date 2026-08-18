<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/subcategories.php';

$db = getDB();

// $subcatSlug и $subcatCategory задаются в index.php перед require
$subcat = getSubcategoryBySlug($subcatSlug, $subcatCategory);
if (!$subcat) {
    http_response_code(404);
    $pageTitle = 'Страница не найдена';
    ob_start();
    echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Страница не найдена</h1></div>';
    $content = ob_get_clean();
    require __DIR__ . '/../includes/layout.php';
    return;
}

$catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
$catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
$catUrl = $catUrls[$subcat['category']] ?? '/zajmy';
$catLabel = $catLabels[$subcat['category']] ?? 'Предложения';

// Город (опциональный)
$cityData = null;
$citySeo = null;
$citySlugForLinks = null;
if (!empty($citySlug)) {
    require_once __DIR__ . '/../data/cities.php';
    $cityData = findCityBySlug($citySlug);
    if ($cityData) {
        $citySlugForLinks = $cityData['slug'];
        $citySeo = getSubcategoryCitySeo((int)$subcat['id'], $citySlug);
    }
}

// Офферы: фильтруем по правилам подкатегории
$allOffers = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
$allOffers->execute([$subcat['category']]);
$allOffers = $allOffers->fetchAll();

$rules = !empty($subcat['filter_rules']) ? json_decode($subcat['filter_rules'], true) : [];
$offers = is_array($rules) && $rules ? filterOffersBySubcategoryRules($allOffers, $rules) : $allOffers;

// === SEO-данные ===
$h1 = $subcat['h1'] ?: $subcat['title'];
$shortDesc = $subcat['description'] ?? '';
$seoText = $subcat['seo_text'] ?? '';

if ($cityData) {
    // Есть город — нужен уникальный контент
    if ($citySeo && !empty($citySeo['seo_text'])) {
        // Кастомный SEO-текст для этого города из БД
        $h1 = $citySeo['seo_h1'] ?: ($h1 . ' в ' . ($cityData['prep'] ?? $cityData['name']));
        $seoText = $citySeo['seo_text'];
        $shortDesc = $citySeo['meta_description'] ?: $shortDesc;
    } else {
        // Нет кастомного — автоматически адаптируем базовый текст под город
        $adapted = adaptSubcatTextForCity($seoText, $shortDesc, $cityData, $subcat);
        $h1 = $adapted['h1'];
        $shortDesc = $adapted['description'];
        $seoText = $adapted['seo_text'];
    }
}

$pageTitle = ($cityData && $citySeo && !empty($citySeo['meta_title']))
    ? $citySeo['meta_title']
    : ($h1 . ' | ' . SITE_NAME);
$metaDescription = $shortDesc ?: ($h1 . '. Сравните лучшие предложения онлайн.');

$breadcrumbs = [
    breadcrumbItem('Главная', '/'),
    breadcrumbItem($catLabel, $catUrl),
];
if ($cityData) {
    $breadcrumbs[] = breadcrumbItem($catLabel . ' в ' . ($cityData['prep'] ?? $cityData['name']), $catUrl . '/' . $cityData['slug']);
}
$breadcrumbs[] = breadcrumbItem($subcat['title'] . ($cityData ? ' в ' . ($cityData['prep'] ?? '') : ''), '');

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($h1) ?></h1>
    <?php if ($shortDesc): ?>
    <p class="text-gray-600 mb-8"><?= e(strip_tags($shortDesc)) ?></p>
    <?php endif; ?>

    <p class="text-gray-500 mb-4"><?= count($offers) ?> предложений</p>

    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-12 bg-white rounded-xl border">
        <p class="text-gray-500">Нет подходящих предложений</p>
        <a href="<?= $catUrl ?>" class="text-primary hover:underline mt-2 inline-block">← Все <?= mb_strtolower($catLabel) ?></a>
    </div>
    <?php endif; ?>

    <?php if ($seoText): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-8">
        <div class="prose prose-sm max-w-none text-gray-600"><?= $seoText ?></div>
    </div>
    <?php endif; ?>
</section>

<?php
// Блок с другими допзапросами (исключая текущий)
echo renderSubcategoryLinks(
    $subcat['category'],
    $citySlugForLinks,
    $subcat['slug'],
    $catLabel . ' — другие запросы'
);
?>

<?php
$canonicalUrl = pageCanonical($catUrl . ($cityData ? '/' . $cityData['slug'] : '') . '/q/' . $subcat['slug']);
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

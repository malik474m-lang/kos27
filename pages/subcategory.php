<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/subcategories.php';

$db = getDB();

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

$catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
$catLabel = $catLabels[$subcatCategory] ?? 'Предложения';
$catBase = getSubcategoryBaseUrl($subcatCategory);

$rules = json_decode($subcat['filter_rules'] ?? '{}', true) ?: [];
$allOffers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = '" . $subcatCategory . "' ORDER BY sort_order ASC")->fetchAll();
$filtered = filterOffersBySubcategoryRules($allOffers, $rules);

// Город
$city = null;
$citySeo = null;
if (!empty($citySlug)) {
    require_once __DIR__ . '/../data/cities.php';
    $city = findCityBySlug($citySlug);
    if ($city) {
        $citySeo = getSubcategoryCitySeo((int)$subcat['id'], $citySlug);
    }
}

$h1 = $citySeo['seo_h1'] ?? ($city ? ($subcat['seo_h1'] ?? $subcat['title']) . ' в ' . ($city['prep'] ?? $city['name']) : ($subcat['seo_h1'] ?? $subcat['title']));
$pageTitle = $citySeo['meta_title'] ?? ($city ? $subcat['title'] . ' в ' . ($city['prep'] ?? $city['name']) . ' | ' . SITE_NAME : ($subcat['meta_title'] ?? $subcat['title'] . ' | ' . SITE_NAME));
$metaDescription = $citySeo['meta_description'] ?? ($subcat['meta_description'] ?? ($catLabel . ' — ' . $subcat['title'] . ($city ? ' в ' . ($city['prep'] ?? $city['name']) : '') . '. Сравните предложения онлайн.'));
$seoText = $citySeo['seo_text'] ?? $subcat['seo_text'] ?? '';

$breadcrumbs = [
    breadcrumbItem('Главная', '/'),
    breadcrumbItem($catLabel, $catBase),
];
if ($city) {
    $breadcrumbs[] = breadcrumbItem($catLabel . ' в ' . ($city['prep'] ?? $city['name']), $catBase . '/' . $city['slug']);
}
$breadcrumbs[] = breadcrumbItem($subcat['title'], '');

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-6"><?= e($h1) ?></h1>
    
    <?php if ($filtered): ?>
    <div class="grid gap-4 mb-8">
        <?php foreach ($filtered as $offer): ?>
        <?= renderOfferCard($offer) ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-gray-50 rounded-xl p-12 text-center mb-8">
        <p class="text-gray-500">Предложений по запросу «<?= e($subcat['title']) ?>» пока нет</p>
        <a href="<?= $catBase ?>" class="text-primary hover:underline mt-2 inline-block">Все <?= mb_strtolower($catLabel) ?> →</a>
    </div>
    <?php endif; ?>

    <?php if ($seoText): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 prose prose-sm max-w-none">
        <?= $seoText ?>
    </div>
    <?php endif; ?>

    <?php
    // Города для этой подкатегории
    require_once __DIR__ . '/../data/cities.php';
    $topCitiesSub = array_slice(getCities(), 0, 20);
    if ($topCitiesSub): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4"><?= e($subcat['title']) ?> по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($topCitiesSub as $c): ?>
            <a href="<?= $catBase ?>/<?= $c['slug'] ?>/q/<?= $subcat['slug'] ?>" class="text-sm text-gray-600 hover:text-primary hover:underline">в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?= renderSubcategoryLinks($subcatCategory) ?>
</section>
<?php
$canonicalUrl = SITE_URL . $catBase . ($city ? '/' . $city['slug'] : '') . '/q/' . $subcat['slug'];
$jsonLdSchemas = [jsonLdBreadcrumb($breadcrumbs)];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

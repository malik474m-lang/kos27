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
if (!empty($subcatCitySlug)) {
    require_once __DIR__ . '/../data/cities.php';
    $cityData = findCity($subcatCitySlug);
    if ($cityData) {
        $citySeo = getSubcategoryCitySeo((int)$subcat['id'], $subcatCitySlug);
    }
}

// Офферы: фильтруем по правилам подкатегории
$allOffers = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
$allOffers->execute([$subcat['category']]);
$allOffers = $allOffers->fetchAll();

$rules = !empty($subcat['filter_rules']) ? json_decode($subcat['filter_rules'], true) : [];
$offers = is_array($rules) && $rules ? filterOffersBySubcategoryRules($allOffers, $rules) : $allOffers;

// SEO
$h1 = $subcat['h1'] ?: $subcat['title'];
$desc = $subcat['description'] ?? '';
$seoText = $subcat['seo_text'] ?? '';

if ($cityData) {
    $cityPrep = $cityData['prep'] ?? $cityData['name'];
    $h1 .= ' в ' . $cityPrep;
    if ($citySeo) {
        $h1 = $citySeo['seo_h1'] ?: $h1;
        $seoText = $citySeo['seo_text'] ?: $seoText;
        $desc = $citySeo['meta_description'] ?: $desc;
    }
}

$pageTitle = ($cityData && $citySeo && $citySeo['meta_title'])
    ? $citySeo['meta_title']
    : (($subcat['meta_title'] ?: $h1) . ' | ' . SITE_NAME);
$metaDescription = $desc ?: ($h1 . '. Сравните лучшие предложения онлайн.');

$breadcrumbs = [
    breadcrumbItem('Главная', '/'),
    breadcrumbItem($catLabel, $catUrl),
];
if ($cityData) {
    $breadcrumbs[] = breadcrumbItem($catLabel . ' в ' . ($cityData['prep'] ?? $cityData['name']), $catUrl . '/' . $cityData['slug']);
}
$breadcrumbs[] = breadcrumbItem($subcat['title'] . ($cityData ? ' в ' . ($cityData['prep'] ?? '') : ''), '');

// Другие подкатегории
$otherSubcats = getSubcategoriesByCategory($subcat['category']);

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($h1) ?></h1>
    <?php if ($desc): ?>
    <p class="text-gray-600 mb-8"><?= e($desc) ?></p>
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

    <?php if ($otherSubcats): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4"><?= e($catLabel) ?> — другие запросы</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($otherSubcats as $sc):
                $scUrl = $catUrl . '/q/' . $sc['slug'];
                if ($cityData) $scUrl = $catUrl . '/' . $cityData['slug'] . '/q/' . $sc['slug'];
            ?>
            <a href="<?= e($scUrl) ?>" class="inline-flex items-center gap-1.5 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 text-gray-700 hover:text-blue-700 px-3 py-2 rounded-lg text-sm transition-colors <?= ($sc['slug'] === $subcat['slug']) ? 'bg-blue-50 border-blue-300 text-blue-700 font-semibold' : '' ?>">
                <span><?= $sc['icon'] ?? '📋' ?></span> <?= e($sc['title']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php
$canonicalUrl = pageCanonical($catUrl . '/q/' . $subcat['slug']);
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

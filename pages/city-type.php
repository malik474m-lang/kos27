<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) {
    http_response_code(404);
    $pageTitle = 'Город не найден';
    $metaDescription = 'Страница не найдена.';
    ob_start();
    echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>';
    $content = ob_get_clean();
    require __DIR__ . '/../includes/layout.php';
    return;
}

$db = getDB();
$typeStmt = $db->prepare("SELECT * FROM offer_tags WHERE slug = ? AND category = ? AND is_active = 1 LIMIT 1");
$typeStmt->execute([$typeSlug, $cityTypeCategory]);
$type = $typeStmt->fetch();

if (!$type) {
    http_response_code(404);
    $pageTitle = 'Подборка не найдена';
    $metaDescription = 'Страница подборки не найдена.';
    ob_start();
    echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Подборка не найдена</h1></div>';
    $content = ob_get_clean();
    require __DIR__ . '/../includes/layout.php';
    return;
}

$GLOBALS['current_city_context'] = $city;
$GLOBALS['current_city_context_type'] = 'city';

$linkedStmt = $db->prepare("SELECT o.* FROM offers o JOIN offer_tag_links l ON o.id = l.offer_id WHERE l.tag_id = ? AND o.is_active = 1 AND o.category = ? ORDER BY o.sort_order ASC");
$linkedStmt->execute([$type['id'], $cityTypeCategory]);
$offers = $linkedStmt->fetchAll();

if (!$offers) {
    $allStmt = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
    $allStmt->execute([$cityTypeCategory]);
    $offers = $allStmt->fetchAll();
    $showingAll = true;
} else {
    $showingAll = false;
}

$otherTags = $db->prepare("SELECT * FROM offer_tags WHERE is_active = 1 AND category = ? AND id != ? ORDER BY sort_order ASC");
$otherTags->execute([$cityTypeCategory, $type['id']]);
$otherTags = $otherTags->fetchAll();

$nearbyCities = array_values(array_filter(getCities(), fn($c) => $c['slug'] !== $citySlug));
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 10);

$features = [];
if (!empty($type['features'])) {
    $features = json_decode($type['features'], true) ?: [];
}

$catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
$catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
$catUrl = $catUrls[$cityTypeCategory] ?? '/zajmy';
$catLabel = $catLabels[$cityTypeCategory] ?? 'Предложения';

$cityTagSeo = getOrGenerateCityTagSeo($city, $type, $cityTypeCategory);
$cityTagTitle = ($cityTagSeo['seo_h1'] ?? '') ?: (($type['h1'] ?: $type['title']) . ' в ' . $city['prep']);
$pageTitle = ($cityTagSeo['meta_title'] ?? '') ?: ($cityTagTitle . ' | ' . SITE_NAME);
$metaDescription = ($cityTagSeo['meta_description'] ?? '') ?: (($type['title'] . ' в ' . $city['prep'] . '. Сравните ' . count($offers) . ' предложений и выберите лучший вариант.'));
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem($catLabel, $catUrl), breadcrumbItem($city['name'], $catUrl . '/' . $citySlug), breadcrumbItem($type['title'] . ' в ' . $city['prep'], $catUrl . '/' . $citySlug . '/type/' . $type['slug'])];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <h1 class="text-3xl font-bold text-gray-900 mb-3"><?= e($cityTagTitle) ?></h1>
    <p class="text-gray-600 text-lg mb-8"><?= e($type['description'] ?: ($catLabel . ' в ' . $city['prep'])) ?>. Доступно <?= count($offers) ?> предложений.</p>

    <?php if ($features): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php foreach ($features as $f): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <span class="text-2xl block mb-2"><?= $f['icon'] ?? '📌' ?></span>
            <p class="font-semibold text-sm"><?= e($f['title'] ?? '') ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= e($f['text'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($showingAll): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 text-sm text-yellow-800">Для этой подборки пока нет отдельных привязанных офферов, поэтому показаны все предложения категории.</div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <?php if (!empty($cityTagSeo['seo_text'])): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-8 mt-8 prose max-w-none text-gray-700">
        <?= autoLinkText($cityTagSeo['seo_text'], 8) ?>
    </div>
    <?php elseif (!empty($type['content'])): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-8 mt-8 prose max-w-none text-gray-700">
        <?= autoLinkText($type['content'], 8) ?>
    </div>
    <?php endif; ?>

    <?php if ($otherTags): ?>
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Другие подборки в <?= e($city['prep']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($otherTags as $ot): ?>
            <a href="<?= $catUrl ?>/<?= e($citySlug) ?>/type/<?= e($ot['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if ($ot['icon']): ?><span><?= $ot['icon'] ?></span><?php endif; ?><?= e($ot['title']) ?> в <?= e($city['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4"><?= e($type['title']) ?> в других городах</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="<?= $catUrl ?>/<?= e($c['slug']) ?>/type/<?= e($type['slug']) ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?= e($type['title']) ?> в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
];
// Schema.org ItemList
$_ilItems = [];
foreach ($offers as $_ii => $_io) {
    $_ilItems[] = [
        '@type' => 'ListItem',
        'position' => $_ii + 1,
        'name' => $_io['title'],
        'url' => SITE_URL . '/offer/' . $_io['slug'],
    ];
}
if ($_ilItems) {
    $jsonLdSchemas[] = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Подборка предложений',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . $catUrl . '/' . $citySlug . '/type/' . $type['slug'];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

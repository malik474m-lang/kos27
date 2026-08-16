<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/recommendation.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/categories.php';

$cat = findCategoryBySlug($catSlug);
if (!$cat || !$cat['is_active']) { http_response_code(404); $pageTitle='Категория не найдена'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Категория не найдена</h1><a href="/" class="text-primary hover:underline mt-4 inline-block">На главную</a></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offerCategoryKey = getCategoryOfferKeyBySlug($cat['slug']);
$offers = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
$offers->execute([$offerCategoryKey]);
$offersList = $offers->fetchAll();

// Теги этой категории
$tags = $db->prepare("SELECT * FROM offer_tags WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
$tags->execute([$offerCategoryKey]);
$catTags = $tags->fetchAll();

// Подкатегории
$subcats = getSubcategories((int)$cat['id']);

// Родительская категория (для breadcrumb)
$parentCat = $cat['parent_id'] ? findCategoryBySlug('') : null;
if ($cat['parent_id']) {
    foreach (getCategories(false) as $pc) {
        if ((int)$pc['id'] === (int)$cat['parent_id']) { $parentCat = $pc; break; }
    }
}

$pageTitle = ($cat['meta_title'] ?: $cat['name']) . ' — ' . SITE_NAME;
$metaDescription = $cat['meta_description'] ?: 'Сравните лучшие предложения: ' . $cat['name'];
$bestOffer = getBestOfferByCategory($offerCategoryKey);

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a>
        <?php if ($parentCat): ?> → <a href="<?= getCategoryUrl($parentCat) ?>" class="hover:text-primary"><?= e($parentCat['name']) ?></a><?php endif; ?>
        → <?= e($cat['name']) ?>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= e($cat['h1'] ?: $cat['name']) ?></h1>
    <?php if ($cat['description']): ?>
    <p class="text-gray-600 mb-6"><?= e($cat['description']) ?></p>
    <?php endif; ?>

    <?= renderBestOfferRecommendation($bestOffer, 'Самый выгодный вариант в разделе') ?>

    <?php if ($subcats): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($subcats as $sc): ?>
        <a href="<?= getCategoryUrl($sc) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if ($sc['icon']): ?><span><?= $sc['icon'] ?></span><?php endif; ?><?= e($sc['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($catTags): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($catTags as $lt): ?>
        <a href="<?= getCategoryUrl($cat) ?>/type/<?= e($lt['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if (!empty($lt['icon'])): ?><span><?= $lt['icon'] ?></span><?php endif; ?><?= e($lt['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="text-gray-500 mb-4"><?= count($offersList) ?> предложений</p>

    <?php if ($offersList): ?>
    <div class="grid gap-4">
        <?php foreach ($offersList as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border p-10 text-center"><p class="text-gray-500">Предложений пока нет</p></div>
    <?php endif; ?>

    <?php if ($cat['seo_text']): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <div class="prose prose-sm text-gray-600 max-w-none"><?= autoLinkText($cat['seo_text'], 8) ?></div>
    </div>
    <?php endif; ?>
</div>
<?php
$jsonLdSchemas = [];
if ($parentCat) {
    $jsonLdSchemas[] = jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>$parentCat['name'],'url'=>getCategoryUrl($parentCat)],['name'=>$cat['name'],'url'=>getCategoryUrl($cat)]]);
} else {
    $jsonLdSchemas[] = jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>$cat['name'],'url'=>getCategoryUrl($cat)]]);
}
// Schema.org ItemList
$_ilItems = [];
foreach ($offersList as $_ii => $_io) {
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
        'name' => $cat['name'] . ' — предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . getCategoryUrl($cat);
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

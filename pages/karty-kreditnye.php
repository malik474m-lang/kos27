<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC")->fetchAll();
$cardTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Кредитные карты онлайн — Сравнение | ' . SITE_NAME;
$metaDescription = 'Сравните кредитные карты с льготным периодом и кэшбеком.';

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Кредитные карты</h1>
    <p class="text-gray-600 mb-8">Сравните <?= count($offers) ?> кредитных карт</p>
    <?php if ($cardTags): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($cardTags as $lt): ?>
        <a href="/karty/kreditnye/type/<?= e($lt['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if (!empty($lt['icon'])): ?><span><?= $lt['icon'] ?></span><?php endif; ?><?= e($lt['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Карты по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php $shuffled = $cities; shuffle($shuffled); foreach (array_slice($shuffled, 0, 10) as $c): ?>
            <a href="/karty/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Карты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
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
        'name' => 'Кредитные карты — лучшие предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/karty/kreditnye';
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'list-sticky-cta',
    'href' => '/karty/kreditnye',
    'label' => 'Смотреть кредитные карты',
    'sub' => 'Подберите карту с льготным периодом',
    'variant' => 'primary',
    'external' => false,
]);
require __DIR__ . '/../includes/layout.php';

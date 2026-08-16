<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC")->fetchAll();
$debitTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Дебетовые карты | ' . SITE_NAME;
$metaDescription = 'Сравните дебетовые карты с кэшбеком и процентом на остаток.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Дебетовые карты', '/karty/debetovye')];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Дебетовые карты</h1>
    <p class="text-gray-600 mb-8">Сравните <?= count($offers) ?> дебетовых карт</p>
    <?php if ($debitTags): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($debitTags as $lt): ?>
        <a href="/karty/debetovye/type/<?= e($lt['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if (!empty($lt['icon'])): ?><span><?= $lt['icon'] ?></span><?php endif; ?><?= e($lt['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Дебетовые карты — как выбрать</h2>
        <div class="prose prose-sm text-gray-600 max-w-none">
            <p>При выборе дебетовой карты обратите внимание на процент на остаток, кэшбек-категории, стоимость обслуживания и наличие бесплатных переводов. Сравните условия на нашем сайте.</p>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Карты по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php $shuffled = $cities; shuffle($shuffled); foreach (array_slice($shuffled, 0, 10) as $c): ?>
            <a href="/karty/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Карты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mt-6">
        <a href="/zajmy" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Займы онлайн</a>
        <a href="/kredity" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредиты</a>
        <a href="/karty/kreditnye" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредитные карты</a>
        <a href="/compare" class="inline-block bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Сравнение предложений</a>
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
        'name' => 'Дебетовые карты — лучшие предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/karty/debetovye';
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'list-sticky-cta',
    'href' => '/karty/debetovye',
    'label' => 'Смотреть дебетовые карты',
    'sub' => 'Подберите карту с кэшбэком и бонусами',
    'variant' => 'primary',
    'external' => false,
]);
require __DIR__ . '/../includes/layout.php';

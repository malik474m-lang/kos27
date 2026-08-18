<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC")->fetchAll();
$cardTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Кредитные карты онлайн — Сравнение | ' . SITE_NAME;
$metaDescription = 'Сравните кредитные карты с льготным периодом и кэшбеком.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Кредитные карты', '/karty/kreditnye')];

$creditCardFaqs = [
    ['q' => 'Чем кредитная карта отличается от дебетовой?', 'a' => 'Кредитная карта позволяет использовать деньги банка в рамках установленного лимита, а дебетовая — только собственные средства клиента. Для кредитной карты особенно важны льготный период, комиссия за снятие наличных, минимальный платеж и стоимость обслуживания.'],
    ['q' => 'Что такое льготный период по кредитной карте?', 'a' => 'Льготный период, или грейс-период, — это срок, в течение которого банк не начисляет проценты на покупки при соблюдении условий договора. Важно уточнять, на какие операции он распространяется и когда заканчивается расчётный период.'],
    ['q' => 'На что смотреть при выборе кредитной карты?', 'a' => 'Сравнивайте кредитный лимит, длительность льготного периода, стоимость обслуживания, кэшбэк, минимальный платеж, комиссии за переводы и снятие наличных. Полезно также проверить, как банк рассчитывает проценты после окончания льготного периода.'],
    ['q' => 'Можно ли снимать наличные с кредитной карты?', 'a' => 'Можно, но чаще всего это невыгодно. По таким операциям банки часто сразу начисляют проценты и отдельную комиссию, а льготный период может не действовать. Перед использованием карты для снятия наличных обязательно проверьте тарифы.'],
    ['q' => 'Что будет, если платить только минимальный платеж?', 'a' => 'Минимальный платеж позволяет не выйти в просрочку, но долг при этом погашается медленно, а общая переплата может заметно вырасти. Такой вариант подходит только как временное решение, если вы не можете закрыть задолженность полностью.'],
    ['q' => 'Как пользоваться кредитной картой без переплаты?', 'a' => 'Старайтесь укладываться в льготный период, не снимать наличные без необходимости, вовремя вносить платежи и следить за комиссиями банка. Лучше выбирать карту с понятными условиями и подходящим лимитом под ваш уровень расходов.'],
];

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

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по кредитным картам</h2>
        <div class="space-y-4">
            <?php foreach ($creditCardFaqs as $faq): ?>
            <details class="group border border-gray-200 rounded-lg">
                <summary class="flex justify-between items-center cursor-pointer p-4 font-medium text-gray-900 hover:bg-gray-50 rounded-lg">
                    <span><?= e($faq['q']) ?></span>
                    <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="px-4 pb-4 text-sm text-gray-600 leading-relaxed">
                    <?= safeAutoLink($faq['a'], 2) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($creditCardFaqs),
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
require_once __DIR__ . '/../includes/subcategories.php';
echo renderSubcategoryLinks('credit_cards');
?>
<?php
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

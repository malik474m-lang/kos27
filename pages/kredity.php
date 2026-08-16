<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();
$creditTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = pageMetaTitle('Кредиты онлайн — Сравнение банковских кредитов', false) . ' | ' . SITE_NAME;
$metaDescription = 'Сравните условия банковских кредитов. Низкие ставки, удобное оформление, быстрое одобрение.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Кредиты', '/kredity')];

$creditFaqs = [
    ['q' => 'Чем кредит отличается от займа?', 'a' => 'Банковский кредит обычно оформляется на более крупную сумму и длительный срок, а требования к заёмщику строже. Займы чаще выдаются быстрее и на меньшие суммы, но процентная ставка по ним может быть выше.'],
    ['q' => 'На что смотреть при выборе кредита?', 'a' => 'Сравнивайте процентную ставку, полную стоимость кредита, срок, ежемесячный платеж, требования к заёмщику, наличие страховок и комиссии за дополнительные услуги. Полезно оценивать не только одобрение, но и итоговую переплату.'],
    ['q' => 'Что такое ПСК по кредиту?', 'a' => 'ПСК — это полная стоимость кредита, выраженная в процентах годовых. Она помогает понять реальные расходы по кредиту, потому что учитывает не только ставку, но и обязательные платежи, комиссии и дополнительные условия договора.'],
    ['q' => 'Можно ли получить кредит без справок о доходах?', 'a' => 'Да, некоторые банки предлагают кредиты без справок, но условия по таким продуктам могут отличаться: ставка выше, сумма меньше, а лимиты строже. Перед оформлением важно проверить, какие документы всё же могут понадобиться банку на этапе рассмотрения заявки.'],
    ['q' => 'Как повысить шансы на одобрение кредита?', 'a' => 'Полезно указывать достоверные данные, не завышать запрашиваемую сумму, проверять кредитную историю заранее и выбирать продукты, подходящие по вашему доходу. Также помогает отсутствие текущих просрочек и разумная долговая нагрузка.'],
    ['q' => 'Можно ли погасить кредит досрочно?', 'a' => 'Обычно да, но порядок досрочного погашения зависит от банка и условий договора. Перед оформлением стоит уточнить, нужно ли подавать заявление заранее и как изменится график платежей после частичного или полного досрочного погашения.'],
];

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Кредиты онлайн</h1>
    <p class="text-gray-600 mb-8">Сравните <?= count($offers) ?> предложений от банков</p>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>


    <!-- Перелинковка -->
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Кредиты по городам</h2>
        <div class="flex flex-wrap gap-2">
            <?php $shuffled = $cities; shuffle($shuffled); foreach (array_slice($shuffled, 0, 12) as $c): ?>
            <a href="/kredity/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредиты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по кредитам</h2>
        <div class="space-y-4">
            <?php foreach ($creditFaqs as $faq): ?>
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
    jsonLdFAQ($creditFaqs),
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
        'name' => 'Кредиты онлайн — лучшие предложения банков',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = pageCanonical('/kredity');
$content = ob_get_clean();
$content .= renderStickyCta([
    'id' => 'list-sticky-cta',
    'href' => '/kredity',
    'label' => 'Смотреть кредиты',
    'sub' => 'Сравните условия банков за пару минут',
    'variant' => 'primary',
    'external' => false,
]);
require __DIR__ . '/../includes/layout.php';

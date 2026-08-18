<?php
require_once __DIR__ . '/../includes/subcategories.php';
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден'; $metaDescription=''; $pageHeadHtml='<meta name="robots" content="noindex,follow">'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1><a href="/karty/kreditnye" class="btn-primary inline-block mt-4">Все кредитные карты</a></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$GLOBALS['current_city_context'] = $city;
$GLOBALS['current_city_context_type'] = 'city';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC")->fetchAll();
$tags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credit_cards' ORDER BY sort_order ASC LIMIT 8")->fetchAll();
$citySeo = getOrGenerateCitySeo($city, 'credit_cards');
$nearbyCities = array_filter(getCities(), fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

$pageTitle = ($citySeo['meta_title'] ?? '') ?: "Кредитные карты в {$city['prep']} — " . SITE_NAME;
$metaDescription = ($citySeo['meta_description'] ?? '') ?: "Кредитные карты в {$city['prep']}. Сравните предложения по кредитным картам с кэшбэком и льготным периодом.";
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Кредитные карты', '/karty/kreditnye'), breadcrumbItem($city['name'], '/karty/kreditnye/' . $city['slug'])];
$cityCreditCardFaqs = [
    ['q' => 'Как выбрать кредитную карту в ' . $city['prep'] . '?', 'a' => 'Сравнивайте длительность льготного периода, кредитный лимит, стоимость обслуживания, кэшбэк, комиссии за переводы и снятие наличных. Лучше выбирать карту под реальные сценарии использования, а не только по рекламному лимиту.'],
    ['q' => 'Что важно знать о льготном периоде?', 'a' => 'Льготный период действует не по всем операциям одинаково. Важно уточнить, распространяется ли он только на покупки, как формируется расчётный период и нужно ли полностью погашать долг до определённой даты, чтобы не платить проценты.'],
    ['q' => 'Можно ли снимать наличные с кредитной карты в ' . $city['prep'] . '?', 'a' => 'Можно, но чаще всего это менее выгодно, чем покупки. По снятию наличных банки обычно начисляют комиссию и могут не давать льготный период, поэтому условия нужно проверять заранее.'],
    ['q' => 'Какой минимальный платеж по кредитной карте?', 'a' => 'Минимальный платеж зависит от банка и условий карты. Обычно это небольшая часть от задолженности, которую нужно внести в срок, чтобы не допустить просрочку. Но если платить только минимум, переплата со временем растёт.'],
    ['q' => 'Как пользоваться кредитной картой без переплаты?', 'a' => 'Главное правило — укладываться в льготный период, не допускать просрочек и внимательно проверять комиссии банка. Также лучше избегать наличных операций, если карта в первую очередь выгодна именно для покупок.'],
    ['q' => 'Можно ли оформить кредитную карту онлайн?', 'a' => 'Да, многие банки принимают онлайн-заявки и дают предварительное решение дистанционно. Но окончательные условия и лимит зависят от оценки клиента, документов и внутренней скоринговой модели банка.'],
];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= e($citySeo['seo_h1'] ?? "Кредитные карты в {$city['prep']}") ?></h1>

    <?php if ($tags): ?>
    <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-100">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Подборки кредитных карт в <?= e($city['prep']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
            <a href="/karty/kreditnye/<?= e($city['slug']) ?>/type/<?= e($tag['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-sm hover:bg-blue-100 transition-colors"><?php if (!empty($tag['icon'])): ?><span><?= $tag['icon'] ?></span><?php endif; ?><?= e($tag['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-gray-50 rounded-xl p-12 text-center"><p class="text-gray-500">Предложений по кредитным картам пока нет</p></div>
    <?php endif; ?>

    <?php if (!empty($citySeo['seo_text'])): ?>
    <div class="bg-white rounded-xl border p-6 mt-8 prose prose-sm max-w-none"><?= autoLinkText($citySeo['seo_text'], 8) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по кредитным картам в <?= e($city['prep']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($cityCreditCardFaqs as $faq): ?>
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

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Кредитные карты в других городах</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="/karty/kreditnye/<?= e($c['slug']) ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
// Допзапросы для кредитных карт с учётом города
echo renderSubcategoryLinks('credit_cards', $city['slug'], null, "Кредитные карты в {$city['prep']} — популярные запросы");
?>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($cityCreditCardFaqs),
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
        'name' => 'Кредитные карты — предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/karty/kreditnye/' . $city['slug'];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

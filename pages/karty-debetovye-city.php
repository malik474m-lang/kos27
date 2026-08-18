<?php
require_once __DIR__ . '/../includes/subcategories.php';
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден'; $metaDescription=''; $pageHeadHtml='<meta name="robots" content="noindex,follow">'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1><a href="/karty/debetovye" class="btn-primary inline-block mt-4">Все дебетовые карты</a></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$GLOBALS['current_city_context'] = $city;
$GLOBALS['current_city_context_type'] = 'city';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC")->fetchAll();
$tags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'debit_cards' ORDER BY sort_order ASC LIMIT 8")->fetchAll();
$citySeo = getOrGenerateCitySeo($city, 'debit_cards');
$nearbyCities = array_filter(getCities(), fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

$pageTitle = ($citySeo['meta_title'] ?? '') ?: "Дебетовые карты в {$city['prep']} — " . SITE_NAME;
$metaDescription = ($citySeo['meta_description'] ?? '') ?: "Дебетовые карты в {$city['prep']}. Сравните дебетовые карты с кэшбэком и процентом на остаток.";
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Дебетовые карты', '/karty/debetovye'), breadcrumbItem($city['name'], '/karty/debetovye/' . $city['slug'])];
$cityDebitCardFaqs = [
    ['q' => 'Как выбрать дебетовую карту в ' . $city['prep'] . '?', 'a' => 'Сравнивайте стоимость обслуживания, кэшбэк, процент на остаток, лимиты на переводы через СБП, условия снятия наличных и бонусные программы. Лучший вариант зависит от того, как именно вы планируете пользоваться картой каждый день.'],
    ['q' => 'Что важнее для дебетовой карты: кэшбэк или процент на остаток?', 'a' => 'Если вы активно расплачиваетесь картой, чаще выгоднее кэшбэк. Если на счёте обычно лежит заметная сумма, полезнее может быть процент на остаток. Оптимально выбирать карту, где условия совпадают с вашим реальным сценарием расходов и хранения средств.'],
    ['q' => 'Бывает ли бесплатное обслуживание дебетовой карты?', 'a' => 'Да, многие банки предлагают бесплатное обслуживание сразу или при выполнении условий: минимальный остаток, покупки на определённую сумму, зарплатный проект или регулярные поступления. Эти условия важно проверить заранее, чтобы избежать неожиданной комиссии.'],
    ['q' => 'Можно ли переводить деньги по СБП без комиссии?', 'a' => 'Часто да, но у банка могут быть лимиты. До определённой суммы переводы через СБП бесплатны, а сверх лимита может взиматься комиссия. Перед оформлением карты стоит уточнить месячные ограничения и условия переводов.'],
    ['q' => 'Как проверить условия снятия наличных?', 'a' => 'Обратите внимание, действует ли бесплатное снятие только в банкоматах своего банка или также у партнёров. Важны лимиты, минимальная сумма операции и комиссия за выход за установленный тарифом объём снятия.'],
    ['q' => 'Можно ли оформить дебетовую карту онлайн в ' . $city['prep'] . '?', 'a' => 'Да, многие банки позволяют подать онлайн-заявку и получить карту в отделении или с доставкой. Но условия выпуска, активации и бонусных программ могут отличаться, поэтому полезно сравнить сразу несколько предложений.'],
];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= e($citySeo['seo_h1'] ?? "Дебетовые карты в {$city['prep']}") ?></h1>

    <?php if ($tags): ?>
    <div class="bg-green-50 rounded-xl p-6 mb-8 border border-green-100">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Подборки дебетовых карт в <?= e($city['prep']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
            <a href="/karty/debetovye/<?= e($city['slug']) ?>/type/<?= e($tag['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-green-200 text-green-700 px-3 py-1.5 rounded-lg text-sm hover:bg-green-100 transition-colors"><?php if (!empty($tag['icon'])): ?><span><?= $tag['icon'] ?></span><?php endif; ?><?= e($tag['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-gray-50 rounded-xl p-12 text-center"><p class="text-gray-500">Предложений по дебетовым картам пока нет</p></div>
    <?php endif; ?>

    <?php if (!empty($citySeo['seo_text'])): ?>
    <div class="bg-white rounded-xl border p-6 mt-8 prose prose-sm max-w-none"><?= autoLinkText($citySeo['seo_text'], 8) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по дебетовым картам в <?= e($city['prep']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($cityDebitCardFaqs as $faq): ?>
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
        <h2 class="text-lg font-bold text-gray-900 mb-4">Дебетовые карты в других городах</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="/karty/debetovye/<?= e($c['slug']) ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-green-500 hover:text-green-600 transition-colors">в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
// Допзапросы для дебетовых карт с учётом города
echo renderSubcategoryLinks('debit_cards', $city['slug'], null, "Дебетовые карты в {$city['prep']} — популярные запросы");
?>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($cityDebitCardFaqs),
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
        'name' => 'Дебетовые карты — предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/karty/debetovye/' . $city['slug'];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

<?php
require_once __DIR__ . '/../includes/subcategories.php';
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден — кредиты'; $metaDescription='Страница кредитов для выбранного города не найдена.'; $pageHeadHtml = '<meta name="robots" content="noindex,follow">'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$GLOBALS['current_city_context'] = $city;
$GLOBALS['current_city_context_type'] = 'city';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();
$cityTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC LIMIT 8")->fetchAll();
$citySeo = getOrGenerateCitySeo($city, 'credits');
$nearbyCities = array_filter($cities, fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

$pageTitle = ($citySeo['meta_title'] ?? '') ?: (($citySeo['seo_h1'] ?? '') ?: "Кредиты в {$city['prep']} — " . SITE_NAME);
$metaDescription = ($citySeo['meta_description'] ?? '') ?: "Кредиты в {$city['prep']}. Сравните условия банков.";
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Кредиты', '/kredity'), breadcrumbItem('Кредиты в ' . $city['prep'], '/kredity/' . $citySlug)];
$cityCreditFaqs = [
    ['q' => 'Можно ли оформить кредит в ' . $city['prep'] . ' онлайн?', 'a' => 'Да, многие банки позволяют подать заявку на кредит онлайн. Обычно решение предварительно приходит после анкеты, а окончательные условия зависят от проверки дохода, долговой нагрузки и кредитной истории.'],
    ['q' => 'Что важнее при выборе кредита в ' . $city['prep'] . ': ставка или ПСК?', 'a' => 'Ставка важна, но сравнивать лучше полную стоимость кредита. ПСК помогает увидеть реальную нагрузку с учётом обязательных платежей и дополнительных условий, поэтому именно она полезнее для честного сравнения предложений.'],
    ['q' => 'Можно ли получить кредит без справок о доходах?', 'a' => 'Да, такие предложения встречаются, но условия могут быть менее выгодными: ниже сумма, выше ставка или строже требования к заёмщику. Перед подачей заявки важно проверить, какие документы банк всё же может запросить дополнительно.'],
    ['q' => 'Как повысить шансы на одобрение кредита в ' . $city['prep'] . '?', 'a' => 'Указывайте достоверные данные, не завышайте желаемую сумму, проверяйте кредитную историю заранее и выбирайте продукты, соответствующие вашему доходу. Также помогает отсутствие текущих просрочек и умеренная долговая нагрузка.'],
    ['q' => 'Можно ли погасить кредит досрочно?', 'a' => 'Как правило, да. Но порядок досрочного погашения зависит от банка: иногда нужно заранее подать заявление или выбрать между уменьшением срока и уменьшением платежа. Эти условия лучше уточнить до оформления кредита.'],
    ['q' => 'Сколько обычно рассматривают заявку на кредит?', 'a' => 'Предварительное решение может прийти за несколько минут, но окончательная проверка иногда занимает дольше. Срок зависит от суммы кредита, банка, полноты документов и необходимости дополнительной верификации клиента.'],
];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= e($citySeo['seo_h1'] ?? "Кредиты в {$city['prep']}") ?></h1>
    <p class="text-gray-600 mb-8"><?= count($offers) ?> предложений</p>
    <?php if (!empty($cityTags)): ?>
    <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Популярные подборки кредитов в <?= e($city['prep']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($cityTags as $tag): ?>
            <a href="/kredity/<?= e($city['slug']) ?>/type/<?= e($tag['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-sm hover:bg-blue-100 transition-colors"><?php if (!empty($tag['icon'])): ?><span><?= $tag['icon'] ?></span><?php endif; ?><?= e($tag['title']) ?> в <?= e($city['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <?php if (!empty($citySeo['seo_text'])): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-8">
        <div class="prose prose-sm text-gray-600 max-w-none"><?= autoLinkText($citySeo['seo_text'], 8) ?></div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по кредитам в <?= e($city['prep']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($cityCreditFaqs as $faq): ?>
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
        <h2 class="text-lg font-bold text-gray-900 mb-4">Кредиты в других городах</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="/kredity/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Кредиты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
// Допзапросы для кредитов с учётом города
echo renderSubcategoryLinks('credits', $city['slug'], null, "Кредиты в {$city['prep']} — популярные запросы");
?>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($cityCreditFaqs),
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
        'name' => 'Кредиты — предложения банков',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/kredity/' . $citySlug;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

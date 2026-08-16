<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден — займы'; $metaDescription='Страница займов для выбранного города не найдена.'; $pageHeadHtml = '<meta name="robots" content="noindex,follow">'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$GLOBALS['current_city_context'] = $city;
$GLOBALS['current_city_context_type'] = 'city';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'microloans' ORDER BY sort_order ASC")->fetchAll();
$cityTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'microloans' ORDER BY sort_order ASC LIMIT 8")->fetchAll();
$year = date('Y');

$citySeoMeta = getCitySeoText($city, 'microloans');
$pageTitle = ($citySeoMeta['meta_title'] ?? '') ?: (($citySeoMeta['seo_h1'] ?? '') ?: "Займы в {$city['prep']} — Взять микрозайм онлайн на карту | " . SITE_NAME);
$metaDescription = ($citySeoMeta['meta_description'] ?? '') ?: "Займы в {$city['prep']} на карту онлайн. Сравните " . count($offers) . " предложений от МФО.";
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Займы', '/zajmy'), breadcrumbItem('Займы в ' . $city['prep'], '/zajmy/' . $citySlug)];
$metaKeywords = "займ в {$city['prep']}, микрозайм {$city['name']}, деньги в долг {$city['name']}, займ на карту {$city['name']}";
$cityLoanFaqs = [
    ['q' => 'Можно ли получить займ в ' . $city['prep'] . ' полностью онлайн?', 'a' => 'Да, большинство МФО позволяют оформить заявку полностью онлайн: заполнить анкету, дождаться решения и получить деньги на карту без посещения офиса. Важно заранее проверить требования к заёмщику, лимиты по сумме и условия возврата.'],
    ['q' => 'Сколько времени занимает одобрение займа в ' . $city['prep'] . '?', 'a' => 'Решение по микрозайму часто принимается за 5–15 минут. Точное время зависит от МФО, корректности анкеты, кредитной истории и необходимости дополнительной проверки данных.'],
    ['q' => 'Можно ли получить первый займ под 0% в ' . $city['prep'] . '?', 'a' => 'Да, некоторые МФО предлагают новым клиентам первый займ без процентов на ограниченный срок. Перед оформлением важно уточнить максимальную сумму, дату возврата и условия, при которых льготная ставка действительно сохраняется.'],
    ['q' => 'На что смотреть при выборе займа в ' . $city['prep'] . '?', 'a' => 'Сравнивайте не только рекламную ставку, но и ПСК, срок займа, допустимую сумму, наличие льготного периода, требования к клиенту и условия продления. Чем прозрачнее правила возврата, тем легче избежать лишней переплаты.'],
    ['q' => 'Могут ли отказать в займе даже при онлайн-заявке?', 'a' => 'Да, решение принимает МФО на основе внутреннего скоринга. Частые причины отказа: ошибки в анкете, высокая долговая нагрузка, просрочки в кредитной истории или несоответствие возрастным требованиям компании.'],
    ['q' => 'Можно ли подать несколько заявок сразу?', 'a' => 'Да, многие пользователи сравнивают 2–3 предложения одновременно, чтобы повысить шанс одобрения и выбрать лучший вариант. При этом важно не отправлять слишком много заявок подряд без необходимости, чтобы не создавать лишнюю нагрузку на кредитный профиль.'],
];

// Соседние города для перелинковки
$nearbyCities = array_filter($cities, fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <h1 class="text-3xl font-bold text-gray-900 mb-3">Займы в <?= e($city['prep']) ?> на карту онлайн</h1>
    <p class="text-gray-600 text-lg mb-8">Получите займ в <?= e($city['prep']) ?> за 15 минут. Сравните <?= count($offers) ?> предложений от проверенных МФО. Первый займ под 0%!</p>

    <?php if (!empty($cityTags)): ?>
    <div class="bg-blue-50 rounded-xl p-6 mb-8 border border-blue-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Популярные подборки в <?= e($city['prep']) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($cityTags as $tag): ?>
            <a href="/zajmy/<?= e($city['slug']) ?>/type/<?= e($tag['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-700 px-3 py-1.5 rounded-lg text-sm hover:bg-blue-100 transition-colors"><?php if (!empty($tag['icon'])): ?><span><?= $tag['icon'] ?></span><?php endif; ?><?= e($tag['title']) ?> в <?= e($city['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Преимущества -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 text-center border border-gray-100"><span class="text-2xl">⚡</span><p class="font-semibold mt-2">За 15 минут</p><p class="text-xs text-gray-500">Быстрое решение</p></div>
        <div class="bg-white rounded-xl p-4 text-center border border-gray-100"><span class="text-2xl">💳</span><p class="font-semibold mt-2">На карту</p><p class="text-xs text-gray-500">Любой банк РФ</p></div>
        <div class="bg-white rounded-xl p-4 text-center border border-gray-100"><span class="text-2xl">🎁</span><p class="font-semibold mt-2">Под 0%</p><p class="text-xs text-gray-500">Первый займ</p></div>
        <div class="bg-white rounded-xl p-4 text-center border border-gray-100"><span class="text-2xl">✅</span><p class="font-semibold mt-2">Без отказа</p><p class="text-xs text-gray-500">Высокое одобрение</p></div>
    </div>

    <h2 class="text-xl font-bold text-gray-900 mb-4">МФО, выдающие займы в <?= e($city['prep']) ?></h2>
    <div class="grid gap-4 mb-8">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <!-- SEO текст (из БД) -->
    <?php $citySeo = getOrGenerateCitySeo($city, 'microloans'); ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
        <div class="prose prose-sm text-gray-600 max-w-none">
            <?= autoLinkText($citySeo['seo_text'], 8) ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mb-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">FAQ по займам в <?= e($city['prep']) ?></h2>
        <div class="space-y-4">
            <?php foreach ($cityLoanFaqs as $faq): ?>
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

    <!-- Перелинковка -->
    <div class="bg-gray-50 rounded-xl p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Займы в других городах России</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="/zajmy/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Займы в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
            <a href="/zajmy" class="inline-block bg-primary text-white px-3 py-1.5 rounded-lg text-sm hover:bg-primary-dark transition-colors">Все займы →</a>
        </div>
    </div>
</div>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb($breadcrumbs),
    jsonLdFAQ($cityLoanFaqs),
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
        'name' => 'Займы — предложения МФО',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/zajmy/' . $citySlug;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

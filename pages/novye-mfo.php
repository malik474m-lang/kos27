<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();

$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'microloans' ORDER BY created_at DESC, sort_order ASC")->fetchAll();
$latestDate = $offers && !empty($offers[0]['created_at']) ? date('d.m.Y', strtotime($offers[0]['created_at'])) : null;

$pageTitle = 'Новые МФО — последние добавленные займы | ' . SITE_NAME;
$metaDescription = 'Новые МФО и последние добавленные предложения по займам на сайте Космозайм. Сравните условия, ставки, суммы и сроки оформления онлайн.';
$metaKeywords = 'новые мфо, новые займы, последние мфо, новые микрозаймы, новые предложения мфо';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Займы', '/zajmy'), breadcrumbItem('Новые МФО', '/novye-mfo')];

$shuffledCities = getCities();
shuffle($shuffledCities);
$shuffledCities = array_slice($shuffledCities, 0, 12);

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <div class="bg-white rounded-2xl border border-gray-100 p-8 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.12em] text-blue-600">Подборка</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-bold text-gray-900">Новые МФО</h1>
                <p class="mt-3 text-gray-600 max-w-3xl">На этой странице собраны последние добавленные микрофинансовые организации и свежие предложения по займам. Сравнивайте условия, лимиты, льготные периоды и выбирайте подходящий вариант.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 w-full lg:w-auto lg:min-w-[240px]">
                <div class="rounded-xl bg-blue-50 p-4 border border-blue-100">
                    <p class="text-xs uppercase tracking-wide text-blue-500">Всего МФО</p>
                    <p class="mt-1 text-2xl font-bold text-blue-700"><?= count($offers) ?></p>
                </div>
                <div class="rounded-xl bg-green-50 p-4 border border-green-100">
                    <p class="text-xs uppercase tracking-wide text-green-500">Обновление</p>
                    <p class="mt-1 text-sm font-bold text-green-700"><?= $latestDate ?: '—' ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-gray-100 p-10 text-center">
        <p class="text-gray-500 text-lg">Новые предложения пока не найдены</p>
        <p class="text-gray-400 text-sm mt-2">Добавьте активные офферы в админке, и они появятся на этой странице автоматически.</p>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-10">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Как мы обновляем список новых МФО</h2>
        <div class="prose prose-sm text-gray-600 max-w-none">
            <p>Страница формируется автоматически из активных офферов категории «Займы» и сортируется по дате добавления. Чем свежее предложение в базе, тем выше оно показывается в списке.</p>
            <p>Перед оформлением займа обязательно изучайте полную стоимость кредита, сроки возврата и условия пролонгации. Для новых клиентов многие МФО предлагают беспроцентный период на первый займ.</p>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Популярные страницы по займам</h2>
        <div class="flex flex-wrap gap-2">
            <a href="/zajmy" class="inline-flex items-center gap-1 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">💵 Все займы</a>
            <a href="/calculator" class="inline-flex items-center gap-1 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">🧮 Калькулятор</a>
            <a href="/compare" class="inline-flex items-center gap-1 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">⚖️ Сравнение</a>
            <?php foreach ($shuffledCities as $c): ?>
            <a href="/zajmy/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Займы в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([
        ['name' => 'Главная', 'url' => '/'],
        ['name' => 'Займы', 'url' => '/zajmy'],
        ['name' => 'Новые МФО', 'url' => '/novye-mfo'],
    ]),
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
        'name' => 'Новые МФО — свежие предложения',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . '/novye-mfo';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

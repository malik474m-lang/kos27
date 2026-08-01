<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/sticky-cta.php';
require_once __DIR__ . '/../data/cities.php';

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();
$creditTags = $db->query("SELECT * FROM offer_tags WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = 'Кредиты онлайн — Сравнение банковских кредитов | ' . SITE_NAME;
$metaDescription = 'Сравните условия банковских кредитов. Низкие ставки, удобное оформление, быстрое одобрение.';

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Кредиты</nav>
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
</div>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Кредиты','url'=>'/kredity']]),
];
$canonicalUrl = SITE_URL . '/kredity';
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

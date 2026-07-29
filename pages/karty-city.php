<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category IN ('credit_cards','debit_cards') ORDER BY sort_order ASC")->fetchAll();
$citySeo = getOrGenerateCitySeo($city, 'credit_cards');
$nearbyCities = array_filter($cities, fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

$pageTitle = ($citySeo['meta_title'] ?? '') ?: (($citySeo['seo_h1'] ?? '') ?: "Банковские карты в {$city['prep']} — Космозайм");
$metaDescription = ($citySeo['meta_description'] ?? '') ?: "Банковские карты в {$city['prep']}. Сравните предложения.";

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Карты → <?= e($city['name']) ?></nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= e($citySeo['seo_h1'] ?? "Банковские карты в {$city['prep']}") ?></h1>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <?php if (!empty($citySeo['seo_text'])): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-8">
        <div class="prose prose-sm text-gray-600 max-w-none"><?= autoLinkText($citySeo['seo_text'], 8) ?></div>
    </div>
    <?php endif; ?>

    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Карты в других городах</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($nearbyCities as $c): ?>
            <a href="/karty/<?= $c['slug'] ?>" class="inline-block bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors">Карты в <?= e($c['prep']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Карты','url'=>'/karty/kreditnye'],['name'=>'Карты в '.$city['prep'],'url'=>'/karty/'.$citySlug]]),
];
$canonicalUrl = SITE_URL . '/karty/' . $citySlug;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

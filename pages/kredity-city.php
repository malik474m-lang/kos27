<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден — кредиты'; $metaDescription='Страница кредитов для выбранного города не найдена.'; $pageHeadHtml = '<meta name="robots" content="noindex,follow">'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'credits' ORDER BY sort_order ASC")->fetchAll();
$citySeo = getOrGenerateCitySeo($city, 'credits');
$nearbyCities = array_filter($cities, fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

$pageTitle = ($citySeo['meta_title'] ?? '') ?: (($citySeo['seo_h1'] ?? '') ?: "Кредиты в {$city['prep']} — " . SITE_NAME);
$metaDescription = ($citySeo['meta_description'] ?? '') ?: "Кредиты в {$city['prep']}. Сравните условия банков.";

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → <a href="/kredity" class="hover:text-primary">Кредиты</a> → <?= e($city['name']) ?></nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= e($citySeo['seo_h1'] ?? "Кредиты в {$city['prep']}") ?></h1>
    <p class="text-gray-600 mb-8"><?= count($offers) ?> предложений</p>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <?php if (!empty($citySeo['seo_text'])): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-8">
        <div class="prose prose-sm text-gray-600 max-w-none"><?= autoLinkText($citySeo['seo_text'], 8) ?></div>
    </div>
    <?php endif; ?>

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
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Кредиты','url'=>'/kredity'],['name'=>'Кредиты в '.$city['prep'],'url'=>'/kredity/'.$citySlug]]),
];
$canonicalUrl = SITE_URL . '/kredity/' . $citySlug;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

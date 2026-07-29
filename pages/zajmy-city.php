<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../includes/city-seo.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'microloans' ORDER BY sort_order ASC")->fetchAll();
$year = date('Y');

$citySeoMeta = getCitySeoText($city, 'microloans');
$pageTitle = ($citySeoMeta['meta_title'] ?? '') ?: (($citySeoMeta['seo_h1'] ?? '') ?: "Займы в {$city['prep']} — Взять микрозайм онлайн на карту | Космозайм");
$metaDescription = ($citySeoMeta['meta_description'] ?? '') ?: "Займы в {$city['prep']} на карту онлайн. Сравните " . count($offers) . " предложений от МФО.";
$metaKeywords = "займ в {$city['prep']}, микрозайм {$city['name']}, деньги в долг {$city['name']}, займ на карту {$city['name']}";

// Соседние города для перелинковки
$nearbyCities = array_filter($cities, fn($c) => $c['slug'] !== $citySlug);
shuffle($nearbyCities);
$nearbyCities = array_slice($nearbyCities, 0, 8);

ob_start();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a> →
        <a href="/zajmy" class="hover:text-primary">Займы</a> →
        Займы в <?= e($city['prep']) ?>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-3">Займы в <?= e($city['prep']) ?> на карту онлайн</h1>
    <p class="text-gray-600 text-lg mb-8">Получите займ в <?= e($city['prep']) ?> за 15 минут. Сравните <?= count($offers) ?> предложений от проверенных МФО. Первый займ под 0%!</p>

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
            <?= $citySeo['seo_text'] ?>
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
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Займы','url'=>'/zajmy'],['name'=>'Займы в '.$city['prep'],'url'=>'/zajmy/'.$citySlug]]),
];
$canonicalUrl = SITE_URL . '/zajmy/' . $citySlug;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

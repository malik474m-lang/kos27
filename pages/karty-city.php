<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../data/cities.php';

$city = findCityBySlug($citySlug);
if (!$city) { http_response_code(404); $pageTitle='Город не найден'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Город не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category IN ('credit_cards','debit_cards') ORDER BY sort_order ASC")->fetchAll();

$pageTitle = "Банковские карты в {$city['prep']} — Космозайм";

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Карты → <?= e($city['name']) ?></nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Банковские карты в <?= e($city['prep']) ?></h1>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

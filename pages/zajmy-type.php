<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../data/loan-types.php';

$type = findLoanTypeBySlug($typeSlug);
if (!$type) { http_response_code(404); $pageTitle='Не найдено'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Тип займа не найден</h1></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

$db = getDB();
$offers = $db->query("SELECT * FROM offers WHERE is_active = 1 AND category = 'microloans' ORDER BY sort_order ASC")->fetchAll();

$pageTitle = $type['title'] . ' — Космозайм';
$metaDescription = $type['metaDescription'];

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → <a href="/zajmy" class="hover:text-primary">Займы</a> → <?= e($type['title']) ?></nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($type['h1']) ?></h1>
    <p class="text-gray-600 mb-8"><?= e($type['description']) ?></p>

    <?php if (!empty($type['features'])): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php foreach ($type['features'] as $f): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <span class="text-2xl block mb-2"><?= $f['icon'] ?></span>
            <p class="font-semibold text-sm"><?= e($f['title']) ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= e($f['text']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>

    <?php if (!empty($type['content'])): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-8 mt-8 prose max-w-none text-gray-700">
        <?= nl2br(e($type['content'])) ?>
    </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

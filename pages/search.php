<?php
require_once __DIR__ . '/../includes/offer-card.php';

$q = trim($_GET['q'] ?? '');
$db = getDB();
$results = [];

if ($q) {
    $stmt = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND (title LIKE ? OR description LIKE ?) ORDER BY sort_order ASC");
    $like = "%$q%";
    $stmt->execute([$like, $like]);
    $results = $stmt->fetchAll();
}

$pageTitle = $q ? "Поиск: $q — Космозайм" : 'Поиск — Космозайм';

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Поиск</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Поиск предложений</h1>
    <form method="GET" action="/search" class="flex gap-2 mb-8">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Поиск..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary" autofocus>
        <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-primary-dark">Найти</button>
    </form>
    <?php if ($q): ?>
        <p class="text-gray-600 mb-6">Результатов: <?= count($results) ?></p>
        <?php if ($results): ?>
        <div class="grid gap-4">
            <?php foreach ($results as $offer): echo renderOfferCard($offer); endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-gray-500 text-center py-12">Ничего не найдено по запросу «<?= e($q) ?>»</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Поиск','url'=>'/search']]),
];
$canonicalUrl = SITE_URL . '/search';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

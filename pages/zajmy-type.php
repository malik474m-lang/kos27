<?php
require_once __DIR__ . '/../includes/offer-card.php';
require_once __DIR__ . '/../includes/autolinks.php';

$db = getDB();

// Загружаем тег из БД
$typeStmt = $db->prepare("SELECT * FROM offer_tags WHERE slug = ? AND is_active = 1 LIMIT 1");
$typeStmt->execute([$typeSlug]);
$type = $typeStmt->fetch();

if (!$type) { http_response_code(404); $pageTitle='Не найдено'; ob_start(); echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Тип предложения не найден</h1><a href="/zajmy" class="text-primary hover:underline mt-4 inline-block">← Все займы</a></div>'; $content=ob_get_clean(); require __DIR__.'/../includes/layout.php'; return; }

// Сначала пробуем загрузить ПРИВЯЗАННЫЕ офферы
$linkedStmt = $db->prepare("SELECT o.* FROM offers o JOIN offer_tag_links l ON o.id = l.offer_id WHERE l.tag_id = ? AND o.is_active = 1 ORDER BY o.sort_order ASC");
$linkedStmt->execute([$type['id']]);
$offers = $linkedStmt->fetchAll();

// Если привязок нет — показываем все офферы категории (fallback)
if (!$offers) {
    $allStmt = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
    $allStmt->execute([$type['category']]);
    $offers = $allStmt->fetchAll();
    $showingAll = true;
} else {
    $showingAll = false;
}

// Другие теги той же категории для перелинковки
$otherTags = $db->prepare("SELECT * FROM offer_tags WHERE is_active = 1 AND category = ? AND id != ? ORDER BY sort_order ASC");
$otherTags->execute([$type['category'], $type['id']]);
$otherTags = $otherTags->fetchAll();

// Фичи из JSON
$features = [];
if (!empty($type['features'])) {
    $features = json_decode($type['features'], true) ?: [];
}

$pageTitle = ($type['meta_title'] ?: ($type['h1'] ?: $type['title'])) . ' — ' . SITE_NAME;
$metaDescription = $type['meta_description'] ?: $type['description'];

// URL категории
$catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
$catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
$catUrl = $catUrls[$type['category']] ?? '/zajmy';
$catLabel = $catLabels[$type['category']] ?? 'Предложения';

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a> →
        <a href="<?= $catUrl ?>" class="hover:text-primary"><?= $catLabel ?></a> →
        <?= e($type['title']) ?>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= e($type['h1'] ?: $type['title']) ?></h1>
    <?php if ($type['description']): ?>
    <p class="text-gray-600 mb-8"><?= e($type['description']) ?></p>
    <?php endif; ?>

    <?php if ($features): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <?php foreach ($features as $f): ?>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
            <span class="text-2xl block mb-2"><?= $f['icon'] ?? '📌' ?></span>
            <p class="font-semibold text-sm"><?= e($f['title'] ?? '') ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= e($f['text'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <p class="text-gray-500 mb-4"><?= count($offers) ?> предложений</p>

    <?php if ($offers): ?>
    <div class="grid gap-4">
        <?php foreach ($offers as $offer): echo renderOfferCard($offer); endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-12 bg-white rounded-xl">
        <p class="text-gray-500">Предложения не найдены</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($type['content'])): ?>
    <div class="bg-white rounded-xl border border-gray-100 p-8 mt-8 prose max-w-none text-gray-700">
        <?= autoLinkText($type['content'], 8) ?>
    </div>
    <?php endif; ?>

    <!-- Другие теги -->
    <?php if ($otherTags): ?>
    <div class="bg-gray-50 rounded-xl p-6 mt-8">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Смотрите также</h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($otherTags as $ot): ?>
            <a href="<?= $catUrl ?>/type/<?= e($ot['slug']) ?>" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-sm hover:border-blue-500 hover:text-primary transition-colors"><?php if ($ot['icon']): ?><span><?= $ot['icon'] ?></span><?php endif; ?><?= e($ot['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>$catLabel,'url'=>$catUrl],['name'=>$type['title'],'url'=>$catUrl.'/type/'.$type['slug']]]),
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
        'name' => 'Подборка предложений',
        'numberOfItems' => count($_ilItems),
        'itemListElement' => $_ilItems,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
$canonicalUrl = SITE_URL . $catUrl . '/type/' . $type['slug'];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

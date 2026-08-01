<?php
$db = getDB();
$articles = $db->query("SELECT * FROM articles WHERE is_published = 1 ORDER BY created_at DESC")->fetchAll();

$pageTitle = 'Полезные статьи — ' . SITE_NAME;
$metaDescription = 'Статьи о займах, кредитах и финансовой грамотности.';

ob_start();
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Статьи</nav>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Полезные статьи</h1>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($articles as $a):
            $cover = normalizeMediaUrl($a['cover_image'] ?? '');
        ?>
        <a href="/articles/<?= e($a['slug']) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover block">
            <?php if ($cover): ?>
            <div class="h-40 bg-gray-100"><img src="<?= e($cover) ?>" alt="<?= e($a['title']) ?>" class="w-full h-full object-cover" loading="lazy"></div>
            <?php endif; ?>
            <div class="p-5">
                <h2 class="font-bold text-gray-900 mb-2 line-clamp-2"><?= e($a['title']) ?></h2>
                <?php if ($a['excerpt']): ?>
                <p class="text-sm text-gray-600 line-clamp-3"><?= e($a['excerpt']) ?></p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-3"><?= date('d.m.Y', strtotime($a['created_at'])) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>


    <!-- Навигация -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-12">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Разделы сайта</h2>
        <div class="flex flex-wrap gap-3">
            <a href="/zajmy" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">💵 Займы онлайн</a>
            <a href="/kredity" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">🏦 Кредиты</a>
            <a href="/karty/kreditnye" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">💳 Кредитные карты</a>
            <a href="/karty/debetovye" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">🪪 Дебетовые карты</a>
            <a href="/calculator" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">🧮 Калькулятор</a>
            <a href="/faq" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">❓ FAQ</a>
            <a href="/glossary" class="inline-flex items-center gap-2 bg-blue-50 text-primary px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors">📖 Глоссарий</a>
        </div>
    </div>

</section>
<?php
$jsonLdSchemas = [
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Статьи','url'=>'/articles']]),
];
$canonicalUrl = SITE_URL . '/articles';
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

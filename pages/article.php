<?php
require_once __DIR__ . '/../includes/autolinks.php';

$db = getDB();
$stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$articleSlug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Статья не найдена';
    ob_start();
    echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center"><h1 class="text-2xl font-bold">Статья не найдена</h1><a href="/articles" class="btn-primary inline-block mt-4">Все статьи</a></div>';
    $content = ob_get_clean();
    require __DIR__ . '/../includes/layout.php';
    return;
}

$pageTitle = $article['meta_title'] ?: ($article['title'] . ' — ' . SITE_NAME);
$metaDescription = $article['meta_description'] ?: ($article['excerpt'] ?: '');
$cover = normalizeMediaUrl($article['cover_image'] ?? '');

ob_start();
?>
<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="/" class="hover:text-primary">Главная</a> → 
        <a href="/articles" class="hover:text-primary">Статьи</a> → 
        <?= e($article['title']) ?>
    </nav>

    <?php if ($cover): ?>
    <div class="rounded-2xl overflow-hidden mb-8 max-h-96">
        <img src="<?= e($cover) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover" loading="lazy">
    </div>
    <?php endif; ?>

    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4"><?= e($article['title']) ?></h1>
    <div class="flex items-center gap-3 text-sm text-gray-400 mb-8">
        <time datetime="<?= date('c', strtotime($article['created_at'])) ?>">📅 <?= date('d.m.Y', strtotime($article['created_at'])) ?></time>
        <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['created_at']): ?>
        <span>•</span>
        <time datetime="<?= date('c', strtotime($article['updated_at'])) ?>">✏️ Обновлено <?= date('d.m.Y', strtotime($article['updated_at'])) ?></time>
        <?php endif; ?>
    </div>

    <div class="prose prose-lg max-w-none text-gray-700">
        <?= safeAutoLink($article['content']) ?>
    </div>


    <!-- Читайте также -->
    <?php
    $otherArticles = $db->prepare("SELECT title, slug, cover_image FROM articles WHERE is_published = 1 AND id != ? ORDER BY created_at DESC LIMIT 3");
    $otherArticles->execute([$article['id']]);
    $relArticles = $otherArticles->fetchAll();
    if ($relArticles):
    ?>
    <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Читайте также</h2>
        <div class="grid sm:grid-cols-3 gap-4">
            <?php foreach ($relArticles as $ra):
                $raCover = normalizeMediaUrl($ra['cover_image'] ?? '');
            ?>
            <a href="/articles/<?= e($ra['slug']) ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden card-hover block">
                <?php if ($raCover): ?>
                <div class="h-32 bg-gray-100"><img src="<?= e($raCover) ?>" alt="<?= e($ra['title']) ?>" class="w-full h-full object-cover" loading="lazy"></div>
                <?php endif; ?>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 line-clamp-2 text-sm"><?= e($ra['title']) ?></h3>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Лучшие предложения -->
    <?php
    $topOffers = $db->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3")->fetchAll();
    if ($topOffers):
    require_once __DIR__ . '/../includes/offer-card.php';
    ?>
    <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Лучшие предложения</h2>
            <a href="/zajmy" class="text-primary hover:underline font-medium text-sm">Все предложения →</a>
        </div>
        <div class="grid gap-4">
            <?php foreach ($topOffers as $topOffer): echo renderOfferCard($topOffer); endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</article>
<?php
$jsonLdSchemas = [
    jsonLdArticle($article),
    jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Статьи','url'=>'/articles'],['name'=>$article['title'],'url'=>'/articles/'.$article['slug']]]),
];
$canonicalUrl = SITE_URL . '/articles/' . $article['slug'];
$ogImage = $cover;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

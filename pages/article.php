<?php
require_once __DIR__ . '/../includes/autolinks.php';
require_once __DIR__ . '/../includes/article-eeat.php';

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

// Проверяем наличие E-E-A-T полей (для обратной совместимости)
$hasEeatFields = isset($article['author_name']);
if (!$hasEeatFields) {
    // Дефолтные значения если миграция не выполнена
    $article['author_name'] = 'Редакция Космозайм';
    $article['author_title'] = 'Финансовый редактор';
    $article['reviewer_name'] = 'Анна Соколова';
    $article['reviewer_title'] = 'Главный редактор';
    $article['fact_checked_at'] = $article['updated_at'] ?? $article['created_at'];
    $article['sources'] = json_encode([
        ['title' => 'Банк России', 'url' => 'https://cbr.ru/'],
        ['title' => 'Реестр МФО', 'url' => 'https://cbr.ru/microfinance/registry/'],
    ]);
}

$pageTitle = $article['meta_title'] ?: pageMetaTitle($article['title']);
$metaDescription = $article['meta_description'] ?: ($article['excerpt'] ?: '');
$cover = normalizeMediaUrl($article['cover_image'] ?? '');
$relatedArticles = findRelatedArticles($article, 3);
$articleOfferContext = findRelatedOffersForArticle($article, 3);
$articleOfferCategory = $articleOfferContext['category'];
$articleOfferMeta = $articleOfferContext['meta'];
$articleTopicOffers = $articleOfferContext['offers'];
$inlineArticleOffer = !empty($articleTopicOffers) ? $articleTopicOffers[0] : null;
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Статьи', '/articles'), breadcrumbItem($article['title'], '/articles/' . $article['slug'])];
$articleContent = (string)($article['content'] ?? '');
$articleContent = preg_replace('/\x{FFFD}+/u', '', $articleContent) ?? $articleContent;
$articleHasHtml = (bool)preg_match('/<(p|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|em|a|blockquote|table|img|figure|div|br)\b/i', $articleContent);
$articleBodyHtml = $articleHasHtml
    ? autoLinkText($articleContent, 10, ['current_url' => '/articles/' . $article['slug'], 'current_article_slug' => $article['slug'], 'preferred_offer_category' => $articleOfferCategory])
    : safeAutoLink($articleContent, 10, ['current_url' => '/articles/' . $article['slug'], 'current_article_slug' => $article['slug'], 'preferred_offer_category' => $articleOfferCategory]);
$articleBodyHtml = injectInlineOfferCta($articleBodyHtml, $inlineArticleOffer, 2, $article['slug']);

ob_start();
?>
<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" itemscope itemtype="https://schema.org/Article">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <?php if ($cover): ?>
    <div class="rounded-2xl overflow-hidden mb-8 max-h-96">
        <img src="<?= e($cover) ?>" alt="<?= e($article['title']) ?>" class="w-full h-full object-cover" loading="lazy" itemprop="image">
    </div>
    <?php endif; ?>

    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4" itemprop="headline"><?= e($article['title']) ?></h1>
    
    <!-- Мета-информация и бейдж обновления -->
    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400 mb-6">
        <time datetime="<?= date('c', strtotime($article['created_at'])) ?>" itemprop="datePublished">
            📅 <?= date('d.m.Y', strtotime($article['created_at'])) ?>
        </time>
        <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['created_at']): ?>
        <span>•</span>
        <time datetime="<?= date('c', strtotime($article['updated_at'])) ?>" itemprop="dateModified">
            ✏️ Обновлено <?= date('d.m.Y', strtotime($article['updated_at'])) ?>
        </time>
        <?php endif; ?>
    </div>
    
    <!-- Бейдж "Материал обновлён редакцией" -->
    <?php 
    $updatedBadge = renderArticleUpdatedBadge($article);
    if ($updatedBadge): 
    ?>
    <div class="mb-6"><?= $updatedBadge ?></div>
    <?php endif; ?>

    <!-- Блок автора и редактора (E-E-A-T) -->
    <div class="mb-8">
        <?= renderArticleAuthorBlock($article) ?>
    </div>

    <!-- Содержание статьи -->
    <div class="prose prose-lg max-w-none text-gray-700 mb-10" itemprop="articleBody">
        <?= $articleBodyHtml ?>
    </div>

    <?php if (!empty($relatedArticles)): ?>
    <div class="mb-12 rounded-2xl border border-blue-100 bg-blue-50 p-6">
        <h2 class="mb-3 text-lg font-bold text-gray-900">По теме</h2>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($relatedArticles as $ra): ?>
            <a href="/articles/<?= e($ra['slug']) ?>" class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-medium text-primary shadow-sm ring-1 ring-blue-100 transition hover:bg-blue-100 hover:no-underline">
                <?= e($ra['title']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Блок источников и проверки фактов -->
    <div class="mb-12">
        <?= renderArticleSourcesBlock($article) ?>
    </div>
    
    <!-- Trust ссылки -->
    <div class="bg-gray-50 rounded-xl p-6 mb-12">
        <p class="text-sm text-gray-600 mb-3">Узнайте больше о том, как мы готовим материалы:</p>
        <div class="flex flex-wrap gap-4">
            <a href="/editorial-policy" class="inline-flex items-center gap-2 text-sm text-primary hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Редакционная политика
            </a>
            <a href="/sources" class="inline-flex items-center gap-2 text-sm text-primary hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Источники информации
            </a>
            <a href="/how-we-rank" class="inline-flex items-center gap-2 text-sm text-primary hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Как мы составляем рейтинг
            </a>
            <a href="/about" class="inline-flex items-center gap-2 text-sm text-primary hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                О проекте
            </a>
        </div>
    </div>


    <!-- Релевантные офферы по теме статьи -->
    <?php if (!empty($articleTopicOffers)):
    require_once __DIR__ . '/../includes/offer-card.php';
    ?>
    <div class="mt-12">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900"><?= e($articleOfferMeta['label']) ?></h2>
                <p class="text-sm text-gray-500 mt-1">Подобрали предложения, которые лучше подходят к теме этой статьи</p>
            </div>
            <a href="<?= e($articleOfferMeta['url']) ?>" class="text-primary hover:underline font-medium text-sm">Все предложения →</a>
        </div>
        <div class="grid gap-4">
            <?php foreach ($articleTopicOffers as $topOffer): echo renderOfferCard($topOffer); endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</article>
<?php
// Используем улучшенную E-E-A-T schema
$jsonLdSchemas = [
    jsonLdArticleEEAT($article),
    jsonLdBreadcrumb($breadcrumbs),
];
$canonicalUrl = pageCanonical('/articles/' . $article['slug']);
$ogImage = $cover;
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

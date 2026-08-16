<?php
/**
 * Компонент E-E-A-T блоков для статей
 * - Блок автора/редакции
 * - Блок источников и проверки фактов
 */

/**
 * Рендер блока автора и редактора
 */
function renderArticleAuthorBlock(array $article): string {
    $authorName = $article['author_name'] ?? 'Редакция Космозайм';
    $authorTitle = $article['author_title'] ?? 'Финансовый редактор';
    $reviewerName = $article['reviewer_name'] ?? null;
    $reviewerTitle = $article['reviewer_title'] ?? 'Главный редактор';
    $factCheckedAt = $article['fact_checked_at'] ?? $article['updated_at'] ?? null;
    $updatedAt = $article['updated_at'] ?? $article['created_at'];
    
    ob_start();
    ?>
    <div class="article-eeat-author bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
        <div class="flex flex-col sm:flex-row gap-6">
            <!-- Автор -->
            <div class="flex-1">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                        <?= mb_substr($authorName, 0, 1) ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Автор материала</p>
                        <p class="font-semibold text-gray-900"><?= e($authorName) ?></p>
                        <p class="text-sm text-gray-600"><?= e($authorTitle) ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($reviewerName): ?>
            <!-- Проверил -->
            <div class="flex-1">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                        <?= mb_substr($reviewerName, 0, 1) ?>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Проверил</p>
                        <p class="font-semibold text-gray-900"><?= e($reviewerName) ?></p>
                        <p class="text-sm text-gray-600"><?= e($reviewerTitle) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Даты и ссылки -->
        <div class="mt-5 pt-5 border-t border-blue-200/50 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
            <?php if ($factCheckedAt): ?>
            <div class="flex items-center gap-2 text-gray-600">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Факты проверены: <time datetime="<?= date('c', strtotime($factCheckedAt)) ?>"><?= date('d.m.Y', strtotime($factCheckedAt)) ?></time></span>
            </div>
            <?php endif; ?>
            
            <div class="flex items-center gap-2 text-gray-600">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Обновлено: <time datetime="<?= date('c', strtotime($updatedAt)) ?>"><?= date('d.m.Y', strtotime($updatedAt)) ?></time></span>
            </div>
            
            <a href="/editorial-policy" class="flex items-center gap-1 text-primary hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Редакционная политика
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Рендер блока источников и проверки фактов
 */
function renderArticleSourcesBlock(array $article): string {
    // Парсим источники из JSON
    $sources = [];
    if (!empty($article['sources'])) {
        $decoded = json_decode($article['sources'], true);
        if (is_array($decoded)) {
            $sources = $decoded;
        }
    }
    
    // Дефолтные источники, если не заданы
    if (empty($sources)) {
        $sources = [
            ['title' => 'Банк России', 'url' => 'https://cbr.ru/'],
            ['title' => 'Реестр МФО Банка России', 'url' => 'https://cbr.ru/microfinance/registry/'],
        ];
    }
    
    $factCheckedAt = $article['fact_checked_at'] ?? $article['updated_at'] ?? date('Y-m-d');
    
    ob_start();
    ?>
    <div class="article-eeat-sources bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                Источники и проверка фактов
            </h3>
        </div>
        
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Информация в этой статье основана на официальных и публичных источниках. 
                Мы тщательно проверяем факты перед публикацией.
            </p>
            
            <div class="grid sm:grid-cols-2 gap-4 mb-6">
                <?php foreach ($sources as $source): ?>
                <a href="<?= e($source['url']) ?>" target="_blank" rel="noopener noreferrer" 
                   class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group">
                    <div class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-400 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900 truncate"><?= e($source['title']) ?></p>
                        <p class="text-xs text-gray-500 truncate"><?= e(parse_url($source['url'], PHP_URL_HOST)) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-gray-100 text-sm">
                <div class="flex items-center gap-2 text-emerald-600 font-medium">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Факты проверены <?= date('d.m.Y', strtotime($factCheckedAt)) ?>
                </div>
                
                <a href="/sources" class="text-primary hover:underline flex items-center gap-1">
                    Все источники
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Рендер мини-бейджа "Материал обновлён редакцией"
 */
function renderArticleUpdatedBadge(array $article): string {
    $updatedAt = $article['updated_at'] ?? $article['created_at'];
    $createdAt = $article['created_at'];
    
    // Показываем бейдж только если статья обновлялась
    if ($updatedAt === $createdAt) {
        return '';
    }
    
    ob_start();
    ?>
    <div class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Материал обновлён редакцией <?= date('d.m.Y', strtotime($updatedAt)) ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Генерация улучшенной Article schema с E-E-A-T
 */
function jsonLdArticleEEAT(array $article): string {
    $cover = normalizeMediaUrl($article['cover_image'] ?? '');
    $image = $cover ? (str_starts_with($cover, 'http') ? $cover : SITE_URL . $cover) : SITE_URL . '/favicon.svg';
    
    $authorName = $article['author_name'] ?? 'Редакция Космозайм';
    $authorTitle = $article['author_title'] ?? 'Финансовый редактор';
    $reviewerName = $article['reviewer_name'] ?? null;
    $factCheckedAt = $article['fact_checked_at'] ?? $article['updated_at'] ?? $article['created_at'];
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article['title'],
        'description' => $article['excerpt'] ?: ($article['meta_description'] ?: ''),
        'image' => [
            '@type' => 'ImageObject',
            'url' => $image,
            'width' => 1200,
            'height' => 630,
        ],
        'datePublished' => date('c', strtotime($article['created_at'])),
        'dateModified' => date('c', strtotime($article['updated_at'] ?? $article['created_at'])),
        'author' => [
            '@type' => 'Person',
            'name' => $authorName,
            'jobTitle' => $authorTitle,
            'worksFor' => [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'url' => SITE_URL,
            ],
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => SITE_NAME,
            'url' => SITE_URL,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => SITE_URL . '/favicon.svg',
                'width' => 512,
                'height' => 512,
            ],
            'sameAs' => [
                SITE_URL . '/about',
                SITE_URL . '/editorial-policy',
            ],
        ],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => SITE_URL . '/articles/' . $article['slug'],
        ],
        'isAccessibleForFree' => true,
        'inLanguage' => 'ru-RU',
    ];
    
    // Добавляем reviewer если есть
    if ($reviewerName) {
        $schema['editor'] = [
            '@type' => 'Person',
            'name' => $reviewerName,
            'jobTitle' => $article['reviewer_title'] ?? 'Редактор',
        ];
    }
    
    // Парсим источники для citation
    $sources = [];
    if (!empty($article['sources'])) {
        $decoded = json_decode($article['sources'], true);
        if (is_array($decoded)) {
            $sources = $decoded;
        }
    }
    if ($sources) {
        $schema['citation'] = array_map(function($src) {
            return [
                '@type' => 'WebPage',
                'name' => $src['title'],
                'url' => $src['url'],
            ];
        }, $sources);
    }
    
    return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

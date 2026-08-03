<?php
/**
 * Автоматическая отправка URL на индексацию после изменений контента
 * Вызывается из API создания/обновления офферов, статей, тегов
 */

function autoSubmitUrl(string $path): void {
    $fullUrl = SITE_URL . $path;

    // Google Indexing API
    try {
        require_once __DIR__ . '/google-indexing.php';
        if (googleIndexingAvailable()) {
            googleIndexUrl($fullUrl);
        }
    } catch (Exception $e) {}

    // Yandex Webmaster API
    try {
        require_once __DIR__ . '/yandex-webmaster.php';
        if (yandexWebmasterAvailable()) {
            yandexSubmitRecrawl($fullUrl);
        }
    } catch (Exception $e) {}

    // Обновляем url_index_tracker
    try {
        $db = getDB();
        $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE last_modified = NOW()")
           ->execute([$path, detectUrlType($path), detectUrlPriority($path)]);
    } catch (Exception $e) {}

    // Ping sitemap
    pingSitemap();
}

function autoSubmitUrls(array $paths): void {
    foreach ($paths as $path) {
        autoSubmitUrl($path);
    }
}

function detectUrlType(string $path): string {
    if (str_starts_with($path, '/offer/')) return 'offer';
    if (str_starts_with($path, '/articles/')) return 'article';
    if (str_contains($path, '/type/')) return 'category';
    if (preg_match('#^/(zajmy|kredity|karty)/[a-z]#', $path)) return 'city';
    return 'static';
}

function detectUrlPriority(string $path): float {
    if ($path === '/') return 1.0;
    if (str_starts_with($path, '/offer/')) return 0.8;
    if (str_starts_with($path, '/articles/')) return 0.6;
    if (str_contains($path, '/type/')) return 0.7;
    return 0.5;
}

function pingSitemap(): void {
    static $pinged = false;
    if ($pinged) return;
    $pinged = true;

    $sitemapUrl = SITE_URL . '/sitemap.xml';

    // Google ping
    @file_get_contents('https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl), false, stream_context_create([
        'http' => ['timeout' => 3, 'method' => 'GET']
    ]));

    // Yandex ping
    @file_get_contents('https://webmaster.yandex.ru/ping?sitemap=' . urlencode($sitemapUrl), false, stream_context_create([
        'http' => ['timeout' => 3, 'method' => 'GET']
    ]));
}

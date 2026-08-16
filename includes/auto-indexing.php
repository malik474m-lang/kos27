<?php
/**
 * Автоматическая отправка URL на индексацию после изменений контента
 * Поддерживает: Google Indexing API, Yandex Webmaster, IndexNow (Yandex+Bing)
 */

function autoSubmitUrl(string $path): void {
    $fullUrl = SITE_URL . $path;

    // 1. IndexNow — мгновенное уведомление Яндекс + Bing (самый быстрый способ)
    indexNowSubmit($path);

    // 2. Google Indexing API
    try {
        require_once __DIR__ . '/google-indexing.php';
        if (googleIndexingAvailable()) {
            googleIndexUrl($fullUrl);
        }
    } catch (Exception $e) {}

    // 3. Yandex Webmaster API (переобход)
    try {
        require_once __DIR__ . '/yandex-webmaster.php';
        if (yandexWebmasterAvailable()) {
            yandexSubmitRecrawl($fullUrl);
        }
    } catch (Exception $e) {}

    // 4. Обновляем url_index_tracker
    try {
        $db = getDB();
        $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE last_modified = NOW()")
           ->execute([$path, detectUrlType($path), detectUrlPriority($path)]);
    } catch (Exception $e) {}

    // 5. Ping Yandex sitemap
    pingSitemap();
}

function autoSubmitUrls(array $paths): void {
    // IndexNow поддерживает batch — отправляем все разом
    indexNowSubmitBatch($paths);

    foreach ($paths as $path) {
        $fullUrl = SITE_URL . $path;

        try {
            require_once __DIR__ . '/google-indexing.php';
            if (googleIndexingAvailable()) {
                googleIndexUrl($fullUrl);
                usleep(100000); // 100ms для Google rate limit
            }
        } catch (Exception $e) {}

        try {
            require_once __DIR__ . '/yandex-webmaster.php';
            if (yandexWebmasterAvailable()) {
                yandexSubmitRecrawl($fullUrl);
                usleep(150000);
            }
        } catch (Exception $e) {}

        try {
            $db = getDB();
            $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority)
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE last_modified = NOW()")
               ->execute([$path, detectUrlType($path), detectUrlPriority($path)]);
        } catch (Exception $e) {}
    }

    pingSitemap();
}

// ==================== IndexNow ====================

function getIndexNowKey(): string {
    $keyFile = __DIR__ . '/../data/indexnow-key.txt';
    if (file_exists($keyFile)) {
        $key = trim(file_get_contents($keyFile));
        if ($key) return $key;
    }
    // Генерируем ключ при первом вызове
    $key = bin2hex(random_bytes(16));
    @file_put_contents($keyFile, $key);
    return $key;
}

/**
 * Отправить один URL через IndexNow
 */
function indexNowSubmit(string $path): bool {
    $key = getIndexNowKey();
    $fullUrl = SITE_URL . $path;
    $host = parse_url(SITE_URL, PHP_URL_HOST);

    // Отправляем в Yandex IndexNow
    $url = 'https://yandex.com/indexnow?' . http_build_query([
        'url' => $fullUrl,
        'key' => $key,
    ]);

    $result = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 5, 'method' => 'GET', 'ignore_errors' => true]
    ]));

    // Отправляем в Bing IndexNow
    @file_get_contents('https://www.bing.com/indexnow?' . http_build_query([
        'url' => $fullUrl,
        'key' => $key,
    ]), false, stream_context_create([
        'http' => ['timeout' => 5, 'method' => 'GET', 'ignore_errors' => true]
    ]));

    return $result !== false;
}

/**
 * Пакетная отправка через IndexNow (до 10000 URL за раз)
 */
function indexNowSubmitBatch(array $paths): array {
    if (empty($paths)) return ['success' => 0, 'total' => 0];

    $key = getIndexNowKey();
    $host = parse_url(SITE_URL, PHP_URL_HOST);
    $fullUrls = array_map(fn($p) => SITE_URL . $p, array_slice($paths, 0, 10000));

    $payload = json_encode([
        'host' => $host,
        'key' => $key,
        'keyLocation' => SITE_URL . '/' . $key . '.txt',
        'urlList' => $fullUrls,
    ]);

    $success = 0;

    // Yandex IndexNow batch
    $ch = curl_init('https://yandex.com/indexnow');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) $success++;

    // Bing IndexNow batch
    $ch = curl_init('https://www.bing.com/indexnow');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);

    return ['success' => $success, 'total' => count($fullUrls)];
}

// ==================== Утилиты ====================

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

    // Yandex sitemap ping
    @file_get_contents('https://webmaster.yandex.ru/ping?sitemap=' . urlencode($sitemapUrl), false, stream_context_create([
        'http' => ['timeout' => 3, 'method' => 'GET']
    ]));
}

<?php
/**
 * Проверка пропавших страниц — сверяет sitemap с реальными роутами
 * Оптимизировано: пакетная проверка с лимитом, чтобы не вызывать таймаут
 */
requireAdmin();

$db = getDB();
$action = $_GET['action'] ?? 'check';

require_once __DIR__ . '/../../data/cities.php';
require_once __DIR__ . '/../../includes/subcategories.php';

$catUrls = [
    'microloans' => '/zajmy',
    'credits' => '/kredity',
    'credit_cards' => '/karty/kreditnye',
    'debit_cards' => '/karty/debetovye'
];

switch ($action) {

case 'check':
    // Пакетная проверка: offset + limit (по умолчанию 30 URL за раз)
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 30)));
    
    $allUrls = getAllSitemapUrls();
    $total = count($allUrls);
    $batch = array_slice($allUrls, $offset, $limit);
    
    $ok = 0;
    $broken = [];
    
    foreach ($batch as $url) {
        $status = checkUrlInternal($url);
        if ($status >= 200 && $status < 400) {
            $ok++;
        } else {
            $broken[] = ['url' => $url, 'status' => $status];
        }
    }
    
    $hasMore = ($offset + $limit) < $total;
    
    echo json_encode([
        'total' => $total,
        'offset' => $offset,
        'limit' => $limit,
        'checked' => count($batch),
        'ok' => $ok,
        'broken_count' => count($broken),
        'broken' => $broken,
        'has_more' => $hasMore,
        'next_offset' => $hasMore ? $offset + $limit : null,
    ]);
    break;

case 'check-sample':
    // Быстрая проверка выборки ключевых URL
    $urls = getSampleUrls();
    $broken = [];
    
    foreach ($urls as $url) {
        $status = checkUrlInternal($url);
        if ($status < 200 || $status >= 400) {
            $broken[] = ['url' => $url, 'status' => $status];
        }
    }
    
    echo json_encode([
        'total' => count($urls),
        'ok' => count($urls) - count($broken),
        'broken_count' => count($broken),
        'broken' => $broken
    ]);
    break;

case 'check-url':
    // Проверка одного URL
    $url = $_GET['url'] ?? '';
    if (!$url) { echo json_encode(['error' => 'url required']); exit; }
    
    $fullUrl = SITE_URL . $url;
    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'KosmoEngine PageChecker/1.0'
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    echo json_encode([
        'url' => $url,
        'full_url' => $fullUrl,
        'status' => $httpCode,
        'final_url' => $finalUrl,
        'is_redirect' => ($finalUrl !== $fullUrl)
    ]);
    break;

case 'count':
    // Быстро вернуть количество URL без проверки
    echo json_encode(['total' => count(getAllSitemapUrls())]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

/**
 * Проверить URL через HTTP HEAD запрос к самому себе
 */
function checkUrlInternal(string $path): int {
    $fullUrl = SITE_URL . $path;
    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'KosmoEngine PageChecker/1.0'
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code ?: 0;
}

/**
 * Получить все URL из sitemap-логики
 */
function getAllSitemapUrls(): array {
    global $db;
    $urls = [];
    $cities = getCities();
    
    // Статические
    $static = ['/','/zajmy','/kredity','/karty/kreditnye','/karty/debetovye','/novye-mfo',
               '/calculator','/compare','/articles','/faq','/glossary'];
    $urls = array_merge($urls, $static);
    
    // Офферы
    $offers = $db->query("SELECT slug FROM offers WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($offers as $s) { $urls[] = "/offer/{$s}"; }
    
    // Статьи
    $articles = $db->query("SELECT slug FROM articles WHERE is_published = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($articles as $s) { $urls[] = "/articles/{$s}"; }
    
    // Теги
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
    $tags = $db->query("SELECT slug, category FROM offer_tags WHERE is_active = 1")->fetchAll();
    foreach ($tags as $t) {
        $base = $catUrls[$t['category']] ?? '/zajmy';
        $urls[] = "{$base}/type/{$t['slug']}";
    }
    
    // Допзапросы (подкатегории)
    try {
        $subcats = $db->query("SELECT slug, category FROM subcategories WHERE is_active = 1")->fetchAll();
        foreach ($subcats as $sc) {
            $base = $catUrls[$sc['category']] ?? '/zajmy';
            $urls[] = "{$base}/q/{$sc['slug']}";
        }
    } catch (Exception $e) {}
    
    // Города (только основные категории, без перемножения на теги/допзапросы)
    foreach ($cities as $c) {
        $urls[] = "/zajmy/{$c['slug']}";
        $urls[] = "/kredity/{$c['slug']}";
        $urls[] = "/karty/{$c['slug']}";
        $urls[] = "/karty/kreditnye/{$c['slug']}";
        $urls[] = "/karty/debetovye/{$c['slug']}";
    }
    
    return $urls;
}

/**
 * Выборка ключевых URL для быстрой проверки
 */
function getSampleUrls(): array {
    global $db;
    $urls = ['/','/zajmy','/kredity','/karty/kreditnye','/karty/debetovye','/articles','/calculator'];
    
    // Первые 3 оффера
    $offers = $db->query("SELECT slug FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($offers as $s) { $urls[] = "/offer/{$s}"; }
    
    // Первые 2 города
    $cities = array_slice(getCities(), 0, 2);
    foreach ($cities as $c) {
        $urls[] = "/zajmy/{$c['slug']}";
        $urls[] = "/kredity/{$c['slug']}";
    }
    
    // Первый допзапрос (если есть)
    try {
        $sc = $db->query("SELECT slug, category FROM subcategories WHERE is_active = 1 LIMIT 1")->fetch();
        if ($sc) {
            $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
            $base = $catUrls[$sc['category']] ?? '/zajmy';
            $urls[] = "{$base}/q/{$sc['slug']}";
        }
    } catch (Exception $e) {}
    
    return $urls;
}

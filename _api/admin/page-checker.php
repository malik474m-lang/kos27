<?php
/**
 * Проверка пропавших страниц — сверяет sitemap с реальными роутами
 */
requireAdmin();

$db = getDB();
$action = $_GET['action'] ?? 'check';

require_once __DIR__ . '/../../data/cities.php';

$catUrls = [
    'microloans' => '/zajmy',
    'credits' => '/kredity',
    'credit_cards' => '/karty/kreditnye',
    'debit_cards' => '/karty/debetovye'
];

switch ($action) {

case 'check':
    $results = ['ok' => [], 'broken' => [], 'total' => 0];
    $checkUrl = $_GET['url'] ?? '';
    
    // Если передан конкретный URL — проверяем его
    if ($checkUrl) {
        $status = checkUrlInternal($checkUrl);
        echo json_encode(['url' => $checkUrl, 'status' => $status]);
        exit;
    }
    
    // Полная проверка всех URL из sitemap
    $urls = getAllSitemapUrls();
    $results['total'] = count($urls);
    
    foreach ($urls as $url) {
        $status = checkUrlInternal($url);
        if ($status === 200) {
            $results['ok'][] = $url;
        } else {
            $results['broken'][] = ['url' => $url, 'status' => $status];
        }
    }
    
    echo json_encode([
        'total' => $results['total'],
        'ok' => count($results['ok']),
        'broken_count' => count($results['broken']),
        'broken' => $results['broken']
    ]);
    break;

case 'check-sample':
    // Быстрая проверка выборки URL (не все, а ключевые)
    $urls = getSampleUrls();
    $broken = [];
    
    foreach ($urls as $url) {
        $status = checkUrlInternal($url);
        if ($status !== 200) {
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
    $tags = $db->query("SELECT slug, category FROM offer_tags WHERE is_active = 1")->fetchAll();
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
    foreach ($tags as $t) {
        $base = $catUrls[$t['category']] ?? '/zajmy';
        $urls[] = "{$base}/type/{$t['slug']}";
    }
    
    // Города
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
    
    // Первые 3 города по каждой категории
    $cities = array_slice(getCities(), 0, 3);
    foreach ($cities as $c) {
        $urls[] = "/zajmy/{$c['slug']}";
        $urls[] = "/kredity/{$c['slug']}";
        $urls[] = "/karty/kreditnye/{$c['slug']}";
        $urls[] = "/karty/debetovye/{$c['slug']}";
    }
    
    return $urls;
}

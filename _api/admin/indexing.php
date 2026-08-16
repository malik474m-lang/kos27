<?php
/**
 * API для модуля индексации
 */
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';

// Убедимся что таблицы существуют
try {
    $db->query("SELECT 1 FROM url_index_tracker LIMIT 1");
} catch (Exception $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS `url_index_tracker` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `url` varchar(500) NOT NULL,
        `url_type` enum('offer','article','city','city_tag','category','static') NOT NULL DEFAULT 'static',
        `first_seen` timestamp NULL DEFAULT current_timestamp(),
        `last_modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `content_hash` varchar(64) DEFAULT NULL,
        `indexed_yandex` tinyint(1) NOT NULL DEFAULT 0,
        `indexed_google` tinyint(1) NOT NULL DEFAULT 0,
        `submitted_yandex` timestamp NULL DEFAULT NULL,
        `submitted_google` timestamp NULL DEFAULT NULL,
        `priority` decimal(2,1) NOT NULL DEFAULT 0.5,
        `changefreq` enum('always','hourly','daily','weekly','monthly','yearly','never') NOT NULL DEFAULT 'weekly',
        PRIMARY KEY (`id`),
        UNIQUE KEY `url` (`url`),
        KEY `idx_type` (`url_type`),
        KEY `idx_last_modified` (`last_modified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS `indexing_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `service` enum('yandex','google','bing') NOT NULL,
        `action` enum('submit','reindex','check') NOT NULL DEFAULT 'submit',
        `urls_count` int(11) NOT NULL DEFAULT 0,
        `urls_success` int(11) NOT NULL DEFAULT 0,
        `status` enum('success','partial','error') NOT NULL DEFAULT 'success',
        `response` text DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_service_date` (`service`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

// Добавляем колонки если их нет
try { $db->query("SELECT content_hash FROM url_index_tracker LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE url_index_tracker ADD COLUMN content_hash VARCHAR(64) DEFAULT NULL"); } catch (Exception $e2) {} }
try { $db->query("SELECT urls_success FROM indexing_log LIMIT 1"); }
catch (Exception $e) { try { $db->exec("ALTER TABLE indexing_log ADD COLUMN urls_success INT NOT NULL DEFAULT 0 AFTER urls_count"); } catch (Exception $e2) {} }

/**
 * Синхронизирует URL из БД в url_index_tracker.
 * НЕ обновляет last_modified если контент не менялся (по content_hash).
 */
function syncUrlsFromDb(): array {
    global $db;
    require_once __DIR__ . '/../../data/cities.php';
    $stats = ['added' => 0, 'updated' => 0, 'unchanged' => 0];
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];

    // Последнее обновление офферов — для городских страниц
    $lastOfferUpdate = $db->query("SELECT MAX(updated_at) as dt FROM offers WHERE is_active = 1")->fetch()['dt'] ?: date('Y-m-d');

    $offers = $db->query("SELECT slug, updated_at FROM offers WHERE is_active = 1")->fetchAll();
    foreach ($offers as $o) {
        smartUpsertUrl("/offer/{$o['slug']}", 'offer', $o['updated_at'], 0.8, $o['slug'], $stats);
    }

    $articles = $db->query("SELECT slug, updated_at FROM articles WHERE is_published = 1")->fetchAll();
    foreach ($articles as $a) {
        smartUpsertUrl("/articles/{$a['slug']}", 'article', $a['updated_at'], 0.6, $a['slug'], $stats);
    }

    $tags = $db->query("SELECT slug, category, created_at FROM offer_tags WHERE is_active = 1")->fetchAll();
    foreach ($tags as $t) {
        smartUpsertUrl(($catUrls[$t['category']] ?? '/zajmy')."/type/{$t['slug']}", 'category', $t['created_at'], 0.7, $t['slug'], $stats);
    }

    $cities = getCities();
    foreach ($cities as $c) {
        // Для городских страниц используем дату последнего обновления офферов, а не NOW()
        $cityHash = $c['slug'] . '_' . substr($lastOfferUpdate, 0, 10);
        
        // Проверяем SEO-текст города для каждой категории
        foreach (['microloans' => '/zajmy', 'credits' => '/kredity'] as $cat => $prefix) {
            $citySeoDate = $lastOfferUpdate;
            try {
                $stmt = $db->prepare("SELECT updated_at FROM city_seo_texts WHERE city_slug = ? AND category = ? LIMIT 1");
                $stmt->execute([$c['slug'], $cat]);
                $seo = $stmt->fetch();
                if ($seo) $citySeoDate = $seo['updated_at'];
            } catch (Exception $e) {}
            smartUpsertUrl("{$prefix}/{$c['slug']}", 'city', $citySeoDate, $cat === 'microloans' ? 0.6 : 0.5, $c['slug'] . '_' . $cat, $stats);
        }
        smartUpsertUrl("/karty/{$c['slug']}", 'city', $lastOfferUpdate, 0.5, $c['slug'] . '_cards', $stats);

        foreach ($tags as $t) {
            $catUrl = $catUrls[$t['category']] ?? '/zajmy';
            $lastmod = $t['created_at'];
            try {
                $stmt = $db->prepare("SELECT updated_at FROM city_tag_seo_texts WHERE city_slug=? AND category=? AND tag_slug=?");
                $stmt->execute([$c['slug'], $t['category'], $t['slug']]);
                $seo = $stmt->fetch();
                if ($seo) $lastmod = $seo['updated_at'];
            } catch (Exception $e) {}
            smartUpsertUrl("{$catUrl}/{$c['slug']}/type/{$t['slug']}", 'city_tag', $lastmod, 0.5, $c['slug'] . '_' . $t['slug'], $stats);
        }
    }

    foreach (['/' => ['static',1.0], '/zajmy' => ['category',0.9], '/kredity' => ['category',0.9], '/karty/kreditnye' => ['category',0.8], '/karty/debetovye' => ['category',0.8], '/novye-mfo' => ['category',0.8], '/calculator' => ['static',0.7], '/compare' => ['static',0.7], '/articles' => ['static',0.7]] as $url => $p) {
        smartUpsertUrl($url, $p[0], $lastOfferUpdate, $p[1], 'static_' . md5($url), $stats);
    }
    return $stats;
}

/**
 * Умная вставка/обновление URL.
 * Обновляет last_modified ТОЛЬКО если контент реально изменился.
 */
function smartUpsertUrl(string $url, string $type, string $lastmod, float $priority, string $contentKey, array &$stats): void {
    global $db;
    $hash = md5($contentKey . '|' . substr($lastmod, 0, 10));
    try {
        // Проверяем существует ли URL и изменился ли hash
        $existing = $db->prepare("SELECT id, content_hash FROM url_index_tracker WHERE url = ?");
        $existing->execute([$url]);
        $row = $existing->fetch();
        
        if (!$row) {
            // Новый URL — вставляем
            $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority, content_hash) VALUES (?,?,?,?,?)")
               ->execute([$url, $type, $lastmod, $priority, $hash]);
            $stats['added']++;
        } elseif ($row['content_hash'] !== $hash) {
            // Контент изменился — обновляем last_modified
            $db->prepare("UPDATE url_index_tracker SET last_modified = ?, priority = ?, content_hash = ? WHERE id = ?")
               ->execute([$lastmod, $priority, $hash, $row['id']]);
            $stats['updated']++;
        } else {
            // Ничего не изменилось — только priority
            $db->prepare("UPDATE url_index_tracker SET priority = ? WHERE id = ?")
               ->execute([$priority, $row['id']]);
            $stats['unchanged']++;
        }
    } catch (Exception $e) {}
}

switch ($action) {

case 'stats':
    $total = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker")->fetch()['cnt'];
    $byType = $db->query("SELECT url_type, COUNT(*) as cnt FROM url_index_tracker GROUP BY url_type")->fetchAll();
    $recentlyModified = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE last_modified > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['cnt'];
    $notSubmittedYandex = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex")->fetch()['cnt'];
    $notSubmittedGoogle = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_google IS NULL OR last_modified > submitted_google")->fetch()['cnt'];
    $submittedYandex = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_yandex IS NOT NULL AND submitted_yandex >= last_modified")->fetch()['cnt'];
    $submittedGoogle = (int)$db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_google IS NOT NULL AND submitted_google >= last_modified")->fetch()['cnt'];
    $recentLogs = $db->query("SELECT * FROM indexing_log ORDER BY created_at DESC LIMIT 10")->fetchAll();
    echo json_encode([
        'total_urls' => $total, 'by_type' => $byType,
        'recently_modified' => $recentlyModified,
        'pending_yandex' => $notSubmittedYandex, 'pending_google' => $notSubmittedGoogle,
        'submitted_yandex' => $submittedYandex, 'submitted_google' => $submittedGoogle,
        'recent_logs' => $recentLogs
    ]);
    break;

case 'sync':
    $result = syncUrlsFromDb();
    echo json_encode(['success' => true, 'added' => $result['added'], 'updated' => $result['updated'], 'unchanged' => $result['unchanged']]);
    break;

case 'pending':
    $service = $_GET['service'] ?? 'yandex';
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $col = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
    $stmt = $db->prepare("SELECT url, url_type, last_modified, priority FROM url_index_tracker WHERE {$col} IS NULL OR last_modified > {$col} ORDER BY priority DESC, last_modified DESC LIMIT ?");
    $stmt->execute([$limit]);
    echo json_encode(['service' => $service, 'count' => $stmt->rowCount(), 'urls' => $stmt->fetchAll()]);
    break;

case 'recent':
    $days = (int)($_GET['days'] ?? 7);
    $stmt = $db->prepare("SELECT url, url_type, last_modified, priority, indexed_yandex, indexed_google, submitted_yandex, submitted_google FROM url_index_tracker WHERE last_modified > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY last_modified DESC LIMIT 200");
    $stmt->execute([$days]);
    echo json_encode($stmt->fetchAll());
    break;

case 'mark-submitted':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $service = $data['service'] ?? 'yandex';
    $urls = $data['urls'] ?? [];
    if (empty($urls)) { echo json_encode(['error'=>'No URLs']); exit; }
    $col = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
    $ph = str_repeat('?,', count($urls) - 1) . '?';
    $db->prepare("UPDATE url_index_tracker SET {$col}=NOW() WHERE url IN ({$ph})")->execute($urls);
    $db->prepare("INSERT INTO indexing_log (service,action,urls_count,urls_success,status) VALUES (?,'submit',?,?,'success')")->execute([$service, count($urls), count($urls)]);
    echo json_encode(['success' => true, 'marked' => count($urls)]);
    break;

case 'mark-indexed':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $service = $data['service'] ?? 'yandex';
    $urls = $data['urls'] ?? [];
    $col = $service === 'google' ? 'indexed_google' : 'indexed_yandex';
    $ph = str_repeat('?,', count($urls) - 1) . '?';
    $db->prepare("UPDATE url_index_tracker SET {$col}=1 WHERE url IN ({$ph})")->execute($urls);
    echo json_encode(['success' => true, 'marked' => count($urls)]);
    break;

case 'reset-submitted':
    if ($method !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $service = $data['service'] ?? 'all';
    if ($service === 'google' || $service === 'all') {
        $db->exec("UPDATE url_index_tracker SET submitted_google = NULL");
    }
    if ($service === 'yandex' || $service === 'all') {
        $db->exec("UPDATE url_index_tracker SET submitted_yandex = NULL");
    }
    echo json_encode(['success' => true]);
    break;

case 'export-urls':
    $service = $_GET['service'] ?? 'yandex';
    $type = $_GET['type'] ?? 'pending';
    $col = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
    if ($type === 'pending') {
        $urls = $db->query("SELECT url FROM url_index_tracker WHERE {$col} IS NULL OR last_modified > {$col} ORDER BY priority DESC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
    } elseif ($type === 'recent') {
        $urls = $db->query("SELECT url FROM url_index_tracker WHERE last_modified > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY last_modified DESC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $urls = $db->query("SELECT url FROM url_index_tracker ORDER BY priority DESC LIMIT 500")->fetchAll(PDO::FETCH_COLUMN);
    }
    $fullUrls = array_map(fn($u) => SITE_URL . $u, $urls);
    echo json_encode(['count' => count($fullUrls), 'urls' => $fullUrls, 'text' => implode("\n", $fullUrls)]);
    break;

case 'seo-files':
    $offersCount = (int)$db->query("SELECT COUNT(*) as cnt FROM offers WHERE is_active = 1")->fetch()['cnt'];
    $articlesCount = (int)$db->query("SELECT COUNT(*) as cnt FROM articles WHERE is_published = 1")->fetch()['cnt'];
    $tagsCount = (int)$db->query("SELECT COUNT(*) as cnt FROM offer_tags WHERE is_active = 1")->fetch()['cnt'];
    require_once __DIR__ . '/../../data/cities.php';
    $citiesCount = count(getCities());
    $cityTagPages = $citiesCount * $tagsCount;
    $totalSitemapUrls = 15 + $offersCount + $articlesCount + $tagsCount + ($citiesCount * 3) + $cityTagPages;
    $lastOfferUpdate = $db->query("SELECT MAX(updated_at) as dt FROM offers WHERE is_active = 1")->fetch()['dt'];
    $lastArticleUpdate = $db->query("SELECT MAX(updated_at) as dt FROM articles WHERE is_published = 1")->fetch()['dt'];
    echo json_encode([
        'sitemap' => ['url' => SITE_URL.'/sitemap.xml', 'total_urls' => $totalSitemapUrls, 'offers' => $offersCount, 'articles' => $articlesCount, 'tags' => $tagsCount, 'cities' => $citiesCount, 'city_tag_pages' => $cityTagPages],
        'robots' => ['url' => SITE_URL.'/robots.txt'],
        'llms' => ['url' => SITE_URL.'/llms.txt', 'offers' => $offersCount, 'articles' => $articlesCount, 'tags' => $tagsCount, 'auto_generated' => true]
    ]);
    break;

case 'preview-llms':
    ob_start();
    require __DIR__ . '/../../pages/llms.php';
    echo json_encode(['content' => ob_get_clean()]);
    break;

case 'preview-robots':
    ob_start();
    require __DIR__ . '/../../pages/robots.php';
    echo json_encode(['content' => ob_get_clean()]);
    break;

case 'changes':
    $days = (int)($_GET['days'] ?? 7);
    $changes = [];
    $stmt = $db->prepare("SELECT 'offer' as type, title, slug, updated_at FROM offers WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY updated_at DESC LIMIT 20");
    $stmt->execute([$days]); $changes = array_merge($changes, $stmt->fetchAll());
    $stmt = $db->prepare("SELECT 'article' as type, title, slug, updated_at FROM articles WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY updated_at DESC LIMIT 20");
    $stmt->execute([$days]); $changes = array_merge($changes, $stmt->fetchAll());
    $stmt = $db->prepare("SELECT 'tag' as type, title, slug, created_at as updated_at FROM offer_tags WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$days]); $changes = array_merge($changes, $stmt->fetchAll());
    try {
        $stmt = $db->prepare("SELECT 'city_seo' as type, CONCAT(city_slug,' (',category,')') as title, city_slug as slug, updated_at FROM city_seo_texts WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY updated_at DESC LIMIT 20");
        $stmt->execute([$days]); $changes = array_merge($changes, $stmt->fetchAll());
    } catch (Exception $e) {}
    try {
        $stmt = $db->prepare("SELECT 'city_tag_seo' as type, CONCAT(city_slug,' + ',tag_slug) as title, CONCAT(city_slug,'/',tag_slug) as slug, updated_at FROM city_tag_seo_texts WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? DAY) ORDER BY updated_at DESC LIMIT 20");
        $stmt->execute([$days]); $changes = array_merge($changes, $stmt->fetchAll());
    } catch (Exception $e) {}
    usort($changes, fn($a,$b) => strtotime($b['updated_at']) - strtotime($a['updated_at']));
    echo json_encode(['days' => $days, 'count' => count($changes), 'changes' => array_slice($changes, 0, 50)]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

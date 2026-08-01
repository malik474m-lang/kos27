<?php
/**
 * API для модуля индексации
 * Получение списка URL, статистики, отправка на индексацию
 */
requireAdmin();

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'stats';

// Убедимся что таблицы существуют
try {
    $db->query("SELECT 1 FROM url_index_tracker LIMIT 1");
} catch (Exception $e) {
    // Создаём таблицы если не существуют
    $db->exec("CREATE TABLE IF NOT EXISTS `url_index_tracker` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `url` varchar(500) NOT NULL,
        `url_type` enum('offer','article','city','city_tag','category','static') NOT NULL DEFAULT 'static',
        `first_seen` timestamp NULL DEFAULT current_timestamp(),
        `last_modified` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
        `status` enum('success','partial','error') NOT NULL DEFAULT 'success',
        `response` text DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_service_date` (`service`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

// Синхронизация URL из БД
function syncUrlsFromDb(): array {
    global $db;
    
    require_once __DIR__ . '/../../data/cities.php';
    
    $stats = ['added' => 0, 'updated' => 0];
    $siteUrl = SITE_URL;
    
    $catUrls = [
        'microloans' => '/zajmy',
        'credits' => '/kredity',
        'credit_cards' => '/karty/kreditnye',
        'debit_cards' => '/karty/debetovye'
    ];
    
    // Офферы
    $offers = $db->query("SELECT slug, updated_at FROM offers WHERE is_active = 1")->fetchAll();
    foreach ($offers as $o) {
        $url = "/offer/{$o['slug']}";
        upsertUrl($url, 'offer', $o['updated_at'], 0.8);
        $stats['added']++;
    }
    
    // Статьи
    $articles = $db->query("SELECT slug, updated_at FROM articles WHERE is_published = 1")->fetchAll();
    foreach ($articles as $a) {
        $url = "/articles/{$a['slug']}";
        upsertUrl($url, 'article', $a['updated_at'], 0.6);
        $stats['added']++;
    }
    
    // Теги
    $tags = $db->query("SELECT slug, category, created_at FROM offer_tags WHERE is_active = 1")->fetchAll();
    foreach ($tags as $t) {
        $catUrl = $catUrls[$t['category']] ?? '/zajmy';
        $url = "{$catUrl}/type/{$t['slug']}";
        upsertUrl($url, 'category', $t['created_at'], 0.7);
        $stats['added']++;
    }
    
    // Города
    $cities = getCities();
    foreach ($cities as $c) {
        // Займы
        upsertUrl("/zajmy/{$c['slug']}", 'city', date('Y-m-d H:i:s'), 0.6);
        // Кредиты
        upsertUrl("/kredity/{$c['slug']}", 'city', date('Y-m-d H:i:s'), 0.5);
        // Карты
        upsertUrl("/karty/{$c['slug']}", 'city', date('Y-m-d H:i:s'), 0.5);
        $stats['added'] += 3;
        
        // City + Tag
        foreach ($tags as $t) {
            $catUrl = $catUrls[$t['category']] ?? '/zajmy';
            $url = "{$catUrl}/{$c['slug']}/type/{$t['slug']}";
            
            // Проверяем lastmod из city_tag_seo_texts
            try {
                $stmt = $db->prepare("SELECT updated_at FROM city_tag_seo_texts WHERE city_slug = ? AND category = ? AND tag_slug = ?");
                $stmt->execute([$c['slug'], $t['category'], $t['slug']]);
                $seo = $stmt->fetch();
                $lastmod = $seo ? $seo['updated_at'] : $t['created_at'];
            } catch (Exception $e) {
                $lastmod = $t['created_at'];
            }
            
            upsertUrl($url, 'city_tag', $lastmod, 0.5);
            $stats['added']++;
        }
    }
    
    // Статические страницы
    $staticPages = [
        ['/', 'static', 1.0],
        ['/zajmy', 'category', 0.9],
        ['/kredity', 'category', 0.9],
        ['/karty/kreditnye', 'category', 0.8],
        ['/karty/debetovye', 'category', 0.8],
        ['/novye-mfo', 'category', 0.8],
        ['/calculator', 'static', 0.7],
        ['/compare', 'static', 0.7],
        ['/articles', 'static', 0.7],
    ];
    
    foreach ($staticPages as $p) {
        upsertUrl($p[0], $p[1], date('Y-m-d H:i:s'), $p[2]);
        $stats['added']++;
    }
    
    return $stats;
}

function upsertUrl(string $url, string $type, string $lastmod, float $priority): void {
    global $db;
    try {
        $stmt = $db->prepare("INSERT INTO url_index_tracker (url, url_type, last_modified, priority) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE last_modified = VALUES(last_modified), priority = VALUES(priority)");
        $stmt->execute([$url, $type, $lastmod, $priority]);
    } catch (Exception $e) {}
}

// Обработка действий
switch ($action) {
    case 'stats':
        // Общая статистика
        $total = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker")->fetch()['cnt'];
        $byType = $db->query("SELECT url_type, COUNT(*) as cnt FROM url_index_tracker GROUP BY url_type")->fetchAll();
        
        $notIndexedYandex = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE indexed_yandex = 0")->fetch()['cnt'];
        $notIndexedGoogle = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE indexed_google = 0")->fetch()['cnt'];
        
        $recentlyModified = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE last_modified > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['cnt'];
        
        $notSubmittedYandex = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex")->fetch()['cnt'];
        $notSubmittedGoogle = $db->query("SELECT COUNT(*) as cnt FROM url_index_tracker WHERE submitted_google IS NULL OR last_modified > submitted_google")->fetch()['cnt'];
        
        // Последние логи
        $recentLogs = $db->query("SELECT * FROM indexing_log ORDER BY created_at DESC LIMIT 10")->fetchAll();
        
        echo json_encode([
            'total_urls' => (int)$total,
            'by_type' => $byType,
            'not_indexed_yandex' => (int)$notIndexedYandex,
            'not_indexed_google' => (int)$notIndexedGoogle,
            'recently_modified' => (int)$recentlyModified,
            'pending_yandex' => (int)$notSubmittedYandex,
            'pending_google' => (int)$notSubmittedGoogle,
            'recent_logs' => $recentLogs
        ]);
        break;
        
    case 'sync':
        // Синхронизировать URL из БД
        $stats = syncUrlsFromDb();
        echo json_encode(['success' => true, 'synced' => $stats['added']]);
        break;
        
    case 'pending':
        // Получить список URL для отправки на индексацию
        $service = $_GET['service'] ?? 'yandex';
        $limit = min((int)($_GET['limit'] ?? 100), 500);
        
        $submittedCol = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
        
        $stmt = $db->prepare("SELECT url, url_type, last_modified, priority 
            FROM url_index_tracker 
            WHERE {$submittedCol} IS NULL OR last_modified > {$submittedCol}
            ORDER BY priority DESC, last_modified DESC 
            LIMIT ?");
        $stmt->execute([$limit]);
        $urls = $stmt->fetchAll();
        
        echo json_encode([
            'service' => $service,
            'count' => count($urls),
            'urls' => $urls
        ]);
        break;
        
    case 'recent':
        // Недавно обновлённые URL
        $days = (int)($_GET['days'] ?? 7);
        $stmt = $db->prepare("SELECT url, url_type, last_modified, priority, indexed_yandex, indexed_google
            FROM url_index_tracker 
            WHERE last_modified > DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY last_modified DESC 
            LIMIT 200");
        $stmt->execute([$days]);
        
        echo json_encode($stmt->fetchAll());
        break;
        
    case 'mark-submitted':
        // Отметить URL как отправленные
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $service = $data['service'] ?? 'yandex';
        $urls = $data['urls'] ?? [];
        
        if (empty($urls)) {
            echo json_encode(['error' => 'No URLs provided']);
            exit;
        }
        
        $submittedCol = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
        $placeholders = str_repeat('?,', count($urls) - 1) . '?';
        
        $stmt = $db->prepare("UPDATE url_index_tracker SET {$submittedCol} = NOW() WHERE url IN ({$placeholders})");
        $stmt->execute($urls);
        
        // Логируем
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, status) VALUES (?, 'submit', ?, 'success')")
           ->execute([$service, count($urls)]);
        
        echo json_encode(['success' => true, 'marked' => count($urls)]);
        break;
        
    case 'mark-indexed':
        // Отметить URL как проиндексированные
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $service = $data['service'] ?? 'yandex';
        $urls = $data['urls'] ?? [];
        
        $indexedCol = $service === 'google' ? 'indexed_google' : 'indexed_yandex';
        $placeholders = str_repeat('?,', count($urls) - 1) . '?';
        
        $stmt = $db->prepare("UPDATE url_index_tracker SET {$indexedCol} = 1 WHERE url IN ({$placeholders})");
        $stmt->execute($urls);
        
        echo json_encode(['success' => true, 'marked' => count($urls)]);
        break;
        
    case 'export-urls':
        // Экспорт URL для Яндекс.Вебмастера или Google Search Console
        $service = $_GET['service'] ?? 'yandex';
        $type = $_GET['type'] ?? 'pending'; // pending | recent | all
        
        $submittedCol = $service === 'google' ? 'submitted_google' : 'submitted_yandex';
        
        if ($type === 'pending') {
            $urls = $db->query("SELECT url FROM url_index_tracker WHERE {$submittedCol} IS NULL OR last_modified > {$submittedCol} ORDER BY priority DESC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($type === 'recent') {
            $urls = $db->query("SELECT url FROM url_index_tracker WHERE last_modified > DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY last_modified DESC LIMIT 200")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $urls = $db->query("SELECT url FROM url_index_tracker ORDER BY priority DESC LIMIT 500")->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // Полные URL
        $fullUrls = array_map(fn($u) => SITE_URL . $u, $urls);
        
        echo json_encode([
            'count' => count($fullUrls),
            'urls' => $fullUrls,
            'text' => implode("\n", $fullUrls)
        ]);
        break;
        
    case 'yandex-api':
        // Отправка через Яндекс.Вебмастер API (если есть токен)
        $settings = getSiteSettings();
        $yandexToken = $settings['yandex_webmaster_token'] ?? '';
        $yandexHostId = $settings['yandex_webmaster_host_id'] ?? '';
        
        if (!$yandexToken || !$yandexHostId) {
            echo json_encode(['error' => 'Yandex Webmaster API not configured', 'need_config' => true]);
            exit;
        }
        
        // Получаем pending URLs
        $urls = $db->query("SELECT url FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex ORDER BY priority DESC LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($urls)) {
            echo json_encode(['success' => true, 'message' => 'No pending URLs']);
            exit;
        }
        
        $results = [];
        foreach ($urls as $url) {
            $fullUrl = SITE_URL . $url;
            
            // Yandex Webmaster API: reindex request
            $ch = curl_init("https://api.webmaster.yandex.net/v4/user/{$yandexHostId}/hosts/{$yandexHostId}/recrawl/queue");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['url' => $fullUrl]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: OAuth ' . $yandexToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $results[] = [
                'url' => $url,
                'status' => $httpCode,
                'response' => json_decode($response, true)
            ];
            
            if ($httpCode === 200 || $httpCode === 201) {
                $db->prepare("UPDATE url_index_tracker SET submitted_yandex = NOW() WHERE url = ?")->execute([$url]);
            }
        }
        
        // Логируем
        $successCount = count(array_filter($results, fn($r) => $r['status'] === 200 || $r['status'] === 201));
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, status, response) VALUES ('yandex', 'submit', ?, ?, ?)")
           ->execute([$successCount, $successCount === count($results) ? 'success' : 'partial', json_encode($results)]);
        
        echo json_encode([
            'success' => true,
            'submitted' => $successCount,
            'total' => count($results),
            'results' => $results
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action']);
}

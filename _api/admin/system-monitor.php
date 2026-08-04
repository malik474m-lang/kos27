<?php
/**
 * Системный мониторинг — единая вкладка здоровья системы
 */
requireAdmin();
require_once __DIR__ . '/../../includes/error-logger.php';
require_once __DIR__ . '/../../includes/yandex-webmaster.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/google-indexing.php';
require_once __DIR__ . '/../../includes/article-image.php';

$db = getDB();
$action = $_GET['action'] ?? 'overview';

switch ($action) {

case 'overview':
    $checks = [];

    // 1. Планировщик
    $schedulerSettings = [];
    $schedulerFile = __DIR__ . '/../../data/scheduler-settings.json';
    if (file_exists($schedulerFile)) $schedulerSettings = json_decode(file_get_contents($schedulerFile), true) ?: [];

    $reviewsEnabled = $schedulerSettings['reviews_enabled'] ?? true;
    $articlesEnabled = $schedulerSettings['articles_enabled'] ?? true;

    $today = date('Y-m-d');
    $reviewCount = 0; $articleCount = 0;
    $reviewFile = __DIR__ . "/../../data/review_count_{$today}.txt";
    $articleFile = __DIR__ . "/../../data/article_count_{$today}.txt";
    if (file_exists($reviewFile)) $reviewCount = (int)file_get_contents($reviewFile);
    if (file_exists($articleFile)) $articleCount = (int)file_get_contents($articleFile);

    $lastReviewTime = 0; $lastArticleTime = 0;
    $lrFile = __DIR__ . '/../../data/last_review_time.txt';
    $laFile = __DIR__ . '/../../data/last_article_time.txt';
    if (file_exists($lrFile)) $lastReviewTime = (int)file_get_contents($lrFile);
    if (file_exists($laFile)) $lastArticleTime = (int)file_get_contents($laFile);

    $schedulerLog = [];
    $slFile = __DIR__ . '/../../data/scheduler-fire.log';
    if (file_exists($slFile)) {
        $lines = array_filter(array_map('trim', file($slFile)));
        $schedulerLog = array_slice(array_reverse($lines), 0, 10);
    }

    $checks['scheduler'] = [
        'reviews_enabled' => $reviewsEnabled,
        'articles_enabled' => $articlesEnabled,
        'reviews_today' => $reviewCount,
        'articles_today' => $articleCount,
        'reviews_target' => $schedulerSettings['reviews_per_day'] ?? 5,
        'articles_target' => $schedulerSettings['articles_per_day'] ?? 1,
        'last_review' => $lastReviewTime ? date('Y-m-d H:i:s', $lastReviewTime) : null,
        'last_article' => $lastArticleTime ? date('Y-m-d H:i:s', $lastArticleTime) : null,
        'recent_log' => $schedulerLog,
    ];

    // 2. Почта
    $mailCfg = getMailConfig();
    $checks['mail'] = [
        'smtp_enabled' => $mailCfg['smtp_enabled'],
        'smtp_host' => $mailCfg['smtp_host'] ?: null,
        'mail_from' => $mailCfg['mail_from'],
        'contact_email' => $mailCfg['contact_email'] ?: null,
        'method' => $mailCfg['smtp_enabled'] ? 'SMTP' : 'mail()',
    ];

    // 3. Внешние сервисы и SEO-интеграции
    $siteSettings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    $checks['services'] = [
        'google_indexing' => googleIndexingAvailable(),
        'google_search_console' => googleIndexingAvailable(),
        'yandex_webmaster' => yandexWebmasterAvailable(),
        'yandex_gpt' => !empty(YANDEX_GPT_API_KEY) && !empty(YANDEX_FOLDER_ID),
        'google_sitemap_ping_deprecated' => true,
        'article_image_provider' => $siteSettings['article_image_provider'] ?? 'yandex',
        'article_image_prompt_template' => $siteSettings['article_image_prompt_template'] ?? 'нарисуй 16:9 {title}',
        'article_image_recent' => getArticleImageLog(8),
    ];

    // 4. 2FA
    try {
        $adminUser = $db->query("SELECT totp_enabled FROM admin_users WHERE id = " . (int)($_SESSION['admin_id'] ?? 0))->fetch();
        $checks['security'] = ['2fa_enabled' => !empty($adminUser['totp_enabled'])];
    } catch (Exception $e) {
        $checks['security'] = ['2fa_enabled' => false];
    }

    // 5. Кэш
    $cacheDir = __DIR__ . '/../../data/page_cache';
    $cacheFiles = is_dir($cacheDir) ? glob($cacheDir . '/*.html') : [];
    $checks['cache'] = [
        'page_cache_files' => count($cacheFiles ?: []),
        'page_cache_size' => array_sum(array_map('filesize', $cacheFiles ?: [])),
    ];

    // 6. Бэкапы
    $backupDir = __DIR__ . '/../../data/backups';
    $backupFiles = is_dir($backupDir) ? glob($backupDir . '/*.zip') : [];
    $lastBackup = null;
    if ($backupFiles) {
        usort($backupFiles, fn($a,$b) => filemtime($b) - filemtime($a));
        $lastBackup = date('Y-m-d H:i:s', filemtime($backupFiles[0]));
    }
    $checks['backups'] = [
        'count' => count($backupFiles ?: []),
        'last_backup' => $lastBackup,
        'days_since_backup' => $lastBackup ? (int)((time() - strtotime($lastBackup)) / 86400) : null,
    ];

    // 7. БД
    try {
        $dbSize = $db->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()")->fetch();
        $tableCount = $db->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()")->fetch();
        $checks['database'] = [
            'size_mb' => (float)($dbSize['size_mb'] ?? 0),
            'tables' => (int)($tableCount['cnt'] ?? 0),
        ];
    } catch (Exception $e) {
        $checks['database'] = ['error' => $e->getMessage()];
    }

    // 8. Диск
    $dataDir = __DIR__ . '/../../data';
    $checks['disk'] = [
        'data_dir_exists' => is_dir($dataDir),
        'data_dir_writable' => is_writable($dataDir),
    ];

    // 9. PHP
    $checks['php'] = [
        'version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'curl' => function_exists('curl_init'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
    ];

    // 10. Ошибки
    $errors = getAppErrors(20);
    $checks['recent_errors'] = $errors;
    $checks['error_count'] = count($errors);

    echo json_encode($checks);
    break;

case 'errors':
    $errors = getAppErrors((int)($_GET['limit'] ?? 50));
    echo json_encode(['errors' => $errors, 'count' => count($errors)]);
    break;

case 'clear-errors':
    $cleared = clearAppErrors();
    echo json_encode(['success' => true, 'cleared' => $cleared]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

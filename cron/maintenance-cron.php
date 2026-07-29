<?php
/**
 * Крон-задачи обслуживания сайта
 * 
 * Запуск: php cron/maintenance-cron.php [задача]
 * 
 * Задачи:
 *   all              — все задачи по порядку (по умолчанию)
 *   link-check       — проверка битых партнёрских ссылок
 *   smart-rating     — пересчёт умного рейтинга
 *   clean-logs       — очистка старых логов
 *   clean-cache      — очистка старого кэша
 *   backup           — бэкап базы данных
 *   health-ping      — проверка работоспособности сайта
 * 
 * Crontab (рекомендуется):
 *   0 4 * * *  php ~/domains/kosmozaim.ru/cron/maintenance-cron.php all
 *   или по отдельности:
 *   0 3 * * *  php ~/domains/kosmozaim.ru/cron/maintenance-cron.php link-check
 *   0 5 * * *  php ~/domains/kosmozaim.ru/cron/maintenance-cron.php smart-rating
 *   30 4 * * * php ~/domains/kosmozaim.ru/cron/maintenance-cron.php clean-logs
 *   35 4 * * * php ~/domains/kosmozaim.ru/cron/maintenance-cron.php clean-cache
 *   0 2 * * 0  php ~/domains/kosmozaim.ru/cron/maintenance-cron.php backup
 *   */10 * * * * php ~/domains/kosmozaim.ru/cron/maintenance-cron.php health-ping
 */

require_once __DIR__ . '/../config.php';

$task = $argv[1] ?? 'all';
$logFile = __DIR__ . '/../data/maintenance.log';
$startTime = microtime(true);

function mlog(string $msg): void {
    global $logFile;
    $ts = date('Y-m-d H:i:s');
    $line = "[$ts] $msg\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

mlog("=== Старт: $task ===");

// ============================================================
// 1. ПРОВЕРКА БИТЫХ ССЫЛОК
// ============================================================
function taskLinkCheck(): void {
    mlog("🔗 Проверка партнёрских ссылок...");
    
    $db = getDB();
    $offers = $db->query("SELECT id, title, affiliate_url FROM offers WHERE is_active = 1 AND affiliate_url != ''")->fetchAll();
    
    if (!$offers) {
        mlog("   Нет активных офферов со ссылками");
        return;
    }
    
    // Создаём таблицу если нет
    try {
        $db->query("SELECT 1 FROM offer_link_checks LIMIT 1");
    } catch (Exception $e) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `offer_link_checks` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `offer_id` int(11) NOT NULL,
                `http_code` int(11) DEFAULT 0,
                `final_url` varchar(2000) DEFAULT NULL,
                `redirect_count` int(11) DEFAULT 0,
                `is_ok` tinyint(1) DEFAULT 0,
                `error_message` varchar(500) DEFAULT NULL,
                `checked_at` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_offer` (`offer_id`),
                KEY `idx_checked` (`checked_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    
    $ok = 0;
    $fail = 0;
    $insertStmt = $db->prepare("INSERT INTO offer_link_checks (offer_id, http_code, final_url, redirect_count, is_ok, error_message) VALUES (?,?,?,?,?,?)");
    
    foreach ($offers as $offer) {
        $url = $offer['affiliate_url'];
        $result = checkUrl($url);
        
        $insertStmt->execute([
            $offer['id'],
            $result['http_code'],
            mb_substr($result['final_url'], 0, 2000),
            $result['redirect_count'],
            $result['is_ok'],
            $result['error_message'] ? mb_substr($result['error_message'], 0, 500) : null,
        ]);
        
        if ($result['is_ok']) {
            $ok++;
        } else {
            $fail++;
            mlog("   ❌ {$offer['title']}: HTTP {$result['http_code']} — " . ($result['error_message'] ?: 'ошибка'));
        }
        
        usleep(500000); // 0.5 сек между запросами
    }
    
    mlog("   ✅ OK: $ok, ❌ Битых: $fail из " . count($offers));
}

function checkUrl(string $url): array {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'Некорректный URL'];
    }
    if (!function_exists('curl_init')) {
        return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'cURL недоступен'];
    }
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_NOBODY => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Kosmozaim-LinkChecker/1.0)',
        CURLOPT_ENCODING => '',
    ]);
    curl_exec($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $code = (int)($info['http_code'] ?? 0);
    $isOk = ($code >= 200 && $code < 400) ? 1 : 0;
    
    // HEAD может давать 405 — пробуем GET
    if (!$isOk && $code === 405) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Kosmozaim-LinkChecker/1.0)',
        ]);
        curl_exec($ch);
        $err = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $code = (int)($info['http_code'] ?? 0);
        $isOk = ($code >= 200 && $code < 400) ? 1 : 0;
    }
    
    return [
        'http_code' => $code,
        'final_url' => $info['url'] ?? $url,
        'redirect_count' => (int)($info['redirect_count'] ?? 0),
        'is_ok' => $isOk,
        'error_message' => $err ?: ($isOk ? null : "HTTP $code"),
    ];
}

// ============================================================
// 2. ПЕРЕСЧЁТ УМНОГО РЕЙТИНГА
// ============================================================
function taskSmartRating(): void {
    mlog("🧠 Пересчёт умного рейтинга...");
    
    $db = getDB();
    $period = 30;
    
    $offers = $db->query("SELECT id, title, slug, category, rating, review_count, sort_order, created_at FROM offers WHERE is_active = 1")->fetchAll();
    if (!$offers) { mlog("   Нет активных офферов"); return; }
    
    // Собираем метрики
    $grouped = [];
    foreach ($offers as $o) {
        $oid = (int)$o['id'];
        
        $clicks = (int)$db->query("SELECT COUNT(*) FROM click_stats WHERE offer_id = $oid AND clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)")->fetchColumn();
        
        $approved = 0; $rejected = 0; $payout = 0;
        try {
            $rows = $db->query("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = $oid AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status")->fetchAll();
            foreach ($rows as $r) {
                if ($r['status'] === 'approved') { $approved = (int)$r['cnt']; $payout = (float)$r['total']; }
                elseif ($r['status'] === 'rejected') { $rejected = (int)$r['cnt']; }
            }
        } catch (Exception $e) {}
        
        $views = 0;
        try {
            $views = (int)$db->query("SELECT COUNT(*) FROM page_views WHERE page = '/offer/{$o['slug']}' AND viewed_at >= DATE_SUB(NOW(), INTERVAL $period DAY)")->fetchColumn();
        } catch (Exception $e) {}
        
        $ctr = $views > 0 ? ($clicks / $views) * 100 : 0;
        $approvalRate = ($approved + $rejected) > 0 ? ($approved / ($approved + $rejected)) * 100 : 0;
        $epc = $clicks > 0 ? $payout / $clicks : 0;
        $reviewStrength = ((float)$o['rating']) * log(((int)$o['review_count']) + 1, 2);
        
        $grouped[$o['category']][] = [
            'id' => $oid,
            'title' => $o['title'],
            'clicks' => $clicks,
            'ctr' => $ctr,
            'approval_rate' => $approvalRate,
            'epc' => $epc,
            'review_strength' => $reviewStrength,
        ];
    }
    
    // Скоринг и обновление sort_order
    $stmt = $db->prepare("UPDATE offers SET sort_order = ? WHERE id = ?");
    $updated = 0;
    
    foreach ($grouped as $category => $items) {
        $maxClicks = max(1, ...array_column($items, 'clicks'));
        $maxCtr = max(1, ...array_column($items, 'ctr'));
        $maxApproval = max(1, ...array_column($items, 'approval_rate'));
        $maxEpc = max(1, ...array_column($items, 'epc'));
        $maxReview = max(1, ...array_column($items, 'review_strength'));
        
        foreach ($items as &$row) {
            $row['score'] = round(
                ($row['clicks'] / $maxClicks) * 25 +
                ($row['ctr'] / $maxCtr) * 20 +
                ($row['approval_rate'] / $maxApproval) * 20 +
                ($row['epc'] / $maxEpc) * 20 +
                ($row['review_strength'] / $maxReview) * 15, 1
            );
        }
        unset($row);
        
        usort($items, fn($a, $b) => $b['score'] <=> $a['score']);
        
        foreach ($items as $i => $row) {
            $stmt->execute([$i, $row['id']]);
            $updated++;
        }
        
        mlog("   $category: " . count($items) . " офферов отсортировано");
    }
    
    // Очистка кэша страниц
    try {
        require_once __DIR__ . '/../includes/page-cache.php';
        pageCacheClear();
    } catch (Exception $e) {}
    
    mlog("   ✅ Обновлено: $updated офферов");
}

// ============================================================
// 3. ОЧИСТКА СТАРЫХ ЛОГОВ
// ============================================================
function taskCleanLogs(): void {
    mlog("🧹 Очистка старых логов...");
    
    $db = getDB();
    $cleaned = [];
    
    // Логи авторизации старше 30 дней
    try {
        $stmt = $db->exec("DELETE FROM admin_login_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $cleaned[] = "admin_login_log: $stmt";
    } catch (Exception $e) {}
    
    // Аудит-лог старше 90 дней
    try {
        $stmt = $db->exec("DELETE FROM admin_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $cleaned[] = "admin_audit_log: $stmt";
    } catch (Exception $e) {}
    
    // Лог отправки рассылок старше 60 дней
    try {
        $stmt = $db->exec("DELETE FROM newsletter_send_log WHERE sent_at < DATE_SUB(NOW(), INTERVAL 60 DAY)");
        $cleaned[] = "newsletter_send_log: $stmt";
    } catch (Exception $e) {}
    
    // Проверки ссылок старше 30 дней (кроме последней для каждого оффера)
    try {
        $db->exec("
            DELETE lc FROM offer_link_checks lc
            INNER JOIN (
                SELECT offer_id, MAX(id) as keep_id
                FROM offer_link_checks
                GROUP BY offer_id
            ) keep ON lc.offer_id = keep.offer_id AND lc.id != keep.keep_id
            WHERE lc.checked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $cleaned[] = "offer_link_checks: old entries";
    } catch (Exception $e) {}
    
    // page_views старше 90 дней
    try {
        $stmt = $db->exec("DELETE FROM page_views WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
        $cleaned[] = "page_views: $stmt";
    } catch (Exception $e) {}
    
    // click_stats старше 180 дней
    try {
        $stmt = $db->exec("DELETE FROM click_stats WHERE clicked_at < DATE_SUB(NOW(), INTERVAL 180 DAY)");
        $cleaned[] = "click_stats: $stmt";
    } catch (Exception $e) {}
    
    // Файловые логи — обрезаем до 10000 строк
    $logFiles = glob(__DIR__ . '/../data/*.log');
    foreach ($logFiles ?: [] as $f) {
        if (basename($f) === 'maintenance.log') continue;
        $lines = @file($f);
        if ($lines && count($lines) > 10000) {
            file_put_contents($f, implode('', array_slice($lines, -5000)));
            $cleaned[] = basename($f) . ": обрезан до 5000 строк";
        }
    }
    
    mlog("   ✅ Очищено: " . implode(', ', $cleaned));
}

// ============================================================
// 4. ОЧИСТКА СТАРОГО КЭША
// ============================================================
function taskCleanCache(): void {
    mlog("🗑️ Очистка кэша...");
    
    $dirs = [
        __DIR__ . '/../data/page_cache' => 3600,     // HTML-кэш > 1 час
        __DIR__ . '/../data/api_cache' => 3600,       // API-кэш > 1 час
        __DIR__ . '/../data/geo_cache' => 86400 * 7,  // Гео-кэш > 7 дней
    ];
    
    $total = 0;
    foreach ($dirs as $dir => $maxAge) {
        if (!is_dir($dir)) continue;
        $files = glob($dir . '/*');
        foreach ($files ?: [] as $f) {
            if (is_file($f) && (time() - filemtime($f)) > $maxAge) {
                @unlink($f);
                $total++;
            }
        }
    }
    
    mlog("   ✅ Удалено файлов кэша: $total");
}

// ============================================================
// 5. БЭКАП БАЗЫ ДАННЫХ
// ============================================================
function taskBackup(): void {
    mlog("💾 Создание бэкапа...");
    
    $backupDir = __DIR__ . '/../data/backups';
    if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);
    
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'partnerka72_kosmonew';
    $user = getenv('DB_USER') ?: 'partnerka72';
    $pass = getenv('DB_PASS') ?: '';
    
    $timestamp = date('Y-m-d_H-i-s');
    $sqlFile = "$backupDir/backup_cron_$timestamp.sql";
    $zipFile = "$backupDir/backup_cron_$timestamp.zip";
    
    // mysqldump
    $passArg = $pass ? "-p" . escapeshellarg($pass) : '';
    $cmd = "mysqldump -h " . escapeshellarg($host) . " -P " . escapeshellarg($port) . " -u " . escapeshellarg($user) . " $passArg " . escapeshellarg($name) . " > " . escapeshellarg($sqlFile) . " 2>&1";
    $output = shell_exec($cmd);
    
    if (!file_exists($sqlFile) || filesize($sqlFile) < 100) {
        mlog("   ❌ mysqldump не удался: $output");
        @unlink($sqlFile);
        return;
    }
    
    // Сжимаем в ZIP
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
            $zip->addFile($sqlFile, basename($sqlFile));
            $zip->close();
            @unlink($sqlFile);
            $size = round(filesize($zipFile) / 1024 / 1024, 2);
            mlog("   ✅ Бэкап создан: " . basename($zipFile) . " ($size MB)");
        }
    } else {
        $size = round(filesize($sqlFile) / 1024 / 1024, 2);
        mlog("   ✅ Бэкап создан: " . basename($sqlFile) . " ($size MB, без сжатия)");
    }
    
    // Удаляем бэкапы старше 30 дней
    $oldBackups = glob($backupDir . '/backup_cron_*.{zip,sql}', GLOB_BRACE);
    $deleted = 0;
    foreach ($oldBackups ?: [] as $f) {
        if ((time() - filemtime($f)) > 86400 * 30) {
            @unlink($f);
            $deleted++;
        }
    }
    if ($deleted) mlog("   🗑️ Удалено старых бэкапов: $deleted");
}

// ============================================================
// 6. ПРОВЕРКА РАБОТОСПОСОБНОСТИ
// ============================================================
function taskHealthPing(): void {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://kosmozaim.ru';
    $alertFile = __DIR__ . '/../data/health-alert.txt';
    
    $endpoints = [
        '/' => 'Главная',
        '/api/health' => 'API Health',
        '/zajmy' => 'Страница займов',
    ];
    
    $allOk = true;
    foreach ($endpoints as $path => $name) {
        $url = $siteUrl . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Kosmozaim-HealthCheck/1.0',
            CURLOPT_NOBODY => false,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2);
        $err = curl_error($ch);
        curl_close($ch);
        
        $ok = ($code >= 200 && $code < 400);
        $slow = $time > 5;
        
        if (!$ok) {
            $allOk = false;
            mlog("   ❌ $name ($path): HTTP $code" . ($err ? " — $err" : ""));
        } elseif ($slow) {
            mlog("   ⚠️ $name ($path): OK но медленно ({$time}s)");
        } else {
            mlog("   ✅ $name ({$time}s)");
        }
    }
    
    // Проверяем БД
    try {
        $db = getDB();
        $db->query("SELECT 1")->fetch();
        mlog("   ✅ MySQL подключение");
    } catch (Exception $e) {
        $allOk = false;
        mlog("   ❌ MySQL: " . $e->getMessage());
    }
    
    // Алерт-файл для мониторинга
    if (!$allOk) {
        @file_put_contents($alertFile, date('Y-m-d H:i:s') . " FAIL\n", FILE_APPEND);
    } else {
        // Если всё ок — убираем алерт
        if (file_exists($alertFile)) @unlink($alertFile);
    }
}

// ============================================================
// ДИСПЕТЧЕР
// ============================================================
$tasks = [
    'link-check' => 'taskLinkCheck',
    'smart-rating' => 'taskSmartRating',
    'clean-logs' => 'taskCleanLogs',
    'clean-cache' => 'taskCleanCache',
    'backup' => 'taskBackup',
    'health-ping' => 'taskHealthPing',
];

if ($task === 'all') {
    foreach ($tasks as $name => $func) {
        try {
            $func();
        } catch (Exception $e) {
            mlog("❌ Ошибка в $name: " . $e->getMessage());
        }
    }
} elseif (isset($tasks[$task])) {
    try {
        $tasks[$task]();
    } catch (Exception $e) {
        mlog("❌ Ошибка: " . $e->getMessage());
    }
} else {
    mlog("❌ Неизвестная задача: $task");
    mlog("Доступные: " . implode(', ', array_keys($tasks)) . ", all");
}

$elapsed = round(microtime(true) - $startTime, 2);
mlog("=== Завершено за {$elapsed}s ===\n");

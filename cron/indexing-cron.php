<?php
/**
 * Крон автоматической индексации
 *
 * Запуск каждые 2 часа:
 *   каждые 2 часа: php ~/domains/kosmozaim.ru/cron/indexing-cron.php
 *
 * Что делает:
 *   0. Синхронизирует трекер (раз в 12ч)
 *   1. Находит URL с last_modified > submitted_* (новые/обновлённые)
 *   2. Отправляет через IndexNow (Яндекс + Bing) — до 100 URL за раз
 *   3. Отправляет через Google Indexing API — до 50 URL за раз
 *   4. Отправляет через Yandex Webmaster API — до 20 URL
 *   5. Пингует sitemap
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auto-indexing.php';

$logFile = __DIR__ . '/../data/indexing-cron.log';
$startTime = microtime(true);

function ilog(string $msg): void {
    global $logFile;
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logFile, "[{$ts}] {$msg}\n", FILE_APPEND | LOCK_EX);
    echo "[{$ts}] {$msg}\n";
}

function e_json($v): string {
    $s = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (strlen($s) > 300) $s = substr($s, 0, 300) . '...';
    return $s;
}
/**
 * Дневной счётчик попыток отправки в Google Indexing API.
 * Квота: 200 URL_UPDATED/day на сайт, сброс в 00:00 Pacific Time.
 * Считаем ПОПЫТКИ включая ошибки (429 тоже расходует попытки).
 */
function googleDailyCount(): int {
    $f = __DIR__ . '/../data/google-indexing-daily.json';
    if (!file_exists($f)) return 0;
    $d = json_decode(file_get_contents($f), true);
    if (!is_array($d)) return 0;
    $today = (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d');
    return (($d['date'] ?? '') === $today) ? (int)($d['count'] ?? 0) : 0;
}

function googleDailyAdd(int $n): void {
    $f = __DIR__ . '/../data/google-indexing-daily.json';
    $today = (new DateTime('now', new DateTimeZone('America/Los_Angeles')))->format('Y-m-d');
    $d = ['date' => $today, 'count' => googleDailyCount() + max(0, $n)];
    @file_put_contents($f, json_encode($d));
}


// Ограничение: не чаще 1 раза в час
$lockFile = __DIR__ . '/../data/indexing-cron.lock';
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 3600) {
    ilog("Skip: last run < 1 hour ago");
    exit;
}
@touch($lockFile);

ilog("=== Indexing Cron Start ===");

$db = getDB();

// Проверяем существование таблицы
try {
    $db->query("SELECT 1 FROM url_index_tracker LIMIT 1");
} catch (Exception $e) {
    ilog("Table url_index_tracker not found, run sync first");
    exit;
}

// 0. Синхронизация URL из базы (добавление новых, обновление изменённых)
// выполняется раз в 12 часов — файл-флаг чтобы не гонять городских subcats ��аждый час.
ilog("--- Sync URLs ---");
$syncFlag = __DIR__ . '/../data/indexing-sync.flag';
if (!file_exists($syncFlag) || (time() - filemtime($syncFlag)) >= 43200) {
    try {
        require_once __DIR__ . '/../includes/indexing-sync.php';
        @set_time_limit(600);
        $sync = syncUrlsFromDb();
        ilog("Sync: added={$sync['added']} updated={$sync['updated']} unchanged={$sync['unchanged']}");
        @touch($syncFlag);
    } catch (Exception $e) {
        ilog("Sync error: " . $e->getMessage());
    }
} else {
    ilog("Sync: skipped (ran < 12h ago)");
}

// 1. IndexNow — все ожидающие URL (Яндекс + Bing)
ilog("--- IndexNow ---");
try {
    // URL, не отправленные в Яндекс
    $pendingYandex = $db->query("SELECT url FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex ORDER BY priority DESC LIMIT 100")->fetchAll(PDO::FETCH_COLUMN);
    
    if ($pendingYandex) {
        $result = indexNowSubmitBatch($pendingYandex);
        ilog("IndexNow: sent " . count($pendingYandex) . " URLs, success=" . $result['success']);
    } else {
        ilog("IndexNow: no pending URLs");
    }
} catch (Exception $e) {
    ilog("IndexNow error: " . $e->getMessage());
}

// 2. Google Indexing API
ilog("--- Google Indexing API ---");
try {
    require_once __DIR__ . '/../includes/google-indexing.php';
    $GOOGLE_DAILY_LIMIT = 190; // буфер от 200
    $dailyUsed = googleDailyCount();
    if (googleIndexingAvailable() && $dailyUsed >= $GOOGLE_DAILY_LIMIT) {
        ilog("Google: skipped — daily quota {$GOOGLE_DAILY_LIMIT}/day already used (429 safeguard)");
    } elseif (googleIndexingAvailable()) {
        $batchLimit = min(50, max(0, $GOOGLE_DAILY_LIMIT - $dailyUsed));
        $pendingGoogle = $db->query("SELECT url FROM url_index_tracker WHERE submitted_google IS NULL OR last_modified > submitted_google ORDER BY priority DESC LIMIT {$batchLimit}")->fetchAll(PDO::FETCH_COLUMN);
        ilog("Google: daily used {$dailyUsed}/{$GOOGLE_DAILY_LIMIT}, batch {$batchLimit}");        
        if ($pendingGoogle && $batchLimit > 0) {
            $fullUrls = array_map(fn($u) => SITE_URL . $u, $pendingGoogle);
            $result = googleIndexBatch($fullUrls);
            googleDailyAdd(count($fullUrls));
            ilog("Google: sent " . $result['total'] . ", success=" . $result['success'] . ", failed=" . $result['failed']);
            if ($result['failed'] > 0) {
                $statusHistogram = [];
                $firstErr = '';
                foreach ($result['results'] as $ri => $r) {
                    if (!$r['success']) {
                        $st = (string)($r['status'] ?? '?');
                        $statusHistogram[$st] = ($statusHistogram[$st] ?? 0) + 1;
                        if (!$firstErr) $firstErr = e_json($r);
                    }
                }
                $histParts = [];
                foreach ($statusHistogram as $st => $cnt) $histParts[] = "{$st}x{$cnt}";
                ilog("Google: failed statuses: " . implode(', ', $histParts));
                ilog("Google: first fail: " . $firstErr);
            }
            
            // Обновляем submitted_google
            foreach ($result['results'] as $r) {
                if ($r['success']) {
                    $path = str_replace(SITE_URL, '', $r['url']);
                    $db->prepare("UPDATE url_index_tracker SET submitted_google = NOW() WHERE url = ?")->execute([$path]);
                }
            }
            
            // Лог
            try {
                $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status) VALUES ('google', 'submit', ?, ?, ?)")
                   ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success']);
            } catch (Exception $e) {}
        } else {
            ilog("Google: no pending URLs");
        }
    } else {
        ilog("Google: API not configured");
    }
} catch (Exception $e) {
    ilog("Google error: " . $e->getMessage());
}

// 3. Yandex Webmaster API
ilog("--- Yandex Webmaster ---");
try {
    require_once __DIR__ . '/../includes/yandex-webmaster.php';
    if (yandexWebmasterAvailable()) {
        $pendingYandex = $db->query("SELECT url FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex ORDER BY priority DESC LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
        
        if ($pendingYandex) {
            $fullUrls = array_map(fn($u) => SITE_URL . $u, $pendingYandex);
            $result = yandexSubmitBatch($fullUrls);
            ilog("Yandex: sent " . $result['total'] . ", success=" . $result['success'] . ", failed=" . $result['failed']);
            if ($result['failed'] > 0) {
                $statusHistogram = [];
                $firstErr = '';
                foreach ($result['results'] as $ri => $r) {
                    if (!$r['success']) {
                        $st = (string)($r['status'] ?? '?');
                        $statusHistogram[$st] = ($statusHistogram[$st] ?? 0) + 1;
                        if (!$firstErr) $firstErr = e_json($r);
                    }
                }
                $histParts = [];
                foreach ($statusHistogram as $st => $cnt) $histParts[] = "{$st}x{$cnt}";
                ilog("Yandex: failed statuses: " . implode(', ', $histParts));
                ilog("Yandex: first fail: " . $firstErr);
            }
            
            // Обновляем submitted_yandex
            foreach ($result['results'] as $r) {
                if ($r['success']) {
                    $path = str_replace(SITE_URL, '', $r['url']);
                    $db->prepare("UPDATE url_index_tracker SET submitted_yandex = NOW() WHERE url = ?")->execute([$path]);
                }
            }
            
            // Лог
            try {
                $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status) VALUES ('yandex', 'submit', ?, ?, ?)")
                   ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success']);
            } catch (Exception $e) {}
        } else {
            ilog("Yandex: no pending URLs");
        }
    } else {
        ilog("Yandex: API not configured");
    }
} catch (Exception $e) {
    ilog("Yandex error: " . $e->getMessage());
}

// 4. Ping sitemap
pingSitemap();
ilog("Sitemap pinged");

$elapsed = round(microtime(true) - $startTime, 2);
ilog("=== Indexing Cron Done ({$elapsed}s) ===\n");

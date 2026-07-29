<?php
/**
 * Авто-расписание генерации контента
 * Подключается в index.php на каждый запрос
 * Проверяет условия и запускает генерацию
 * 
 * v2: без exec() — HTTP-запрос к собственному API или inline
 */

function checkAutoScheduler(): void {
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    
    // Настройки
    $settingsFile = "$dataDir/scheduler-settings.json";
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    
    $reviewsPerDay = $settings['reviews_per_day'] ?? (int)(getenv('REVIEW_COUNT') ?: 5);
    $reviewStartHour = $settings['review_start_hour'] ?? 6;
    $reviewEndHour = $settings['review_end_hour'] ?? 22;
    $reviewsEnabled = $settings['reviews_enabled'] ?? true;
    
    $articlesPerDay = $settings['articles_per_day'] ?? 1;
    $articleStartHour = $settings['article_start_hour'] ?? 8;
    $articleEndHour = $settings['article_end_hour'] ?? 20;
    $articlesEnabled = $settings['articles_enabled'] ?? true;
    
    date_default_timezone_set('Europe/Moscow');
    $currentHour = (int)date('H');
    $today = date('Y-m-d');
    
    // === ОТЗЫВЫ ===
    if ($reviewsEnabled && $reviewsPerDay > 0) {
        $inWindow = ($reviewStartHour <= $reviewEndHour)
            ? ($currentHour >= $reviewStartHour && $currentHour < $reviewEndHour)
            : ($currentHour >= $reviewStartHour || $currentHour < $reviewEndHour);
        
        if ($inWindow) {
            $countFile = "$dataDir/review_count_{$today}.txt";
            $doneTodayReviews = file_exists($countFile) ? (int)file_get_contents($countFile) : 0;
            
            if ($doneTodayReviews < $reviewsPerDay) {
                $workingHours = ($reviewStartHour <= $reviewEndHour) 
                    ? ($reviewEndHour - $reviewStartHour)
                    : (24 - $reviewStartHour + $reviewEndHour);
                $intervalMinutes = max(10, ($workingHours * 60) / max(1, $reviewsPerDay));
                
                $lastFile = "$dataDir/last_review_time.txt";
                $lastTime = file_exists($lastFile) ? (int)file_get_contents($lastFile) : 0;
                
                if (time() - $lastTime > $intervalMinutes * 60) {
                    // Помечаем сразу — чтобы следующий запрос не запустил дубль
                    file_put_contents($lastFile, time());
                    file_put_contents($countFile, $doneTodayReviews + 1);
                    
                    // Запускаем генерацию через HTTP к своему API (неблокирующе)
                    schedulerRunGeneration('review');
                }
            }
        }
    }
    
    // === СТАТЬИ ===
    if ($articlesEnabled && $articlesPerDay > 0) {
        $inWindow = ($articleStartHour <= $articleEndHour)
            ? ($currentHour >= $articleStartHour && $currentHour < $articleEndHour)
            : ($currentHour >= $articleStartHour || $currentHour < $articleEndHour);
        
        if ($inWindow) {
            $countFile = "$dataDir/article_count_{$today}.txt";
            $doneTodayArticles = file_exists($countFile) ? (int)file_get_contents($countFile) : 0;
            
            if ($doneTodayArticles < $articlesPerDay) {
                $workingHours = ($articleStartHour <= $articleEndHour)
                    ? ($articleEndHour - $articleStartHour)
                    : (24 - $articleStartHour + $articleEndHour);
                $intervalMinutes = max(30, ($workingHours * 60) / max(1, $articlesPerDay));
                
                $lastFile = "$dataDir/last_article_time.txt";
                $lastTime = file_exists($lastFile) ? (int)file_get_contents($lastFile) : 0;
                
                if (time() - $lastTime > $intervalMinutes * 60) {
                    file_put_contents($lastFile, time());
                    file_put_contents($countFile, $doneTodayArticles + 1);
                    
                    schedulerRunGeneration('article');
                }
            }
        }
    }
}

/**
 * Запуск генерации контента
 * Попытка 1: неблокирующий HTTP через fsockopen
 * Попытка 2: cURL с коротким таймаутом
 * Попытка 3: inline require (блокирующий, но гарантированный)
 */
function schedulerRunGeneration(string $type): void {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://kosmozaim.ru';
    $cronSecret = defined('CRON_SECRET') ? CRON_SECRET : 'kosmozaim-cron-2024';
    $path = '/api/cron-generate?type=' . $type . '&secret=' . urlencode($cronSecret);
    $url = $siteUrl . $path;
    $logFile = __DIR__ . '/../data/scheduler-fire.log';
    
    // Попытка 1: fsockopen (неблокирующий)
    $parts = parse_url($url);
    $host = $parts['host'] ?? 'kosmozaim.ru';
    $port = ($parts['scheme'] ?? 'https') === 'https' ? 443 : 80;
    $reqPath = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    $prefix = ($port === 443) ? 'ssl://' : '';
    
    $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 3);
    if ($fp) {
        $req = "GET $reqPath HTTP/1.1
Host: $host
Connection: close

";
        fwrite($fp, $req);
        fclose($fp);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " OK fsockopen $type
", FILE_APPEND | LOCK_EX);
        return;
    }
    
    // Попытка 2: cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        @curl_exec($ch);
        curl_close($ch);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " OK curl $type
", FILE_APPEND | LOCK_EX);
        return;
    }
    
    // Попытка 3: inline (блокирующий, но работает всегда)
    try {
        if ($type === 'review') {
            $cronScript = __DIR__ . '/../cron/review-cron.php';
        } elseif ($type === 'article') {
            $cronScript = __DIR__ . '/../cron/article-cron.php';
        } else {
            return;
        }
        if (file_exists($cronScript)) {
            ob_start();
            require $cronScript;
            $output = ob_get_clean();
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " OK inline $type: " . trim(substr($output, 0, 100)) . "
", FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " ERR inline $type: " . $e->getMessage() . "
", FILE_APPEND | LOCK_EX);
    }
    return;
}

// Оставлено для совместимости
function schedulerFireAndForget(string $path, string $method = 'POST'): void {
    $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://kosmozaim.ru';
    $url = $siteUrl . $path;
    $parts = parse_url($url);
    
    $host = $parts['host'] ?? 'kosmozaim.ru';
    $port = ($parts['scheme'] ?? 'https') === 'https' ? 443 : 80;
    $requestPath = $parts['path'] ?? '/';
    
    // Передаём сессионную куку для авторизации 
    $cookie = '';
    if (session_status() === PHP_SESSION_ACTIVE && session_id()) {
        $cookie = 'Cookie: PHPSESSID=' . session_id() . "\r\n";
    }
    
    $body = '{}';
    $headers = "$method $requestPath HTTP/1.1\r\n";
    $headers .= "Host: $host\r\n";
    $headers .= "Content-Type: application/json\r\n";
    $headers .= "Content-Length: " . strlen($body) . "\r\n";
    $headers .= $cookie;
    $headers .= "Connection: close\r\n\r\n";
    $headers .= $body;
    
    $prefix = ($port === 443) ? 'ssl://' : '';
    $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 5);
    if ($fp) {
        // Отправляем и сразу закрываем — не ждём ответа
        fwrite($fp, $headers);
        fclose($fp);
        
        // Лог
        $logFile = __DIR__ . '/../data/scheduler-fire.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " FIRE $method $path\n", FILE_APPEND | LOCK_EX);
    } else {
        // fsockopen не удался — пробуем cURL с таймаутом 1 сек
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => ($method === 'POST'),
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 2,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            if ($cookie) {
                curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
            }
            @curl_exec($ch);
            curl_close($ch);
        }
        
        $logFile = __DIR__ . '/../data/scheduler-fire.log';
        @file_put_contents($logFile, date('Y-m-d H:i:s') . " FALLBACK $method $path (fsockopen failed: $errstr)\n", FILE_APPEND | LOCK_EX);
    }
}

/**
 * Получить текущие настройки планировщика
 */
function getSchedulerSettings(): array {
    $dataDir = __DIR__ . '/../data';
    $settingsFile = "$dataDir/scheduler-settings.json";
    
    $defaults = [
        'reviews_enabled' => true,
        'reviews_per_day' => 5,
        'review_start_hour' => 6,
        'review_end_hour' => 22,
        'articles_enabled' => true,
        'articles_per_day' => 1,
        'article_start_hour' => 8,
        'article_end_hour' => 20,
    ];
    
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true);
        return array_merge($defaults, $settings ?: []);
    }
    
    return $defaults;
}

/**
 * Сохранить настройки планировщика
 */
function saveSchedulerSettings(array $settings): bool {
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    $settingsFile = "$dataDir/scheduler-settings.json";
    return file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Получить статистику планировщика
 */
function getSchedulerStats(): array {
    $dataDir = __DIR__ . '/../data';
    date_default_timezone_set('Europe/Moscow');
    $today = date('Y-m-d');
    
    // Последние записи из лога
    $lastFires = [];
    $fireLog = "$dataDir/scheduler-fire.log";
    if (file_exists($fireLog)) {
        $lines = file($fireLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastFires = array_slice($lines ?: [], -10);
    }
    
    return [
        'reviews_today' => file_exists("$dataDir/review_count_{$today}.txt") 
            ? (int)file_get_contents("$dataDir/review_count_{$today}.txt") : 0,
        'articles_today' => file_exists("$dataDir/article_count_{$today}.txt")
            ? (int)file_get_contents("$dataDir/article_count_{$today}.txt") : 0,
        'last_review' => file_exists("$dataDir/last_review_time.txt")
            ? date('H:i:s', (int)file_get_contents("$dataDir/last_review_time.txt")) : '-',
        'last_article' => file_exists("$dataDir/last_article_time.txt")
            ? date('H:i:s', (int)file_get_contents("$dataDir/last_article_time.txt")) : '-',
        'current_hour' => (int)date('H'),
        'last_fires' => $lastFires,
    ];
}

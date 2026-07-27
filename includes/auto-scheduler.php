<?php
/**
 * Авто-расписание генерации контента
 * Подключается в index.php на каждый запрос
 * Проверяет условия и запускает генерацию в фоне
 */

function checkAutoScheduler(): void {
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    
    // Загружаем настройки из JSON (или .env как fallback)
    $settingsFile = "$dataDir/scheduler-settings.json";
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    
    // === НАСТРОЙКИ ОТЗЫВОВ ===
    $reviewsPerDay = $settings['reviews_per_day'] ?? (int)(getenv('REVIEW_COUNT') ?: 5);
    $reviewStartHour = $settings['review_start_hour'] ?? 6;   // с 06:00
    $reviewEndHour = $settings['review_end_hour'] ?? 22;      // до 22:00
    $reviewsEnabled = $settings['reviews_enabled'] ?? true;
    
    // === НАСТРОЙКИ СТАТЕЙ ===
    $articlesPerDay = $settings['articles_per_day'] ?? 1;
    $articleStartHour = $settings['article_start_hour'] ?? 8;
    $articleEndHour = $settings['article_end_hour'] ?? 20;
    $articlesEnabled = $settings['articles_enabled'] ?? true;
    
    // Текущий час (по московскому времени)
    date_default_timezone_set('Europe/Moscow');
    $currentHour = (int)date('H');
    $today = date('Y-m-d');
    
    // === АВТОГЕНЕРАЦИЯ ОТЗЫВОВ ===
    if ($reviewsEnabled && $reviewsPerDay > 0) {
        // Проверяем временное окно
        $inReviewWindow = ($reviewStartHour <= $reviewEndHour)
            ? ($currentHour >= $reviewStartHour && $currentHour < $reviewEndHour)
            : ($currentHour >= $reviewStartHour || $currentHour < $reviewEndHour);
        
        if ($inReviewWindow) {
            $reviewCountFile = "$dataDir/review_count_{$today}.txt";
            $reviewsToday = file_exists($reviewCountFile) ? (int)file_get_contents($reviewCountFile) : 0;
            
            if ($reviewsToday < $reviewsPerDay) {
                // Вычисляем интервал между отзывами
                $workingHours = ($reviewStartHour <= $reviewEndHour) 
                    ? ($reviewEndHour - $reviewStartHour)
                    : (24 - $reviewStartHour + $reviewEndHour);
                $intervalMinutes = max(10, ($workingHours * 60) / $reviewsPerDay);
                
                $lastReviewFile = "$dataDir/last_review_time.txt";
                $lastReview = file_exists($lastReviewFile) ? (int)file_get_contents($lastReviewFile) : 0;
                
                if (time() - $lastReview > $intervalMinutes * 60) {
                    // Время создать отзыв
                    file_put_contents($lastReviewFile, time());
                    file_put_contents($reviewCountFile, $reviewsToday + 1);
                    
                    $cronScript = __DIR__ . '/../cron/review-cron.php';
                    if (file_exists($cronScript)) {
                        $logFile = "$dataDir/auto-reviews.log";
                        $cmd = "cd " . escapeshellarg(dirname($cronScript) . '/..') . " && php " . escapeshellarg($cronScript) . " 1 >> " . escapeshellarg($logFile) . " 2>&1 &";
                        exec($cmd);
                    }
                }
            }
        }
    }
    
    // === АВТОГЕНЕРАЦИЯ СТАТЕЙ ===
    if ($articlesEnabled && $articlesPerDay > 0) {
        $inArticleWindow = ($articleStartHour <= $articleEndHour)
            ? ($currentHour >= $articleStartHour && $currentHour < $articleEndHour)
            : ($currentHour >= $articleStartHour || $currentHour < $articleEndHour);
        
        if ($inArticleWindow) {
            $articleCountFile = "$dataDir/article_count_{$today}.txt";
            $articlesToday = file_exists($articleCountFile) ? (int)file_get_contents($articleCountFile) : 0;
            
            if ($articlesToday < $articlesPerDay) {
                $workingHours = ($articleStartHour <= $articleEndHour)
                    ? ($articleEndHour - $articleStartHour)
                    : (24 - $articleStartHour + $articleEndHour);
                $intervalMinutes = max(30, ($workingHours * 60) / $articlesPerDay);
                
                $lastArticleFile = "$dataDir/last_article_time.txt";
                $lastArticle = file_exists($lastArticleFile) ? (int)file_get_contents($lastArticleFile) : 0;
                
                if (time() - $lastArticle > $intervalMinutes * 60) {
                    file_put_contents($lastArticleFile, time());
                    file_put_contents($articleCountFile, $articlesToday + 1);
                    
                    $cronScript = __DIR__ . '/../cron/article-cron.php';
                    if (file_exists($cronScript)) {
                        $logFile = "$dataDir/auto-articles.log";
                        $cmd = "cd " . escapeshellarg(dirname($cronScript) . '/..') . " && php " . escapeshellarg($cronScript) . " >> " . escapeshellarg($logFile) . " 2>&1 &";
                        exec($cmd);
                    }
                }
            }
        }
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
    ];
}

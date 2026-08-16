<?php
/**
 * Счётчик интереса к офферу.
 * Использует page_views и click_stats, но безопасно работает,
 * даже если часть колонок/таблиц отсутствует.
 */

function getOfferInterestStats(int $offerId, string $offerPagePath): array {
    $db = getDB();

    $views24h = 0;
    $recentUniqueViews = 0;
    $clicks24h = 0;

    try {
        $viewDateColumn = function_exists('dbDateColumn')
            ? dbDateColumn('page_views', ['viewed_at', 'created_at'])
            : 'viewed_at';

        $pageViewsSql = "
            SELECT
                COUNT(*) AS total_views,
                COUNT(DISTINCT ip) AS unique_views,
                COUNT(DISTINCT CASE WHEN {$viewDateColumn} >= DATE_SUB(NOW(), INTERVAL 45 MINUTE) THEN ip END) AS recent_unique
            FROM page_views
            WHERE page = ?
              AND {$viewDateColumn} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";

        $stmt = $db->prepare($pageViewsSql);
        $stmt->execute([$offerPagePath]);
        $row = $stmt->fetch();

        if ($row) {
            $views24h = (int)($row['total_views'] ?? 0);
            $recentUniqueViews = (int)($row['recent_unique'] ?? 0);
        }
    } catch (Exception $e) {
        // таблица или колонки могут отсутствовать — просто пропускаем
    }

    try {
        $clickDateColumn = function_exists('dbDateColumn')
            ? dbDateColumn('click_stats', ['created_at', 'clicked_at'])
            : 'created_at';

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute([$offerId]);
        $clicks24h = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        // старая схема — ок
    }

    // Мягкий «живой» счётчик на основе реальных данных.
    $minuteJitter = ((int)date('i')) % 3;
    $liveNow = $recentUniqueViews + (int)ceil($clicks24h / 6) + $minuteJitter;

    // Если данных мало, показываем осторожный базовый интерес.
    if ($liveNow <= 0) {
        $hourFactor = ((int)date('G') >= 9 && (int)date('G') <= 22) ? 2 : 0;
        $liveNow = 3 + ($offerId % 4) + $hourFactor;
    }

    $liveNow = max(3, min(29, $liveNow));

    $trendLabel = 'Стабильный интерес';
    if ($views24h >= 40 || $clicks24h >= 12) {
        $trendLabel = 'Высокий спрос';
    } elseif ($views24h >= 15 || $clicks24h >= 5) {
        $trendLabel = 'Предложение популярно';
    }

    return [
        'live_now' => $liveNow,
        'views_24h' => $views24h,
        'clicks_24h' => $clicks24h,
        'trend_label' => $trendLabel,
    ];
}

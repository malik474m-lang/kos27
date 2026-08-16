<?php
/**
 * Счётчик интереса к офферу.
 * Использует page_views, click_stats, postback_conversions и user_applications,
 * но безопасно работает даже если часть таблиц/колонок отсутствует.
 */

function getOfferInterestStats(int $offerId, string $offerPagePath, array $offer = []): array {
    $db = getDB();

    $views24h = 0;
    $recentUniqueViews = 0;
    $clicks24h = 0;
    $applicationsToday = 0;
    $approvedToday = 0;

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
        // ignore
    }

    try {
        $clickDateColumn = function_exists('dbDateColumn')
            ? dbDateColumn('click_stats', ['created_at', 'clicked_at'])
            : 'created_at';

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute([$offerId]);
        $clicks24h = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        // ignore
    }

    try {
        $appDateColumn = function_exists('dbDateColumn')
            ? dbDateColumn('user_applications', ['created_at'])
            : 'created_at';

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM user_applications WHERE offer_id = ? AND DATE({$appDateColumn}) = CURDATE()");
        $stmt->execute([$offerId]);
        $applicationsToday = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        // ignore
    }

    try {
        $convDateColumn = function_exists('dbDateColumn')
            ? dbDateColumn('postback_conversions', ['created_at'])
            : 'created_at';

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM postback_conversions WHERE offer_id = ? AND status = 'approved' AND DATE({$convDateColumn}) = CURDATE()");
        $stmt->execute([$offerId]);
        $approvedToday = (int)($stmt->fetch()['cnt'] ?? 0);
    } catch (Exception $e) {
        // ignore
    }

    $rating = (float)($offer['rating'] ?? 0);
    $reviewCount = (int)($offer['review_count'] ?? 0);
    $category = (string)($offer['category'] ?? 'microloans');

    // У офферов теперь разные сиды по часу/офферу/категории — значения перестают быть одинаковыми.
    $seed = abs(crc32($offerPagePath . '|' . $category . '|' . date('Y-m-d-H')));
    $hourJitter = $seed % 6;           // 0..5
    $microJitter = ($seed >> 3) % 3;   // 0..2
    $ratingBoost = (int)floor($rating);
    $reviewBoost = min(6, (int)floor($reviewCount / 15));
    $categoryBoost = [
        'microloans' => 3,
        'credits' => 2,
        'credit_cards' => 4,
        'debit_cards' => 1,
    ][$category] ?? 2;

    $liveNow = (int)round($recentUniqueViews * 1.8)
        + (int)ceil($clicks24h / 4)
        + $ratingBoost
        + $reviewBoost
        + $categoryBoost
        + $hourJitter
        + $microJitter;

    // Если живых данных мало, строим правдоподобное значение на базе оффера, но разное для каждого.
    if ($liveNow <= 4) {
        $dayHourBoost = ((int)date('G') >= 9 && (int)date('G') <= 22) ? 2 : 0;
        $liveNow = 4 + ($seed % 9) + $categoryBoost + $dayHourBoost;
    }

    $liveNow = max(4, min(41, $liveNow));

    $trendLabel = 'Стабильный интерес';
    if ($views24h >= 50 || $clicks24h >= 15 || $approvedToday >= 3) {
        $trendLabel = 'Высокий спрос';
    } elseif ($views24h >= 18 || $clicks24h >= 6 || $applicationsToday >= 3) {
        $trendLabel = 'Предложение популярно';
    }

    $todayCount = max($approvedToday, $applicationsToday);
    $todayLabel = $approvedToday > 0 ? 'Оформили сегодня' : 'Подали сегодня';

    // Лёгкий fallback, если данных нет совсем.
    if ($todayCount <= 0) {
        $todayCount = max(1, min(8, (int)floor($liveNow / 4)));
        $todayLabel = 'Подали сегодня';
    }

    return [
        'live_now' => $liveNow,
        'views_24h' => $views24h,
        'clicks_24h' => $clicks24h,
        'applications_today' => $applicationsToday,
        'approved_today' => $approvedToday,
        'today_count' => $todayCount,
        'today_label' => $todayLabel,
        'trend_label' => $trendLabel,
    ];
}

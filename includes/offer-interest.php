<?php
/**
 * Счётчик интереса к офферу.
 * Использует page_views, click_stats, postback_conversions и user_applications,
 * но безопасно работает даже если часть таблиц/колонок отсутствует.
 */

if (!function_exists('offerRussianPlural')) {
    function offerRussianPlural(int $n, string $one, string $two, string $five): string {
        $n = abs($n) % 100;
        $n1 = $n % 10;
        if ($n > 10 && $n < 20) return $five;
        if ($n1 > 1 && $n1 < 5) return $two;
        if ($n1 == 1) return $one;
        return $five;
    }
}

function getOfferInterestStats(int $offerId, string $offerPagePath, array $offer = []): array {
    $db = getDB();

    $views24h = 0;
    $uniqueViews24h = 0;
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
            $uniqueViews24h = (int)($row['unique_views'] ?? 0);
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

    $categoryConfig = [
        'microloans' => ['boost' => 3, 'min_live' => 5, 'max_live' => 18, 'fallback_today_min' => 1, 'fallback_today_max' => 5],
        'credits' => ['boost' => 2, 'min_live' => 2, 'max_live' => 9, 'fallback_today_min' => 1, 'fallback_today_max' => 3],
        'credit_cards' => ['boost' => 4, 'min_live' => 6, 'max_live' => 22, 'fallback_today_min' => 1, 'fallback_today_max' => 6],
        'debit_cards' => ['boost' => 1, 'min_live' => 3, 'max_live' => 11, 'fallback_today_min' => 1, 'fallback_today_max' => 4],
    ][$category] ?? ['boost' => 2, 'min_live' => 4, 'max_live' => 14, 'fallback_today_min' => 1, 'fallback_today_max' => 4];

    $seed = abs(crc32($offerPagePath . '|' . $category . '|' . date('Y-m-d-H')));
    $hourJitter = $seed % 6;
    $microJitter = ($seed >> 3) % 3;
    $ratingBoost = (int)floor($rating);
    $reviewBoost = min(6, (int)floor($reviewCount / 15));
    $categoryBoost = $categoryConfig['boost'];

    $liveNow = (int)round($recentUniqueViews * 1.6)
        + (int)ceil($clicks24h / 5)
        + $ratingBoost
        + $reviewBoost
        + $categoryBoost
        + $hourJitter
        + $microJitter;

    // Если реальных просмотров нет — используем правдоподобный fallback.
    if ($views24h <= 0) {
        $dayHourBoost = ((int)date('G') >= 9 && (int)date('G') <= 22) ? 2 : 0;
        $liveNow = $categoryConfig['min_live'] + ($seed % max(2, $categoryConfig['max_live'] - $categoryConfig['min_live'] - 1)) + $dayHourBoost;
        $liveNow = max($categoryConfig['min_live'], min($categoryConfig['max_live'], $liveNow));
    } else {
        // Если просмотры есть — «сейчас смотрят» не должно выглядеть абсурдно.
        $realisticCap = match (true) {
            $views24h <= 3 => 1,
            $views24h <= 6 => 2,
            $views24h <= 10 => 3,
            $views24h <= 18 => 5,
            default => max(6, (int)ceil($views24h * 0.35)),
        };

        $liveNow = max(1, min($liveNow, $realisticCap, max(1, $uniqueViews24h ?: $views24h)));
    }

    $trendLabel = 'Стабильный интерес';
    if ($views24h >= 50 || $clicks24h >= 15 || $approvedToday >= 3) {
        $trendLabel = 'Высокий спрос';
    } elseif ($views24h >= 18 || $clicks24h >= 6 || $applicationsToday >= 3) {
        $trendLabel = 'Предложение популярно';
    }

    $todayCount = max($approvedToday, $applicationsToday);
    $todayLabel = $approvedToday > 0 ? 'Оформили сегодня' : 'Подали сегодня';

    if ($todayCount <= 0) {
        $todayCount = max(
            $categoryConfig['fallback_today_min'],
            min($categoryConfig['fallback_today_max'], max(1, (int)floor($liveNow / 3)))
        );
        $todayLabel = 'Подали сегодня';
    }

    return [
        'live_now' => $liveNow,
        'views_24h' => $views24h,
        'unique_views_24h' => $uniqueViews24h,
        'clicks_24h' => $clicks24h,
        'applications_today' => $applicationsToday,
        'approved_today' => $approvedToday,
        'today_count' => $todayCount,
        'today_label' => $todayLabel,
        'trend_label' => $trendLabel,
        'views_24h_label' => $views24h . ' ' . offerRussianPlural($views24h, 'просмотр', 'просмотра', 'просмотров'),
    ];
}

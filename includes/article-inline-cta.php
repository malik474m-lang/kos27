<?php
/**
 * Трекинг inline CTA внутри статей.
 * Сохраняет impression/click и отдаёт A/B статистику по вариантам.
 */

function ensureArticleInlineCtaTable(PDO $db): bool {
    static $checked = false;
    if ($checked) return true;
    try {
        $db->query("SELECT 1 FROM article_inline_cta_events LIMIT 1");
        $checked = true;
        return true;
    } catch (Exception $e) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `article_inline_cta_events` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `event_type` enum('impression','click') NOT NULL DEFAULT 'impression',
              `article_slug` varchar(500) NOT NULL,
              `offer_id` int(11) NOT NULL,
              `variant` varchar(10) NOT NULL,
              `ip` varchar(45) DEFAULT NULL,
              `session_key` varchar(100) DEFAULT NULL,
              `click_stat_id` int(11) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_article_slug` (`article_slug`(100)),
              KEY `idx_offer_id` (`offer_id`),
              KEY `idx_variant` (`variant`),
              KEY `idx_event_type` (`event_type`),
              KEY `idx_created_at` (`created_at`),
              KEY `idx_click_stat_id` (`click_stat_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            $checked = true;
            return true;
        } catch (Exception $e2) {
            return false;
        }
    }
}

function trackArticleInlineCtaImpression(string $articleSlug, int $offerId, string $variant, string $ip, string $sessionKey): bool {
    try {
        $db = getDB();
        if (!ensureArticleInlineCtaTable($db)) return false;

        // Не дублируем impression в рамках одной сессии для статьи+оффера+варианта
        $check = $db->prepare("SELECT id FROM article_inline_cta_events WHERE event_type = 'impression' AND article_slug = ? AND offer_id = ? AND variant = ? AND session_key = ? LIMIT 1");
        $check->execute([$articleSlug, $offerId, $variant, $sessionKey]);
        if ($check->fetch()) return true;

        $db->prepare("INSERT INTO article_inline_cta_events (event_type, article_slug, offer_id, variant, ip, session_key) VALUES ('impression',?,?,?,?,?)")
           ->execute([$articleSlug, $offerId, $variant, $ip, $sessionKey]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function trackArticleInlineCtaClick(string $articleSlug, int $offerId, string $variant, string $ip, int $clickStatId = 0): bool {
    try {
        $db = getDB();
        if (!ensureArticleInlineCtaTable($db)) return false;
        $db->prepare("INSERT INTO article_inline_cta_events (event_type, article_slug, offer_id, variant, ip, click_stat_id) VALUES ('click',?,?,?,?,?)")
           ->execute([$articleSlug, $offerId, $variant, $ip, $clickStatId ?: null]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function getArticleInlineCtaStats(int $days = 30): array {
    $db = getDB();
    if (!ensureArticleInlineCtaTable($db)) {
        return ['summary' => [], 'articles' => []];
    }

    $summary = [];
    try {
        $stmt = $db->prepare("
            SELECT
                variant,
                SUM(event_type = 'impression') AS impressions,
                SUM(event_type = 'click') AS clicks
            FROM article_inline_cta_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY variant
            ORDER BY variant ASC
        ");
        $stmt->execute([$days]);
        $summary = $stmt->fetchAll();
    } catch (Exception $e) {}

    foreach ($summary as &$row) {
        $imp = (int)($row['impressions'] ?? 0);
        $clk = (int)($row['clicks'] ?? 0);
        $row['ctr'] = $imp > 0 ? round($clk / $imp * 100, 2) : 0;
        $row['approved'] = 0;
        $row['revenue'] = 0;
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) AS approved_cnt, COALESCE(SUM(pc.payout), 0) AS revenue_sum
                FROM article_inline_cta_events e
                JOIN postback_conversions pc ON pc.aff_sub = e.click_stat_id
                WHERE e.event_type = 'click'
                  AND e.variant = ?
                  AND pc.status = 'approved'
                  AND e.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$row['variant'], $days]);
            $conv = $stmt->fetch();
            if ($conv) {
                $row['approved'] = (int)($conv['approved_cnt'] ?? 0);
                $row['revenue'] = (float)($conv['revenue_sum'] ?? 0);
            }
        } catch (Exception $e) {}
    }
    unset($row);

    $articles = [];
    try {
        $stmt = $db->prepare("
            SELECT
                article_slug,
                variant,
                SUM(event_type = 'impression') AS impressions,
                SUM(event_type = 'click') AS clicks
            FROM article_inline_cta_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY article_slug, variant
            ORDER BY clicks DESC, impressions DESC
            LIMIT 100
        ");
        $stmt->execute([$days]);
        $articles = $stmt->fetchAll();
    } catch (Exception $e) {}

    foreach ($articles as &$row) {
        $imp = (int)($row['impressions'] ?? 0);
        $clk = (int)($row['clicks'] ?? 0);
        $row['ctr'] = $imp > 0 ? round($clk / $imp * 100, 2) : 0;
    }
    unset($row);

    return ['summary' => $summary, 'articles' => $articles, 'days' => $days];
}

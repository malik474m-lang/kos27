<?php
if (apiCacheStart('admin_offers', 30)) exit;
$db = getDB();
$clickDateColumn = function_exists('dbDateColumn') ? dbDateColumn('click_stats', ['clicked_at', 'created_at']) : 'clicked_at';

try {
    $rows = $db->query("
        SELECT o.*,
               COALESCE(c30.clicks_30d, 0) AS clicks_30d,
               COALESCE(ct.clicks_total, 0) AS clicks_total
        FROM offers o
        LEFT JOIN (
            SELECT offer_id, COUNT(*) AS clicks_30d
            FROM click_stats
            WHERE {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY offer_id
        ) c30 ON c30.offer_id = o.id
        LEFT JOIN (
            SELECT offer_id, COUNT(*) AS clicks_total
            FROM click_stats
            GROUP BY offer_id
        ) ct ON ct.offer_id = o.id
        ORDER BY o.sort_order ASC, o.id DESC
    ")->fetchAll();
} catch (Exception $e) {
    $rows = $db->query("SELECT * FROM offers ORDER BY sort_order ASC, id DESC")->fetchAll();
    foreach ($rows as &$row) {
        $row['clicks_30d'] = 0;
        $row['clicks_total'] = 0;
    }
    unset($row);
}

apiCacheEnd($rows);

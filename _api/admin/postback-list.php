<?php
if (apiCacheStart('admin_postback', 15)) exit;
$db = getDB();
$period = $_GET['period'] ?? '30';
$period = max(1, min(365, (int)$period));

$conversions = $db->prepare("
    SELECT pc.*, o.title as offer_title
    FROM postback_conversions pc
    LEFT JOIN offers o ON pc.offer_id = o.id
    WHERE pc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY pc.created_at DESC
    LIMIT 200
");
$conversions->execute([$period]);
$list = $conversions->fetchAll();

// Сводка
$summary = $db->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status='hold' THEN 1 ELSE 0 END) as hold_cnt,
        SUM(CASE WHEN status='approved' THEN payout ELSE 0 END) as total_payout
    FROM postback_conversions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
");
$summary->execute([$period]);
$stats = $summary->fetch();

// По офферам
$byOffer = $db->prepare("
    SELECT o.title, pc.status, COUNT(*) as cnt, SUM(pc.payout) as sum_payout
    FROM postback_conversions pc
    LEFT JOIN offers o ON pc.offer_id = o.id
    WHERE pc.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY pc.offer_id, o.title, pc.status
    ORDER BY cnt DESC
");
$byOffer->execute([$period]);
$offerStats = $byOffer->fetchAll();

apiCacheEnd([
    'conversions' => $list,
    'stats' => $stats,
    'byOffer' => $offerStats,
    'period' => $period,
]);

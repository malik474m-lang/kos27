<?php
if (apiCacheStart('admin_funnel', 20)) exit;

$db = getDB();
$period = $_GET['period'] ?? '30';
$period = max(1, min(365, (int)$period));

$offers = $db->query("SELECT id, title, slug, category, logo_url FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

$funnel = [];
foreach ($offers as $o) {
    $oid = (int)$o['id'];
    $slug = $o['slug'];

    $views = (int)$db->prepare("SELECT COUNT(*) FROM page_views WHERE page = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)")->execute(['/offer/' . $slug, $period]) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
    // Правильный способ
    $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND viewed_at >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $vstmt->execute(['/offer/' . $slug]);
    $views = (int)$vstmt->fetch()['cnt'];

    $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $cstmt->execute([$oid]);
    $clicks = (int)$cstmt->fetch()['cnt'];

    $approved = 0; $rejected = 0; $pending = 0; $payout = 0;
    try {
        $pstmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status");
        $pstmt->execute([$oid]);
        foreach ($pstmt->fetchAll() as $row) {
            if ($row['status'] === 'approved') { $approved = (int)$row['cnt']; $payout = (float)$row['total']; }
            elseif ($row['status'] === 'rejected') { $rejected = (int)$row['cnt']; }
            else { $pending += (int)$row['cnt']; }
        }
    } catch (Exception $e) {}

    $ctr = $views > 0 ? round(($clicks / $views) * 100, 1) : 0;
    $cr = $clicks > 0 ? round(($approved / $clicks) * 100, 1) : 0;
    $approvalRate = ($approved + $rejected) > 0 ? round(($approved / ($approved + $rejected)) * 100, 1) : 0;
    $epc = $clicks > 0 ? round($payout / $clicks, 2) : 0;

    $funnel[] = [
        'id' => $oid,
        'title' => $o['title'],
        'category' => $o['category'],
        'logo_url' => $o['logo_url'],
        'views' => $views,
        'clicks' => $clicks,
        'approved' => $approved,
        'rejected' => $rejected,
        'pending' => $pending,
        'payout' => $payout,
        'ctr' => $ctr,
        'cr' => $cr,
        'approval_rate' => $approvalRate,
        'epc' => $epc,
    ];
}

// Сортируем по кликам DESC
usort($funnel, fn($a, $b) => $b['clicks'] <=> $a['clicks']);

// Суммарные
$totals = [
    'views' => array_sum(array_column($funnel, 'views')),
    'clicks' => array_sum(array_column($funnel, 'clicks')),
    'approved' => array_sum(array_column($funnel, 'approved')),
    'rejected' => array_sum(array_column($funnel, 'rejected')),
    'pending' => array_sum(array_column($funnel, 'pending')),
    'payout' => array_sum(array_column($funnel, 'payout')),
];
$totals['ctr'] = $totals['views'] > 0 ? round(($totals['clicks'] / $totals['views']) * 100, 1) : 0;
$totals['cr'] = $totals['clicks'] > 0 ? round(($totals['approved'] / $totals['clicks']) * 100, 1) : 0;
$totals['approval_rate'] = ($totals['approved'] + $totals['rejected']) > 0 ? round(($totals['approved'] / ($totals['approved'] + $totals['rejected'])) * 100, 1) : 0;
$totals['epc'] = $totals['clicks'] > 0 ? round($totals['payout'] / $totals['clicks'], 2) : 0;

apiCacheEnd(['funnel' => $funnel, 'totals' => $totals, 'period' => $period]);

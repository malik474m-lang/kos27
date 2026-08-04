<?php
if (apiCacheStart('admin_funnel', 20)) exit;

$db = getDB();
$clickDateColumn = dbDateColumn('click_stats', ['clicked_at', 'created_at']);
$pageViewDateColumn = dbDateColumn('page_views', ['viewed_at', 'created_at']);
$postbackDateColumn = dbDateColumn('postback_conversions', ['created_at']);
$period = $_GET['period'] ?? '30';
$period = max(1, min(365, (int)$period));

$action = $_GET['action'] ?? 'default';
$debugOfferId = max(0, (int)($_GET['offer_id'] ?? 0));

if ($action === 'debug' && $debugOfferId > 0) {
    $offerStmt = $db->prepare("SELECT id, title, slug FROM offers WHERE id = ? LIMIT 1");
    $offerStmt->execute([$debugOfferId]);
    $offer = $offerStmt->fetch();
    if (!$offer) { echo json_encode(['error' => 'Оффер не найден']); exit; }

    $viewStmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $viewStmt->execute(['/offer/' . $offer['slug']]);
    $views = (int)$viewStmt->fetch()['cnt'];

    $clickStmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $clickStmt->execute([$debugOfferId]);
    $clicks = (int)$clickStmt->fetch()['cnt'];

    $convStmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND {$postbackDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status");
    $convStmt->execute([$debugOfferId]);
    $convRows = $convStmt->fetchAll();

    $sampleClicks = $db->prepare("SELECT id, {$clickDateColumn} as dt, ip, utm_source FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) ORDER BY {$clickDateColumn} DESC LIMIT 10");
    $sampleClicks->execute([$debugOfferId]);
    $sampleClicks = $sampleClicks->fetchAll();

    $sampleViews = $db->prepare("SELECT page, {$pageViewDateColumn} as dt, ip FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) ORDER BY {$pageViewDateColumn} DESC LIMIT 10");
    $sampleViews->execute(['/offer/' . $offer['slug']]);
    $sampleViews = $sampleViews->fetchAll();

    $sampleConversions = $db->prepare("SELECT id, status, payout, {$postbackDateColumn} as dt, click_id, aff_sub FROM postback_conversions WHERE offer_id = ? AND {$postbackDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) ORDER BY {$postbackDateColumn} DESC LIMIT 10");
    $sampleConversions->execute([$debugOfferId]);
    $sampleConversions = $sampleConversions->fetchAll();

    echo json_encode([
        'offer' => $offer,
        'period' => $period,
        'columns' => [
            'page_views' => $pageViewDateColumn,
            'click_stats' => $clickDateColumn,
            'postback_conversions' => $postbackDateColumn,
        ],
        'counts' => [
            'views' => $views,
            'clicks' => $clicks,
            'conversions' => $convRows,
        ],
        'samples' => [
            'views' => $sampleViews,
            'clicks' => $sampleClicks,
            'conversions' => $sampleConversions,
        ],
    ]);
    exit;
}

$offers = $db->query("SELECT id, title, slug, category, logo_url FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

$funnel = [];
foreach ($offers as $o) {
    $oid = (int)$o['id'];
    $slug = $o['slug'];

    $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $vstmt->execute(['/offer/' . $slug]);
    $views = (int)$vstmt->fetch()['cnt'];

    $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
    $cstmt->execute([$oid]);
    $clicks = (int)$cstmt->fetch()['cnt'];

    $approved = 0; $rejected = 0; $pending = 0; $payout = 0;
    try {
        $pstmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND {$postbackDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status");
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

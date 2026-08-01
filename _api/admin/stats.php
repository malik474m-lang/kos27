<?php
if (apiCacheStart('admin_stats', 20)) exit;

$db = getDB();
$period = $_GET['period'] ?? '30';
$period = max(1, min(365, (int)$period));
$clickDateColumn = dbDateColumn('click_stats', ['clicked_at', 'created_at']);
$pageViewDateColumn = dbDateColumn('page_views', ['viewed_at', 'created_at']);

// Основные счётчики
$offers = $db->query("SELECT COUNT(*) as cnt FROM offers WHERE is_active = 1")->fetch()['cnt'];
$articles = $db->query("SELECT COUNT(*) as cnt FROM articles WHERE is_published = 1")->fetch()['cnt'];
$reviews = $db->query("SELECT COUNT(*) as cnt FROM reviews")->fetch()['cnt'];
$subscribers = $db->query("SELECT COUNT(*) as cnt FROM subscribers WHERE is_active = 1")->fetch()['cnt'];
$clicksToday = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE {$clickDateColumn} >= CURDATE()")->fetch()['cnt'];
$clicksWeek = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE {$clickDateColumn} >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch()['cnt'];
$clicksMonth = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE {$clickDateColumn} >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch()['cnt'];
$clicksTotal = $db->query("SELECT COUNT(*) as cnt FROM click_stats")->fetch()['cnt'];

// Топ офферов с конверсией
$topOffers = $db->query("
    SELECT o.id, o.title,
        COUNT(c.id) as clicks,
        (SELECT COUNT(*) FROM page_views pv WHERE pv.page = CONCAT('/offer/', o.slug) AND pv.{$pageViewDateColumn} >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)) as views
    FROM click_stats c
    JOIN offers o ON c.offer_id = o.id
    WHERE c.{$clickDateColumn} >= DATE_SUB(CURDATE(), INTERVAL {$period} DAY)
    GROUP BY o.id, o.title
    ORDER BY clicks DESC LIMIT 20
")->fetchAll();

foreach ($topOffers as &$o) {
    $o['views'] = (int)$o['views'];
    $o['clicks'] = (int)$o['clicks'];
    $o['conversion'] = $o['views'] > 0 ? round(($o['clicks'] / $o['views']) * 100, 1) : 0;
}
unset($o);

$chartClicks = $db->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as cnt
    FROM click_stats
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$chartClicks->execute([$period]);
$chartData = $chartClicks->fetchAll();

$chartViews = [];
try {
    $pvStmt = $db->prepare("
        SELECT DATE({$pageViewDateColumn}) as day, COUNT(*) as cnt
        FROM page_views
        WHERE {$pageViewDateColumn} >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE({$pageViewDateColumn})
        ORDER BY day ASC
    ");
    $pvStmt->execute([$period]);
    $chartViews = $pvStmt->fetchAll();
} catch (Exception $e) {}

$utmSources = $db->prepare("
    SELECT utm_source, utm_medium, utm_campaign, COUNT(*) as clicks
    FROM click_stats
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
      AND utm_source IS NOT NULL AND utm_source != ''
    GROUP BY utm_source, utm_medium, utm_campaign
    ORDER BY clicks DESC LIMIT 30
");
$utmSources->execute([$period]);
$utmData = $utmSources->fetchAll();

$hourly = $db->query("
    SELECT HOUR(created_at) as h, COUNT(*) as cnt
    FROM click_stats
    WHERE {$clickDateColumn} >= CURDATE()
    GROUP BY HOUR(created_at)
    ORDER BY h ASC
")->fetchAll();

$lastHour = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetch()['cnt'];
$last5min = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetch()['cnt'];

$statsPayload = [
    'offers' => (int)$offers,
    'articles' => (int)$articles,
    'reviews' => (int)$reviews,
    'subscribers' => (int)$subscribers,
    'clicksToday' => (int)$clicksToday,
    'clicksWeek' => (int)$clicksWeek,
    'clicksMonth' => (int)$clicksMonth,
    'clicksTotal' => (int)$clicksTotal,
    'topOffers' => $topOffers,
    'chartClicks' => $chartData,
    'chartViews' => $chartViews,
    'utmSources' => $utmData,
    'hourly' => $hourly,
    'lastHour' => (int)$lastHour,
    'last5min' => (int)$last5min,
    'period' => $period,
];

apiCacheEnd($statsPayload);

<?php
require_once __DIR__ . '/../../includes/audit-log.php';
require_once __DIR__ . '/../../includes/page-cache.php';

$db = getDB();
$clickDateColumn = dbDateColumn('click_stats', ['clicked_at', 'created_at']);
$pageViewDateColumn = dbDateColumn('page_views', ['viewed_at', 'created_at']);
$method = $_SERVER['REQUEST_METHOD'];
$period = max(1, min(365, (int)($_GET['period'] ?? 30)));

function collectOfferMetrics(PDO $db, int $period, string $clickDateColumn, string $pageViewDateColumn): array {
    $offers = $db->query("SELECT id, title, slug, category, logo_url, rating, review_count, sort_order, created_at FROM offers WHERE is_active = 1 ORDER BY category ASC, sort_order ASC, id ASC")->fetchAll();
    $result = [];

    foreach ($offers as $o) {
        $oid = (int)$o['id'];
        $slug = $o['slug'];

        $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
        $vstmt->execute(['/offer/' . $slug]);
        $views = (int)$vstmt->fetch()['cnt'];

        $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL $period DAY)");
        $cstmt->execute([$oid]);
        $clicks = (int)$cstmt->fetch()['cnt'];

        $approved = 0; $rejected = 0; $payout = 0.0;
        try {
            $pstmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL $period DAY) GROUP BY status");
            $pstmt->execute([$oid]);
            foreach ($pstmt->fetchAll() as $row) {
                if ($row['status'] === 'approved') { $approved = (int)$row['cnt']; $payout = (float)$row['total']; }
                elseif ($row['status'] === 'rejected') { $rejected = (int)$row['cnt']; }
            }
        } catch (Exception $e) {}

        $ctr = $views > 0 ? ($clicks / $views) * 100 : 0;
        $approvalRate = ($approved + $rejected) > 0 ? ($approved / ($approved + $rejected)) * 100 : 0;
        $epc = $clicks > 0 ? $payout / $clicks : 0;
        $reviewStrength = ((float)$o['rating']) * log(((int)$o['review_count']) + 1, 2);
        $daysOld = max(0, floor((time() - strtotime($o['created_at'])) / 86400));
        $freshness = max(0, 100 - min(100, $daysOld / 3)); // чем новее, тем выше, но слабый вес

        $result[] = [
            'id' => $oid,
            'title' => $o['title'],
            'slug' => $o['slug'],
            'category' => $o['category'],
            'logo_url' => $o['logo_url'],
            'rating' => (float)$o['rating'],
            'review_count' => (int)$o['review_count'],
            'sort_order' => (int)$o['sort_order'],
            'views' => $views,
            'clicks' => $clicks,
            'approved' => $approved,
            'rejected' => $rejected,
            'payout' => round($payout, 2),
            'ctr' => round($ctr, 1),
            'approval_rate' => round($approvalRate, 1),
            'epc' => round($epc, 2),
            'review_strength' => round($reviewStrength, 2),
            'freshness' => round($freshness, 1),
        ];
    }

    return $result;
}

function scoreOffersByCategory(array $rows): array {
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[$row['category']][] = $row;
    }

    $scored = [];
    foreach ($grouped as $category => $items) {
        $maxClicks = max(1, ...array_map(fn($x) => (int)$x['clicks'], $items));
        $maxCtr = max(1, ...array_map(fn($x) => (float)$x['ctr'], $items));
        $maxApproval = max(1, ...array_map(fn($x) => (float)$x['approval_rate'], $items));
        $maxEpc = max(1, ...array_map(fn($x) => (float)$x['epc'], $items));
        $maxReview = max(1, ...array_map(fn($x) => (float)$x['review_strength'], $items));
        $maxFresh = max(1, ...array_map(fn($x) => (float)$x['freshness'], $items));

        foreach ($items as $row) {
            $clicksNorm = ((int)$row['clicks'] / $maxClicks) * 25;
            $ctrNorm = ((float)$row['ctr'] / $maxCtr) * 20;
            $approvalNorm = ((float)$row['approval_rate'] / $maxApproval) * 20;
            $epcNorm = ((float)$row['epc'] / $maxEpc) * 20;
            $reviewNorm = ((float)$row['review_strength'] / $maxReview) * 10;
            $freshNorm = ((float)$row['freshness'] / $maxFresh) * 5;
            $score = round($clicksNorm + $ctrNorm + $approvalNorm + $epcNorm + $reviewNorm + $freshNorm, 1);

            $row['smart_score'] = $score;
            $row['score_parts'] = [
                'clicks' => round($clicksNorm, 1),
                'ctr' => round($ctrNorm, 1),
                'approval' => round($approvalNorm, 1),
                'epc' => round($epcNorm, 1),
                'reviews' => round($reviewNorm, 1),
                'freshness' => round($freshNorm, 1),
            ];
            $scored[] = $row;
        }
    }

    // сортировка по категории, потом score DESC
    usort($scored, function($a, $b) {
        if ($a['category'] !== $b['category']) return strcmp($a['category'], $b['category']);
        if ($a['smart_score'] !== $b['smart_score']) return $b['smart_score'] <=> $a['smart_score'];
        return strcmp($a['title'], $b['title']);
    });

    return $scored;
}

if ($method === 'GET') {
    $scored = scoreOffersByCategory(collectOfferMetrics($db, $period, $clickDateColumn, $pageViewDateColumn));
    apiCacheEnd(['period' => $period, 'items' => $scored]);
    exit;
}

if ($method === 'POST') {
    $scored = scoreOffersByCategory(collectOfferMetrics($db, $period, $clickDateColumn, $pageViewDateColumn));
    $byCategory = [];
    foreach ($scored as $row) $byCategory[$row['category']][] = $row;

    $stmt = $db->prepare("UPDATE offers SET sort_order = ? WHERE id = ?");
    foreach ($byCategory as $category => $rows) {
        usort($rows, fn($a, $b) => $b['smart_score'] <=> $a['smart_score']);
        foreach ($rows as $i => $row) {
            $stmt->execute([$i, $row['id']]);
        }
    }

    pageCacheClear();

    // Аудит
    auditLog('apply', 'smart_rating', null, 'Период: ' . $period . ' дней', ['offers_count' => count($scored)]);
    echo json_encode(['success' => true, 'message' => 'Умная сортировка применена', 'period' => $period]);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);

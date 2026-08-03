<?php
/**
 * API позиций в поисковых системах
 * Данные из Яндекс.Вебмастер и Google Search Console
 */
requireAdmin();
require_once __DIR__ . '/../../includes/yandex-webmaster.php';
require_once __DIR__ . '/../../includes/google-indexing.php';

$db = getDB();
$action = $_GET['action'] ?? 'combined';
$days = max(1, min(90, (int)($_GET['days'] ?? 7)));
$limit = max(10, min(500, (int)($_GET['limit'] ?? 100)));
$urlFilter = trim((string)($_GET['url'] ?? ''));
$queryFilter = trim((string)($_GET['query'] ?? ''));

switch ($action) {

case 'yandex':
    $cfg = getYandexWebmasterConfig();
    if (!$cfg || empty($cfg['oauth_token'])) {
        echo json_encode(['error' => 'Яндекс.Вебмастер не настроен']); exit;
    }
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
    $dateTo = date('Y-m-d', strtotime('-1 day'));

    $url = 'https://api.webmaster.yandex.net/v4/user/' . rawurlencode((string)$cfg['user_id'])
         . '/hosts/' . rawurlencode((string)$cfg['host_id'])
         . '/search-queries/popular?order_by=TOTAL_CLICKS'
         . '&query_indicator=TOTAL_SHOWS&query_indicator=TOTAL_CLICKS&query_indicator=AVG_SHOW_POSITION&query_indicator=AVG_CLICK_POSITION'
         . '&date_from=' . $dateFrom . '&date_to=' . $dateTo
         . '&limit=' . $limit;

    $result = yandexWebmasterApiRequest('GET', $url);
    if (!$result['success']) {
        echo json_encode(['error' => 'Yandex API error', 'status' => $result['status']]); exit;
    }

    $queries = [];
    foreach (($result['data']['queries'] ?? []) as $q) {
        $ind = $q['indicators'] ?? [];
        if ($queryFilter && mb_stripos($q['query_text'], $queryFilter) === false) continue;
        $queries[] = [
            'query' => $q['query_text'],
            'clicks' => (int)($ind['TOTAL_CLICKS'] ?? 0),
            'shows' => (int)($ind['TOTAL_SHOWS'] ?? 0),
            'position' => round((float)($ind['AVG_SHOW_POSITION'] ?? 0), 1),
            'click_position' => round((float)($ind['AVG_CLICK_POSITION'] ?? 0), 1),
            'ctr' => ($ind['TOTAL_SHOWS'] ?? 0) > 0 ? round(($ind['TOTAL_CLICKS'] ?? 0) / $ind['TOTAL_SHOWS'] * 100, 1) : 0,
        ];
    }

    echo json_encode([
        'source' => 'yandex',
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'total' => count($queries),
        'queries' => $queries,
    ]);
    break;

case 'google':
    $token = generateGoogleSearchConsoleToken();
    if (!$token) {
        echo json_encode(['error' => 'Google Search Console не настроен. Проверьте ключ сервисного аккаунта.']); exit;
    }

    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
    $dateTo = date('Y-m-d', strtotime('-1 day'));

    $body = [
        'startDate' => $dateFrom,
        'endDate' => $dateTo,
        'dimensions' => ['query'],
        'rowLimit' => $limit,
        'startRow' => 0,
    ];
    if ($urlFilter) {
        $body['dimensionFilterGroups'] = [[
            'filters' => [['dimension' => 'page', 'operator' => 'contains', 'expression' => $urlFilter]]
        ]];
    }

    $ch = curl_init('https://www.googleapis.com/webmasters/v3/sites/' . urlencode(SITE_URL) . '/searchAnalytics/query');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['error' => 'Google API error', 'status' => $httpCode, 'response' => json_decode($response, true)]); exit;
    }

    $data = json_decode($response, true);
    $queries = [];
    foreach (($data['rows'] ?? []) as $row) {
        $q = $row['keys'][0] ?? '';
        if ($queryFilter && mb_stripos($q, $queryFilter) === false) continue;
        $queries[] = [
            'query' => $q,
            'clicks' => (int)($row['clicks'] ?? 0),
            'shows' => (int)($row['impressions'] ?? 0),
            'position' => round((float)($row['position'] ?? 0), 1),
            'ctr' => round(($row['ctr'] ?? 0) * 100, 1),
        ];
    }

    echo json_encode([
        'source' => 'google',
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'total' => count($queries),
        'queries' => $queries,
    ]);
    break;

case 'combined':
    // Оба источника параллельно
    $yandexData = null;
    $googleData = null;

    // Yandex
    $cfg = getYandexWebmasterConfig();
    if ($cfg && !empty($cfg['oauth_token'])) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        $url = 'https://api.webmaster.yandex.net/v4/user/' . rawurlencode((string)$cfg['user_id'])
             . '/hosts/' . rawurlencode((string)$cfg['host_id'])
             . '/search-queries/popular?order_by=TOTAL_CLICKS'
             . '&query_indicator=TOTAL_SHOWS&query_indicator=TOTAL_CLICKS&query_indicator=AVG_SHOW_POSITION&query_indicator=AVG_CLICK_POSITION'
             . '&date_from=' . $dateFrom . '&date_to=' . $dateTo . '&limit=' . $limit;
        $result = yandexWebmasterApiRequest('GET', $url);
        if ($result['success']) {
            $yandexData = [];
            foreach (($result['data']['queries'] ?? []) as $q) {
                $ind = $q['indicators'] ?? [];
                if ($queryFilter && mb_stripos($q['query_text'], $queryFilter) === false) continue;
                $yandexData[] = [
                    'query' => $q['query_text'],
                    'clicks' => (int)($ind['TOTAL_CLICKS'] ?? 0),
                    'shows' => (int)($ind['TOTAL_SHOWS'] ?? 0),
                    'position' => round((float)($ind['AVG_SHOW_POSITION'] ?? 0), 1),
                    'ctr' => ($ind['TOTAL_SHOWS'] ?? 0) > 0 ? round(($ind['TOTAL_CLICKS'] ?? 0) / $ind['TOTAL_SHOWS'] * 100, 1) : 0,
                ];
            }
        }
    }

    // Google
    $token = generateGoogleSearchConsoleToken();
    if ($token) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        $body = ['startDate' => $dateFrom, 'endDate' => $dateTo, 'dimensions' => ['query'], 'rowLimit' => $limit];
        if ($urlFilter) {
            $body['dimensionFilterGroups'] = [['filters' => [['dimension' => 'page', 'operator' => 'contains', 'expression' => $urlFilter]]]];
        }
        $ch = curl_init('https://www.googleapis.com/webmasters/v3/sites/' . urlencode(SITE_URL) . '/searchAnalytics/query');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $googleData = [];
            foreach ((json_decode($response, true)['rows'] ?? []) as $row) {
                $q = $row['keys'][0] ?? '';
                if ($queryFilter && mb_stripos($q, $queryFilter) === false) continue;
                $googleData[] = [
                    'query' => $q,
                    'clicks' => (int)($row['clicks'] ?? 0),
                    'shows' => (int)($row['impressions'] ?? 0),
                    'position' => round((float)($row['position'] ?? 0), 1),
                    'ctr' => round(($row['ctr'] ?? 0) * 100, 1),
                ];
            }
        }
    }

    echo json_encode([
        'yandex' => $yandexData,
        'google' => $googleData,
        'days' => $days,
    ]);
    break;

case 'by-page':
    // Позиции по страницам (Google SC)
    $token = generateGoogleSearchConsoleToken();
    if (!$token) { echo json_encode(['error' => 'Google SC не настроен']); exit; }
    $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
    $dateTo = date('Y-m-d', strtotime('-1 day'));
    $body = ['startDate' => $dateFrom, 'endDate' => $dateTo, 'dimensions' => ['page'], 'rowLimit' => $limit];
    $ch = curl_init('https://www.googleapis.com/webmasters/v3/sites/' . urlencode(SITE_URL) . '/searchAnalytics/query');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $response = curl_exec($ch); $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($httpCode !== 200) { echo json_encode(['error' => 'Google error', 'status' => $httpCode]); exit; }
    $pages = [];
    foreach ((json_decode($response, true)['rows'] ?? []) as $row) {
        $pageUrl = $row['keys'][0] ?? '';
        $path = str_replace(SITE_URL, '', $pageUrl);
        $pages[] = [
            'url' => $path,
            'clicks' => (int)($row['clicks'] ?? 0),
            'shows' => (int)($row['impressions'] ?? 0),
            'position' => round((float)($row['position'] ?? 0), 1),
            'ctr' => round(($row['ctr'] ?? 0) * 100, 1),
        ];
    }
    echo json_encode(['source' => 'google', 'pages' => $pages, 'days' => $days]);
    break;

case 'funnel-map':
    // Полная карта: запрос → страница → клики на оффер → конверсия
    $clickDateColumn = dbDateColumn('click_stats', ['clicked_at', 'created_at']);
    $offers = $db->query("SELECT id, title, slug FROM offers WHERE is_active = 1")->fetchAll();
    $map = [];
    foreach ($offers as $o) {
        $slug = $o['slug'];
        $oid = (int)$o['id'];
        // Просмотры страницы оффера
        $pageViewCol = dbDateColumn('page_views', ['viewed_at', 'created_at']);
        $vstmt = $db->prepare("SELECT COUNT(*) as cnt FROM page_views WHERE page = ? AND {$pageViewCol} >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $vstmt->execute(['/offer/' . $slug, $days]);
        $views = (int)$vstmt->fetch()['cnt'];
        // Клики
        $cstmt = $db->prepare("SELECT COUNT(*) as cnt FROM click_stats WHERE offer_id = ? AND {$clickDateColumn} >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $cstmt->execute([$oid, $days]);
        $clicks = (int)$cstmt->fetch()['cnt'];
        // Конверсии
        $approved = 0; $rejected = 0; $revenue = 0;
        try {
            $pstmt = $db->prepare("SELECT status, COUNT(*) as cnt, SUM(payout) as total FROM postback_conversions WHERE offer_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY status");
            $pstmt->execute([$oid, $days]);
            foreach ($pstmt->fetchAll() as $row) {
                if ($row['status'] === 'approved') { $approved = (int)$row['cnt']; $revenue = (float)$row['total']; }
                elseif ($row['status'] === 'rejected') { $rejected = (int)$row['cnt']; }
            }
        } catch (Exception $e) {}
        if ($views === 0 && $clicks === 0) continue;
        $map[] = [
            'offer_id' => $oid,
            'title' => $o['title'],
            'slug' => $slug,
            'views' => $views,
            'clicks' => $clicks,
            'approved' => $approved,
            'rejected' => $rejected,
            'revenue' => $revenue,
            'view_to_click' => $views > 0 ? round($clicks / $views * 100, 1) : 0,
            'click_to_conv' => $clicks > 0 ? round($approved / $clicks * 100, 1) : 0,
            'epc' => $clicks > 0 ? round($revenue / $clicks, 2) : 0,
        ];
    }
    usort($map, fn($a, $b) => $b['views'] <=> $a['views']);
    echo json_encode(['map' => $map, 'days' => $days]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

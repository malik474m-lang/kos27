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
    $token = generateGoogleJWT();
    if (!$token) {
        echo json_encode(['error' => 'Google не настроен']); exit;
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
    $token = generateGoogleJWT();
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

default:
    echo json_encode(['error' => 'Unknown action']);
}

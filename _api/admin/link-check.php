<?php
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

function checkOfferUrl(string $url): array {
    $start = microtime(true);
    $result = [
        'http_code' => 0,
        'status' => 'error',
        'final_url' => null,
        'response_time_ms' => 0,
        'error_message' => null,
    ];

    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        $result['status'] = 'broken';
        $result['error_message'] = 'Некорректный URL';
        return $result;
    }

    if (!function_exists('curl_init')) {
        $result['error_message'] = 'cURL недоступен на сервере';
        return $result;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; KosmozaimLinkChecker/1.0; +https://kosmozaim.ru)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADER => false,
    ]);

    curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    $result['http_code'] = $code;
    $result['final_url'] = $final ?: $url;
    $result['response_time_ms'] = (int)round((microtime(true) - $start) * 1000);

    if ($errno === CURLE_OPERATION_TIMEDOUT) {
        $result['status'] = 'timeout';
        $result['error_message'] = $error ?: 'Timeout';
    } elseif ($errno) {
        $result['status'] = 'error';
        $result['error_message'] = $error ?: ('cURL error ' . $errno);
    } elseif ($code >= 200 && $code < 300) {
        $result['status'] = 'ok';
    } elseif ($code >= 300 && $code < 400) {
        $result['status'] = 'redirect';
    } elseif ($code >= 400 || $code === 0) {
        $result['status'] = 'broken';
        $result['error_message'] = 'HTTP ' . $code;
    }

    return $result;
}

function saveLinkCheck(PDO $db, int $offerId, string $url, array $check): void {
    $db->prepare("DELETE FROM offer_link_checks WHERE offer_id = ?")->execute([$offerId]);
    $db->prepare("INSERT INTO offer_link_checks (offer_id, url, http_code, status, final_url, response_time_ms, error_message) VALUES (?,?,?,?,?,?,?)")
       ->execute([$offerId, $url, $check['http_code'], $check['status'], $check['final_url'], $check['response_time_ms'], $check['error_message']]);
}

if ($action === 'list' && $method === 'GET') {
    $rows = $db->query("
        SELECT o.id as offer_id, o.title, o.category, o.affiliate_url, o.is_active,
               lc.http_code, lc.status, lc.final_url, lc.response_time_ms, lc.error_message, lc.checked_at
        FROM offers o
        LEFT JOIN offer_link_checks lc ON lc.offer_id = o.id
        ORDER BY o.category ASC, o.sort_order ASC, o.id ASC
    ")->fetchAll();
    echo json_encode($rows);
    exit;
}

if ($action === 'check-one' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $offerId = (int)($data['offerId'] ?? 0);
    $stmt = $db->prepare("SELECT affiliate_url FROM offers WHERE id = ? LIMIT 1");
    $stmt->execute([$offerId]);
    $url = $stmt->fetchColumn();
    if (!$url) { http_response_code(404); echo json_encode(['error' => 'Оффер не найден']); exit; }
    $check = checkOfferUrl($url);
    saveLinkCheck($db, $offerId, $url, $check);
    echo json_encode(['success' => true, 'check' => $check]);
    exit;
}

if ($action === 'check-all' && $method === 'POST') {
    $offers = $db->query("SELECT id, affiliate_url FROM offers WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
    $stats = ['total' => count($offers), 'ok' => 0, 'redirect' => 0, 'broken' => 0, 'timeout' => 0, 'error' => 0];
    foreach ($offers as $offer) {
        $check = checkOfferUrl($offer['affiliate_url']);
        saveLinkCheck($db, (int)$offer['id'], $offer['affiliate_url'], $check);
        if (isset($stats[$check['status']])) $stats[$check['status']]++;
        else $stats['error']++;
        usleep(150000);
    }
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

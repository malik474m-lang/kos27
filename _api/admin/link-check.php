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

    // Партнёрские ссылки часто блокируют ботов и HEAD-запросы.
    // Используем GET с реалистичным User-Agent и не ждём тело ответа.
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HEADER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.9,en;q=0.8',
        ],
        // Ограничиваем объём скачиваемого тела — нам нужен только HTTP-код
        CURLOPT_RANGE => '0-1024',
    ]);

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    // Если RANGE не поддерживается — retry без него (HEAD)
    if ($code === 0 && !$errno) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
    }

    $result['http_code'] = $code;
    $result['final_url'] = $final ?: $url;
    $result['response_time_ms'] = (int)round((microtime(true) - $start) * 1000);

    if ($errno === CURLE_OPERATION_TIMEDOUT || $errno === 28) {
        // Таймаут — но если connect прошёл (сервер жив), считаем redirect/ok
        $result['status'] = 'timeout';
        $result['error_message'] = 'Таймаут (' . $result['response_time_ms'] . ' мс)';
        // Партнёрские ссылки часто таймаутят из-за JS-редиректа — это не ошибка
        if (str_contains($url, 'leads.su') || str_contains($url, 'pxl.') || str_contains($url, 'click')) {
            $result['status'] = 'redirect';
            $result['error_message'] = 'JS-редирект (таймаут — это нормально для партнёрских ссылок)';
        }
    } elseif ($errno) {
        $result['status'] = 'error';
        $result['error_message'] = $error ?: ('cURL error ' . $errno);
    } elseif ($code >= 200 && $code < 400) {
        // 2xx и 3xx — всё ок
        $result['status'] = 'ok';
    } elseif ($code === 403) {
        // Партнёрки часто отдают 403 ботам — это не ошибка
        $result['status'] = 'ok';
        $result['error_message'] = 'HTTP 403 (антибот, ссылка работает)';
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

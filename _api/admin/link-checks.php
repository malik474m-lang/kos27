<?php
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

function checkAffiliateUrl(string $url): array {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'Некорректный URL'];
    }

    if (function_exists('curl_init')) {
        $attempts = [true, false]; // сперва HEAD, затем GET
        foreach ($attempts as $nobody) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_NOBODY => $nobody,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_USERAGENT => 'Kosmozaim-LinkChecker/1.0',
                CURLOPT_HEADER => false,
            ]);
            curl_exec($ch);
            $curlErr = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            $code = (int)($info['http_code'] ?? 0);
            $final = $info['url'] ?? $url;
            $redirects = (int)($info['redirect_count'] ?? 0);

            if ($code > 0 && $code !== 405) {
                return [
                    'http_code' => $code,
                    'final_url' => $final,
                    'redirect_count' => $redirects,
                    'is_ok' => ($code >= 200 && $code < 400) ? 1 : 0,
                    'error_message' => ($code >= 200 && $code < 400) ? null : ('HTTP ' . $code),
                ];
            }

            if (!$nobody) {
                return [
                    'http_code' => $code,
                    'final_url' => $final,
                    'redirect_count' => $redirects,
                    'is_ok' => ($code >= 200 && $code < 400) ? 1 : 0,
                    'error_message' => $curlErr ?: ($code ? ('HTTP ' . $code) : 'Нет ответа'),
                ];
            }
        }
    }

    return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'cURL недоступен'];
}

if ($method === 'GET' && $action === 'list') {
    if (apiCacheStart('admin_link_checks', 20)) exit;

    $rows = $db->query("\n        SELECT o.id as offer_id, o.title, o.affiliate_url, lc.http_code, lc.final_url, lc.redirect_count, lc.is_ok, lc.error_message, lc.checked_at\n        FROM offers o\n        LEFT JOIN (\n            SELECT t1.* FROM offer_link_checks t1\n            INNER JOIN (SELECT offer_id, MAX(id) as max_id FROM offer_link_checks GROUP BY offer_id) t2 ON t1.id = t2.max_id\n        ) lc ON lc.offer_id = o.id\n        WHERE o.is_active = 1\n        ORDER BY o.category ASC, o.sort_order ASC, o.id ASC\n    ")->fetchAll();

    $summary = [
        'total' => count($rows),
        'ok' => count(array_filter($rows, fn($r) => (int)($r['is_ok'] ?? 0) === 1)),
        'broken' => count(array_filter($rows, fn($r) => isset($r['is_ok']) && (int)$r['is_ok'] === 0 && !empty($r['checked_at']))),
        'unchecked' => count(array_filter($rows, fn($r) => empty($r['checked_at']))),
    ];

    apiCacheEnd(['summary' => $summary, 'items' => $rows]);
    exit;
}

if ($method === 'POST' && $action === 'run') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $offerId = (int)($data['offerId'] ?? 0);

    if ($offerId > 0) {
        $stmt = $db->prepare("SELECT id, affiliate_url FROM offers WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$offerId]);
        $offers = $stmt->fetchAll();
    } else {
        $offers = $db->query("SELECT id, affiliate_url FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
    }

    $insert = $db->prepare("INSERT INTO offer_link_checks (offer_id, url, http_code, final_url, redirect_count, is_ok, error_message) VALUES (?,?,?,?,?,?,?)");

    $checked = 0; $broken = 0;
    foreach ($offers as $offer) {
        $result = checkAffiliateUrl((string)$offer['affiliate_url']);
        $insert->execute([
            (int)$offer['id'],
            (string)$offer['affiliate_url'],
            $result['http_code'],
            $result['final_url'],
            $result['redirect_count'],
            $result['is_ok'],
            $result['error_message'],
        ]);
        $checked++;
        if (!(int)$result['is_ok']) $broken++;
        if ($offerId === 0) usleep(100000);
    }

    apiCacheClear();
    echo json_encode(['success' => true, 'checked' => $checked, 'broken' => $broken]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

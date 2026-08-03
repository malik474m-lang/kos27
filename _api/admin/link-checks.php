<?php
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

function checkAffiliateUrlOnce(string $url, bool $head = true): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $head ? 15 : 20,
        CURLOPT_NOBODY => $head,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
        CURLOPT_HEADER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
    ]);
    if (!$head) {
        curl_setopt($ch, CURLOPT_RANGE, '0-1024');
    }
    curl_exec($ch);
    $curlErr = curl_error($ch);
    $curlErrNo = curl_errno($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return [
        'http_code' => (int)($info['http_code'] ?? 0),
        'final_url' => $info['url'] ?? $url,
        'redirect_count' => (int)($info['redirect_count'] ?? 0),
        'error_message' => $curlErr ?: null,
        'curl_errno' => (int)$curlErrNo,
        'mode' => $head ? 'HEAD' : 'GET',
    ];
}

function checkAffiliateUrl(string $url): array {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'Некорректный URL'];
    }

    if (!function_exists('curl_init')) {
        return ['http_code' => 0, 'final_url' => $url, 'redirect_count' => 0, 'is_ok' => 0, 'error_message' => 'cURL недоступен'];
    }

    $isPartner = str_contains($url, 'leads.su') || str_contains($url, 'pxl.') || str_contains($url, '/click/') || str_contains($url, 'aff') || str_contains($url, 'utm_');

    // 1) HEAD
    $head = checkAffiliateUrlOnce($url, true);
    if ($head['http_code'] >= 200 && $head['http_code'] < 400) {
        return [
            'http_code' => $head['http_code'],
            'final_url' => $head['final_url'],
            'redirect_count' => $head['redirect_count'],
            'is_ok' => 1,
            'error_message' => $head['redirect_count'] > 0 ? ('Редиректов: ' . $head['redirect_count']) : null,
        ];
    }

    // 2) GET with small range
    $get = checkAffiliateUrlOnce($url, false);
    if ($get['http_code'] >= 200 && $get['http_code'] < 400) {
        return [
            'http_code' => $get['http_code'],
            'final_url' => $get['final_url'],
            'redirect_count' => $get['redirect_count'],
            'is_ok' => 1,
            'error_message' => $get['redirect_count'] > 0 ? ('Редиректов: ' . $get['redirect_count']) : null,
        ];
    }

    // 3) Для партнёрок 403 / timeout / anti-bot — это нормальная ситуация
    if ($isPartner) {
        if ($get['http_code'] === 403 || $head['http_code'] === 403) {
            return [
                'http_code' => $get['http_code'] ?: $head['http_code'],
                'final_url' => $get['final_url'] ?: $head['final_url'] ?: $url,
                'redirect_count' => max((int)$get['redirect_count'], (int)$head['redirect_count']),
                'is_ok' => 1,
                'error_message' => 'Антибот-защита партнёрки (403), ссылка может работать в браузере',
            ];
        }
        if (in_array((int)($get['curl_errno'] ?? 0), [28], true) || in_array((int)($head['curl_errno'] ?? 0), [28], true)) {
            return [
                'http_code' => $get['http_code'] ?: $head['http_code'] ?: 0,
                'final_url' => $get['final_url'] ?: $head['final_url'] ?: $url,
                'redirect_count' => max((int)$get['redirect_count'], (int)$head['redirect_count']),
                'is_ok' => 1,
                'error_message' => 'Таймаут/JS-редирект партнёрки — обычно это норма',
            ];
        }
        if (($get['http_code'] === 0 && !empty($get['error_message'])) || ($head['http_code'] === 0 && !empty($head['error_message']))) {
            return [
                'http_code' => 0,
                'final_url' => $url,
                'redirect_count' => max((int)$get['redirect_count'], (int)$head['redirect_count']),
                'is_ok' => 1,
                'error_message' => 'Партнёрская ссылка не даёт техпроверку, но не считается битой',
            ];
        }
    }

    return [
        'http_code' => $get['http_code'] ?: $head['http_code'],
        'final_url' => $get['final_url'] ?: $head['final_url'] ?: $url,
        'redirect_count' => max((int)$get['redirect_count'], (int)$head['redirect_count']),
        'is_ok' => 0,
        'error_message' => $get['error_message'] ?: $head['error_message'] ?: ('HTTP ' . ($get['http_code'] ?: $head['http_code'] ?: 0)),
    ];
}

if ($method === 'GET' && $action === 'list') {
    if (apiCacheStart('admin_link_checks', 20)) exit;

    $rows = $db->query("\n        SELECT o.id as offer_id, o.title, o.category, o.affiliate_url, lc.http_code, lc.final_url, lc.redirect_count, lc.is_ok, lc.error_message, lc.checked_at\n        FROM offers o\n        LEFT JOIN (\n            SELECT t1.* FROM offer_link_checks t1\n            INNER JOIN (SELECT offer_id, MAX(id) as max_id FROM offer_link_checks GROUP BY offer_id) t2 ON t1.id = t2.max_id\n        ) lc ON lc.offer_id = o.id\n        WHERE o.is_active = 1\n        ORDER BY o.category ASC, o.sort_order ASC, o.id ASC\n    ")->fetchAll();

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

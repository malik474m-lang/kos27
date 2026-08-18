<?php
/**
 * API для Google Indexing — управление из админки
 */
requireAdmin();
require_once __DIR__ . '/../../includes/google-indexing.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

switch ($action) {

case 'status':
    echo json_encode([
        'available' => googleIndexingAvailable(),
        'service_account' => googleIndexingAvailable() ? json_decode(file_get_contents(getGoogleServiceAccountPath()), true)['client_email'] ?? '' : '',
    ]);
    break;

case 'test':
    $token = generateGoogleJWT();
    echo json_encode([
        'success' => !empty($token),
        'has_token' => !empty($token),
    ]);
    break;

case 'submit':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $urls = $data['urls'] ?? [];
    if (empty($urls)) { echo json_encode(['error' => 'No URLs']); exit; }

    $urls = array_slice($urls, 0, 50);
    $fullUrls = array_map(function($u) {
        return str_starts_with((string)($u), 'http') ? $u : SITE_URL . $u;
    }, $urls);

    $result = googleIndexBatch($fullUrls);

    // Логируем — total и success раздельно
    try {
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status, response) VALUES ('google', 'submit', ?, ?, ?, ?)")
           ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success', json_encode($result['results'])]);
    } catch (Exception $e) {
        // Fallback для старой схемы без urls_success
        try {
            $db->prepare("INSERT INTO indexing_log (service, action, urls_count, status, response) VALUES ('google', 'submit', ?, ?, ?)")
               ->execute([$result['total'], $result['failed'] > 0 ? 'partial' : 'success', json_encode($result['results'])]);
        } catch (Exception $e2) {}
    }

    // Обновляем submitted_google в url_index_tracker
    try {
        foreach ($result['results'] as $r) {
            if ($r['success']) {
                $path = str_replace(SITE_URL, '', $r['url']);
                $db->prepare("UPDATE url_index_tracker SET submitted_google = NOW() WHERE url = ?")->execute([$path]);
            }
        }
    } catch (Exception $e) {}

    echo json_encode($result);
    break;

case 'submit-new':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }

    try {
        $pending = $db->query("SELECT url FROM url_index_tracker WHERE submitted_google IS NULL OR last_modified > submitted_google ORDER BY priority DESC LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $pending = [];
    }

    if (empty($pending)) {
        echo json_encode(['success' => true, 'total' => 0, 'message' => 'Все URL уже отправлены']);
        exit;
    }

    $fullUrls = array_map(fn($u) => SITE_URL . $u, $pending);
    $result = googleIndexBatch($fullUrls);

    // Логируем — total и success раздельно
    try {
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status, response) VALUES ('google', 'submit', ?, ?, ?, ?)")
           ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success', json_encode(array_slice($result['results'], 0, 10))]);
    } catch (Exception $e) {
        try {
            $db->prepare("INSERT INTO indexing_log (service, action, urls_count, status, response) VALUES ('google', 'submit', ?, ?, ?)")
               ->execute([$result['total'], $result['failed'] > 0 ? 'partial' : 'success', json_encode(array_slice($result['results'], 0, 10))]);
        } catch (Exception $e2) {}
    }

    // Обновляем submitted_google
    try {
        foreach ($result['results'] as $r) {
            if ($r['success']) {
                $path = str_replace(SITE_URL, '', $r['url']);
                $db->prepare("UPDATE url_index_tracker SET submitted_google = NOW() WHERE url = ?")->execute([$path]);
            }
        }
    } catch (Exception $e) {}

    echo json_encode($result);
    break;

case 'check':
    $url = $_GET['url'] ?? '';
    if (!$url) { echo json_encode(['error' => 'url required']); exit; }
    $fullUrl = str_starts_with((string)($url), 'http') ? $url : SITE_URL . $url;
    echo json_encode(googleGetIndexStatus($fullUrl));
    break;

case 'upload-key':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $keyJson = $data['key'] ?? '';
    if (!$keyJson) { echo json_encode(['error' => 'Key required']); exit; }

    $parsed = json_decode($keyJson, true);
    if (!$parsed || empty($parsed['private_key']) || empty($parsed['client_email'])) {
        echo json_encode(['error' => 'Invalid service account JSON']);
        exit;
    }

    $path = getGoogleServiceAccountPath();
    file_put_contents($path, json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    chmod($path, 0600);

    echo json_encode(['success' => true, 'email' => $parsed['client_email']]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

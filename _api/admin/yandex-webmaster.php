<?php
requireAdmin();
require_once __DIR__ . '/../../includes/yandex-webmaster.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'status';

switch ($action) {
case 'status':
    $cfg = getYandexWebmasterConfig();
    echo json_encode([
        'available' => yandexWebmasterAvailable(),
        'client_id' => $cfg['client_id'] ?? '',
        'user_id' => $cfg['user_id'] ?? '',
        'host_id' => $cfg['host_id'] ?? '',
    ]);
    break;

case 'save-config':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $cfg = [
        'client_id' => trim((string)($data['client_id'] ?? '')),
        'oauth_token' => trim((string)($data['oauth_token'] ?? '')),
        'user_id' => trim((string)($data['user_id'] ?? '')),
        'host_id' => trim((string)($data['host_id'] ?? '')),
    ];
    if (!$cfg['oauth_token'] || !$cfg['user_id'] || !$cfg['host_id']) {
        echo json_encode(['error' => 'oauth_token, user_id и host_id обязательны']);
        exit;
    }
    saveYandexWebmasterConfig($cfg);
    echo json_encode(['success' => true]);
    break;

case 'test':
    echo json_encode(yandexWebmasterTestConnection());
    break;

case 'submit':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $urls = $data['urls'] ?? [];
    if (!$urls || !is_array($urls)) { echo json_encode(['error' => 'No URLs']); exit; }
    $urls = array_slice($urls, 0, 20);
    $fullUrls = array_map(fn($u) => str_starts_with((string)($u), 'http') ? $u : SITE_URL . $u, $urls);
    $result = yandexSubmitBatch($fullUrls);
    try {
        $db->query("SELECT 1 FROM indexing_log LIMIT 1");
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status, response) VALUES ('yandex', 'submit', ?, ?, ?, ?)")
           ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success', json_encode(array_slice($result['results'], 0, 10), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Exception $e) {}
    try {
        foreach ($result['results'] as $r) {
            if ($r['success']) {
                $path = str_replace(SITE_URL, '', $r['url']);
                $db->prepare("UPDATE url_index_tracker SET submitted_yandex = NOW() WHERE url = ?")->execute([$path]);
            }
        }
    } catch (Exception $e) {}
    echo json_encode($result);
    break;

case 'submit-new':
    if ($method !== 'POST') { echo json_encode(['error' => 'POST required']); exit; }
    try {
        $pending = $db->query("SELECT url FROM url_index_tracker WHERE submitted_yandex IS NULL OR last_modified > submitted_yandex ORDER BY priority DESC LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $pending = [];
    }
    if (!$pending) {
        echo json_encode(['success' => true, 'total' => 0, 'message' => 'No pending URLs']);
        exit;
    }
    $result = yandexSubmitBatch(array_map(fn($u) => SITE_URL . $u, $pending));
    try {
        $db->prepare("INSERT INTO indexing_log (service, action, urls_count, urls_success, status, response) VALUES ('yandex', 'submit', ?, ?, ?, ?)")
           ->execute([$result['total'], $result['success'], $result['failed'] > 0 ? 'partial' : 'success', json_encode(array_slice($result['results'], 0, 10), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Exception $e) {}
    try {
        foreach ($result['results'] as $r) {
            if ($r['success']) {
                $path = str_replace(SITE_URL, '', $r['url']);
                $db->prepare("UPDATE url_index_tracker SET submitted_yandex = NOW() WHERE url = ?")->execute([$path]);
            }
        }
    } catch (Exception $e) {}
    echo json_encode($result);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

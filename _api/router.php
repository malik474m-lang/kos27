<?php
require_once __DIR__ . '/../includes/api-cache.php';
header('Content-Type: application/json; charset=UTF-8');

$apiUri = substr($uri, 4); // убираем /api
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { register_shutdown_function('apiCacheClear'); }

// Публичные API
if ($apiUri === '/health') { echo json_encode(['ok' => true]); exit; }
if ($apiUri === '/subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/subscribe.php'; exit; }
if ($apiUri === '/offers') { require __DIR__ . '/offers.php'; exit; }
if ($apiUri === '/reviews' && $_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/reviews.php'; exit; }
if ($apiUri === '/nl-open') { require __DIR__ . '/nl-open.php'; exit; }
if ($apiUri === '/nl-click') { require __DIR__ . '/nl-click.php'; exit; }
if ($apiUri === '/postback') { require __DIR__ . '/postback.php'; exit; }
if ($apiUri === '/pwa-track') { require __DIR__ . '/pwa-track.php'; exit; }
// User API
if (str_starts_with($apiUri, '/user/')) {
    $userAction = substr($apiUri, 6);
    $userFile = __DIR__ . '/user/' . basename($userAction) . '.php';
    if (file_exists($userFile)) { require $userFile; exit; }
}
if ($apiUri === '/cron-generate') { require __DIR__ . '/cron-generate.php'; exit; }
if ($apiUri === '/giveaway/active') {
    try {
        $db = getDB();
        $db->query('SELECT 1 FROM giveaways LIMIT 1');
        $stmt = $db->query("SELECT id, title, prize_amount, start_at, end_at, draw_at, status FROM giveaways WHERE status IN ('active','drawing') ORDER BY created_at DESC LIMIT 1");
        $gw = $stmt->fetch();
        if ($gw) { $cnt = $db->prepare('SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?'); $cnt->execute([$gw['id']]); $gw['entries_count'] = (int)$cnt->fetch()['cnt']; }
        echo json_encode($gw ?: null);
    } catch (Exception $e) { echo json_encode(null); }
    exit;
}
if ($apiUri === '/geo') { require __DIR__ . '/geo.php'; exit; }
if ($apiUri === '/geo-redirect') { require __DIR__ . '/geo-redirect.php'; exit; }

// Админ API
if (str_starts_with($apiUri, '/admin/')) {
    require __DIR__ . '/admin-router.php';
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);

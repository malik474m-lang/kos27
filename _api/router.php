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
// User API
if (str_starts_with($apiUri, '/user/')) {
    $userAction = substr($apiUri, 6);
    $userFile = __DIR__ . '/user/' . basename($userAction) . '.php';
    if (file_exists($userFile)) { require $userFile; exit; }
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

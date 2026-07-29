<?php
/**
 * Сервер лицензирования — Роутер
 * serv.kosmozaim.ru
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');
header('X-License-Server: KZM/1.0');

// CORS для клиентских запросов
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-License-Key, X-Signature');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// API эндпоинты
if ($uri === '/api/activate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/activate.php'; exit;
}
if ($uri === '/api/verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/verify.php'; exit;
}
if ($uri === '/api/deactivate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/deactivate.php'; exit;
}
if ($uri === '/api/heartbeat' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api/heartbeat.php'; exit;
}

// Статус
if ($uri === '/' || $uri === '/api/status') {
    jsonResponse(['server' => 'KZM License Server', 'version' => '1.0', 'status' => 'online']);
}

// Админка
if (str_starts_with($uri, '/admin')) {
    if ($uri === '/admin/api' || str_starts_with($uri, '/admin/api')) {
        require __DIR__ . '/admin/api.php'; exit;
    }
    require __DIR__ . '/admin/index.php'; exit;
}

http_response_code(404);
jsonResponse(['error' => 'Endpoint not found'], 404);

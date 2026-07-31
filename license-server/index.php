<?php
require_once __DIR__ . '/config.php';
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') ?: '/';

if (strpos($uri, '/api/') === 0) { require __DIR__ . '/_api/router.php'; exit; }
if (strpos($uri, '/admin') === 0) { require __DIR__ . '/_admin/router.php'; exit; }
if ($uri === '/') { header('Location: /admin'); exit; }

http_response_code(404);
echo json_encode(['error' => 'Not Found']);

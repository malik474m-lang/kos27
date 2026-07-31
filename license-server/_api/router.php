<?php
$apiPath = substr($uri, 4); // убираем /api
if ($apiPath === '/check' || $apiPath === '/verify') { require __DIR__ . '/check.php'; exit; }
if ($apiPath === '/activate') { require __DIR__ . '/activate.php'; exit; }
if ($apiPath === '/info') { require __DIR__ . '/info.php'; exit; }
if (strpos($apiPath, '/admin/') === 0) { require __DIR__ . '/admin-router.php'; exit; }
jsonResponse(['error' => 'Unknown endpoint'], 404);

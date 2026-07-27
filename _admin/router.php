<?php
$adminPath = substr($uri, 6); // убираем /admin

if ($adminPath === '/login' || $adminPath === '/login/') {
    require __DIR__ . '/login.php';
    exit;
}

// Все остальные страницы админки требуют авторизации
if (!isAdmin()) {
    header('Location: /admin/login');
    exit;
}

// API-подобные эндпоинты админки
if ($adminPath === '/clear-cache') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../includes/page-cache.php';
    $count = pageCacheClear();
    // Также очистим geo cache
    $geoFiles = glob(__DIR__ . '/../data/geo_cache/*.txt');
    if ($geoFiles) foreach ($geoFiles as $f) @unlink($f);
    echo json_encode(['success' => true, 'cleared' => $count]);
    exit;
}

if ($adminPath === '/backup' || str_starts_with($adminPath, '/backup/')) {
    require __DIR__ . '/backup.php';
    exit;
}

// Единая SPA-страница админки
require __DIR__ . '/dashboard.php';

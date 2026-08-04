<?php
$adminPath = substr($uri, 6); // убираем /admin

if ($adminPath === '/login' || $adminPath === '/login/') {
    require __DIR__ . '/login.php';
    exit;
}

// Страница лицензии — доступна всегда (для активации)
if ($adminPath === '/license' || $adminPath === '/license/') {
    require __DIR__ . '/license.php';
    exit;
}

// Проверка IP whitelist для всей админки
$adminIp = getClientIp();
if (!checkIpWhitelist($adminIp)) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:100px"><h1>403</h1><p>Доступ запрещён для вашего IP</p><p style="color:#999">' . htmlspecialchars($adminIp, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
    exit;
}

// Все остальные страницы — авторизация
if (!isAdmin()) {
    header('Location: /admin/login');
    exit;
}

// Проверка: сессия привязана к IP (защита от кражи сессии)
if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== $adminIp) {
    session_destroy();
    header('Location: /admin/login');
    exit;
}

// Проверка: сессия не старше 24 часов
if (isset($_SESSION['admin_login_time']) && (time() - $_SESSION['admin_login_time']) > 86400) {
    session_destroy();
    header('Location: /admin/login');
    exit;
}

// API-подобные эндпоинты

if ($adminPath === '/clear-api-cache') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../includes/api-cache.php';
    $count = apiCacheClear();
    echo json_encode(['success' => true, 'cleared' => $count]);
    exit;
}

if ($adminPath === '/clear-cache') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../includes/page-cache.php';
    $count = pageCacheClear();
    $geoFiles = glob(__DIR__ . '/../data/geo_cache/*.txt');
    if ($geoFiles) foreach ($geoFiles as $f) @unlink($f);
    echo json_encode(['success' => true, 'cleared' => $count]);
    exit;
}

if ($adminPath === '/backup' || str_starts_with($adminPath, '/backup/')) {
    require __DIR__ . '/backup.php';
    exit;
}

// Документация
if ($adminPath === '/docs' || $adminPath === '/docs/') {
    require __DIR__ . '/docs.php';
    exit;
}

// Страница «О системе»
if ($adminPath === '/about' || $adminPath === '/about/') {
    require __DIR__ . '/about.php';
    exit;
}

// Одноразовый патч брендинга dashboard
if ($adminPath === '/patch-dashboard') {
    require __DIR__ . '/patch-dashboard.php';
    exit;
}

// Единая SPA-страница админки
require __DIR__ . '/dashboard.php';

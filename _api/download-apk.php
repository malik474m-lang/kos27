<?php
/**
 * Отдача APK файла через PHP
 * GET /api/download-apk
 */
$apkFile = __DIR__ . '/../downloads/kosmozaim.apk';

if (!file_exists($apkFile)) {
    http_response_code(404);
    echo json_encode(['error' => 'APK file not found']);
    exit;
}

// Трекинг скачивания
try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_downloads (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20), ip VARCHAR(45), user_agent TEXT, referrer VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    $db->prepare("INSERT INTO app_downloads (platform, ip, user_agent, referrer) VALUES (?, ?, ?, ?)")
       ->execute(['android', $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']);
} catch (Exception $e) {}

// Отдаём файл
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="kosmozaim.apk"');
header('Content-Length: ' . filesize($apkFile));
header('Cache-Control: no-cache, must-revalidate');
readfile($apkFile);
exit;

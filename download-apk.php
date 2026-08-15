<?php
require_once __DIR__ . '/config.php';

$apkFile = __DIR__ . '/downloads/kosmozaim.apk';

if (!file_exists($apkFile)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Файл приложения не найден';
    exit;
}

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform VARCHAR(20),
        ip VARCHAR(45),
        user_agent TEXT,
        referrer VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    $db->prepare("INSERT INTO app_downloads (platform, ip, user_agent, referrer) VALUES (?, ?, ?, ?)")
       ->execute(['android', $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']);
} catch (Exception $e) {}

while (function_exists('ob_get_level') && ob_get_level() > 0) {
    @ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="kosmozaim.apk"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($apkFile));
readfile($apkFile);
exit;

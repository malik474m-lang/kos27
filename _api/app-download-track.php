<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo '{}'; exit; }
try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_downloads (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20), ip VARCHAR(45), user_agent TEXT, referrer VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    $db->prepare("INSERT INTO app_downloads (platform, ip, user_agent, referrer) VALUES (?, ?, ?, ?)")
       ->execute(['android', $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']);
} catch (Exception $e) {}
echo json_encode(['ok'=>true]);

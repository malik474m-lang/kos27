<?php
// Автономный скрипт скачивания APK — БЕЗ config.php
// Чтобы ничего не мешало бинарной отдаче файла

$apkFile = __DIR__ . '/downloads/kosmozaim.apk';

if (!file_exists($apkFile)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Логируем скачивание (тихо, ошибки не ломают скачивание)
try {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) putenv($line);
        }
    }
    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'partnerka72_kosmonew';
    $user = getenv('DB_USER') ?: 'partnerka72';
    $pass = getenv('DB_PASS') ?: '';
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->exec("CREATE TABLE IF NOT EXISTS app_downloads (id INT AUTO_INCREMENT PRIMARY KEY, platform VARCHAR(20), ip VARCHAR(45), user_agent TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
    $pdo->prepare("INSERT INTO app_downloads (platform, ip, user_agent) VALUES (?,?,?)")
        ->execute(['android', $ip, $_SERVER['HTTP_USER_AGENT'] ?? '']);
} catch (Exception $e) {
    // Молча — не ломаем скачивание
}

// Очищаем ВСЕ буферы
while (ob_get_level()) ob_end_clean();

// Отдаём файл
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="kosmozaim.apk"');
header('Content-Length: ' . filesize($apkFile));
header('Cache-Control: no-cache');
header('Pragma: public');
readfile($apkFile);
exit;

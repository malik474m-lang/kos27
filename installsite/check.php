<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=UTF-8');

echo "=== ДИАГНОСТИКА ===\n\n";

echo "1. PHP: " . PHP_VERSION . "\n";
echo "2. Dir: " . __DIR__ . "\n\n";

echo "3. Файлы:\n";
$files = ['config.php','index.php','.env','.htaccess','data/site-settings.json'];
foreach ($files as $f) {
    $path = dirname(__DIR__) . '/' . $f;
    if (file_exists($path)) {
        echo "   ✓ $f (" . filesize($path) . " байт)\n";
    } else {
        $path2 = __DIR__ . '/../' . $f;
        if (file_exists($path2)) {
            echo "   ✓ $f (" . filesize($path2) . " байт) [alt path]\n";
        } else {
            echo "   ✗ $f НЕ НАЙДЕН\n";
        }
    }
}

// Пробуем загрузить config.php
echo "\n4. Загрузка config.php:\n";
$cfgPath = __DIR__ . '/../config.php';
if (!file_exists($cfgPath)) $cfgPath = dirname(__DIR__) . '/config.php';
if (file_exists($cfgPath)) {
    echo "   Путь: $cfgPath\n";
    echo "   Первые 200 символов:\n";
    echo "   " . substr(file_get_contents($cfgPath), 0, 200) . "\n\n";
    
    // Проверяем синтаксис
    $output = shell_exec('php -l ' . escapeshellarg($cfgPath) . ' 2>&1');
    echo "   Синтаксис: $output\n";
    
    // Пробуем подключить
    try {
        require_once $cfgPath;
        echo "   Подключение: ОК\n";
        echo "   SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'не определён') . "\n";
        echo "   SITE_NAME: " . (defined('SITE_NAME') ? SITE_NAME : 'не определён') . "\n";
        
        // Пробуем БД
        try {
            $db = getDB();
            echo "   БД: ОК\n";
        } catch (Exception $e) {
            echo "   БД ОШИБКА: " . $e->getMessage() . "\n";
        }
    } catch (Throwable $e) {
        echo "   ОШИБКА: " . $e->getMessage() . " в " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "   config.php НЕ НАЙДЕН!\n";
}

echo "\n5. .env содержимое:\n";
$envPath = __DIR__ . '/../.env';
if (!file_exists($envPath)) $envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $l) {
        if (stripos($l, 'PASS') !== false || stripos($l, 'SECRET') !== false) {
            $parts = explode('=', $l, 2);
            echo "   " . $parts[0] . "=***\n";
        } else {
            echo "   $l\n";
        }
    }
} else {
    echo "   .env НЕ НАЙДЕН\n";
}

echo "\n=== УДАЛИТЕ ЭТОТ ФАЙЛ ===\n";

<?php
/**
 * Отладочный скрипт — УДАЛИТЕ ПОСЛЕ ПРОВЕРКИ!
 */
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=== DEBUG LICENSE SERVER ===\n\n";

// 1. Проверка подключения к БД
echo "1. База данных: ";
try {
    $db = getDB();
    echo "OK\n";
} catch (Exception $e) {
    echo "ОШИБКА: " . $e->getMessage() . "\n";
    exit;
}

// 2. Проверка таблиц
$tables = ['admins', 'plans', 'licenses', 'license_checks', 'audit_log', 'login_attempts'];
echo "\n2. Таблицы:\n";
foreach ($tables as $t) {
    try {
        $count = $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "   $t: $count записей\n";
    } catch (Exception $e) {
        echo "   $t: ОШИБКА - " . $e->getMessage() . "\n";
    }
}

// 3. Проверка файлов
echo "\n3. Файлы:\n";
$files = [
    '_api/router.php',
    '_api/admin-router.php',
    '_api/check.php',
    '_api/admin/checks.php',
    '_api/admin/audit.php',
    '_api/admin/login.php',
    '_api/admin/stats.php',
    '_admin/router.php',
    '_admin/dashboard.php',
    '_admin/login.php',
];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    echo "   $f: " . (file_exists($path) ? filesize($path) . " байт" : "НЕ НАЙДЕН!") . "\n";
}

// 4. Проверка router.php — какая переменная
echo "\n4. router.php содержит apiPath: ";
$routerContent = file_get_contents(__DIR__ . '/_api/router.php');
echo (strpos($routerContent, '$apiPath') !== false ? 'ДА' : 'НЕТ (старая версия!)') . "\n";

// 5. Проверка admin-router.php
echo "   admin-router.php содержит adminApiPath: ";
$arContent = file_get_contents(__DIR__ . '/_api/admin-router.php');
echo (strpos($arContent, '$adminApiPath') !== false ? 'ДА' : 'НЕТ (старая версия!)') . "\n";

// 6. Тест API
echo "\n5. Тест роутинга:\n";
echo "   URI /api/admin/checks → adminApiPath = 'checks'\n";
$testApiPath = '/admin/checks';
$testAdminApiPath = substr($testApiPath, 7);
echo "   substr('/admin/checks', 7) = '$testAdminApiPath'\n";

echo "\n=== УДАЛИТЕ ЭТОТ ФАЙЛ! ===\n";

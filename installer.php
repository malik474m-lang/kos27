<?php
/**
 * KosmoEngine Installer v2.0
 * Полная установка системы: скачивание файлов + настройка БД
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Загрузите ТОЛЬКО этот файл (installer.php) на хостинг
 * 2. Откройте: https://your-site.ru/installer.php
 * 3. Следуйте инструкциям
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);
header('Content-Type: text/html; charset=UTF-8');

$step = $_GET['step'] ?? 'check';
$error = '';
$success = '';

// GitHub репозиторий
define('REPO_URL', 'https://github.com/malik474m-lang/kos27/archive/refs/heads/main.zip');
define('REPO_NAME', 'kos27-main');

function slugify($text) {
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
    $text = mb_strtolower($text);
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') . '-' . substr(uniqid(), -6);
}

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = "$dir/$file";
        is_dir($path) ? deleteDir($path) : @unlink($path);
    }
    @rmdir($dir);
}

function copyDir($src, $dst) {
    if (!is_dir($src)) return false;
    @mkdir($dst, 0755, true);
    $files = array_diff(scandir($src), ['.', '..']);
    foreach ($files as $file) {
        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";
        if (is_dir($srcPath)) {
            copyDir($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    return true;
}

// Шаг: Скачивание файлов
if ($step === 'download' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $zipFile = __DIR__ . '/kosmo-temp.zip';
        $extractDir = __DIR__ . '/kosmo-temp';
        
        // Скачиваем архив с GitHub
        $ch = curl_init(REPO_URL);
        $fp = fopen($zipFile, 'w');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 120,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        
        if (!$result || $httpCode !== 200) {
            throw new Exception("Ошибка скачивания: HTTP $httpCode, $error");
        }
        
        if (!file_exists($zipFile) || filesize($zipFile) < 1000) {
            throw new Exception("Файл не скачан или повреждён");
        }
        
        // Распаковываем
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new Exception("Не удалось открыть архив");
        }
        
        @mkdir($extractDir, 0755, true);
        $zip->extractTo($extractDir);
        $zip->close();
        
        // Копируем файлы из kos27-main/ в корень (кроме license-server)
        $sourceDir = $extractDir . '/' . REPO_NAME;
        if (!is_dir($sourceDir)) {
            throw new Exception("Папка $sourceDir не найдена в архиве");
        }
        
        // Список файлов/папок для копирования
        $items = array_diff(scandir($sourceDir), ['.', '..', 'license-server', 'installer.php', '.git']);
        
        foreach ($items as $item) {
            $src = "$sourceDir/$item";
            $dst = __DIR__ . "/$item";
            
            // Пропускаем database*.sql и README
            if (preg_match('/^database.*\.sql$/', $item) || preg_match('/\.md$/', $item)) {
                continue;
            }
            
            if (is_dir($src)) {
                copyDir($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
        
        // Удаляем временные файлы
        @unlink($zipFile);
        deleteDir($extractDir);
        
        $success = 'Файлы успешно загружены!';
        $step = 'config';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        @unlink($zipFile);
        deleteDir($extractDir);
    }
}

// Шаг: Установка БД
if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? '';
    $siteName = trim($_POST['site_name'] ?? 'Космозайм');
    $siteUrl = trim($_POST['site_url'] ?? '');
    
    if (!$dbName || !$dbUser) {
        $error = 'Заполните данные базы данных';
    } elseif (strlen($adminPass) < 6) {
        $error = 'Пароль администратора минимум 6 символов';
    } else {
        try {
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$dbName`");
            
            // SQL схема
            $sql = file_get_contents(__DIR__ . '/installer-schema.sql');
            if (!$sql) {
                // Встроенная схема если файл не найден
                $sql = getEmbeddedSchema();
            }
            
            $queries = array_filter(array_map('trim', preg_split('/;[\r\n]+/', $sql)));
            foreach ($queries as $query) {
                if ($query && !preg_match('/^--/', $query)) {
                    $pdo->exec($query);
                }
            }
            
            // Админ
            $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)")
                ->execute([$adminUser, $adminHash]);
            
            // Демо-теги
            $tags = [
                ['bez-otkaza', 'Займы без отказа', '✅', 'microloans'],
                ['bez-protsentov', 'Займы без процентов', '🆓', 'microloans'],
                ['na-kartu', 'Займы на карту', '💳', 'microloans'],
                ['s-plohoj-ki', 'С плохой КИ', '📊', 'microloans'],
            ];
            $stmt = $pdo->prepare("INSERT IGNORE INTO offer_tags (slug, title, icon, category, is_active, sort_order) VALUES (?, ?, ?, ?, 1, ?)");
            foreach ($tags as $i => $t) $stmt->execute([$t[0], $t[1], $t[2], $t[3], $i+1]);
            
            // Демо-офферы
            $offers = [
                ['Пример МФО', 'microloans', 1000, 30000, 0.8, 'Демо-предложение'],
                ['Пример Банка', 'credits', 50000, 1000000, 12.9, 'Демо-кредит'],
            ];
            $stmt = $pdo->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, rate, rate_unit, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            foreach ($offers as $i => $o) {
                $stmt->execute([$o[0], slugify($o[0]), $o[1], $o[2], $o[3], $o[4], $o[1]==='microloans'?'day':'year', $o[5], $i+1]);
            }
            
            // .env
            $env = "DB_HOST=$dbHost\nDB_NAME=$dbName\nDB_USER=$dbUser\nDB_PASS=$dbPass\nSESSION_SECRET=" . bin2hex(random_bytes(32)) . "\n";
            file_put_contents(__DIR__ . '/.env', $env);
            @chmod(__DIR__ . '/.env', 0600);
            
            // Настройки сайта
            @mkdir(__DIR__ . '/data', 0755, true);
            $settings = ['site_name' => $siteName, 'site_url' => $siteUrl ?: 'https://' . $_SERVER['HTTP_HOST']];
            file_put_contents(__DIR__ . '/data/site-settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $step = 'done';
            
        } catch (PDOException $e) {
            $error = 'Ошибка БД: ' . $e->getMessage();
        }
    }
}

function getEmbeddedSchema() {
    return <<<'SQL'
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `totp_secret` varchar(64) DEFAULT NULL,
    `totp_enabled` tinyint(1) DEFAULT 0,
    `backup_codes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `slug` varchar(500) NOT NULL,
    `category` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `amount_min` int(11) DEFAULT 1000,
    `amount_max` int(11) DEFAULT 100000,
    `term_min_days` int(11) DEFAULT 1,
    `term_max_days` int(11) DEFAULT 365,
    `psk` decimal(10,2) DEFAULT 0,
    `rate` decimal(10,2) DEFAULT 0,
    `rate_unit` enum('day','year') DEFAULT 'day',
    `free_term_days` int(11) DEFAULT 0,
    `logo_url` text DEFAULT NULL,
    `affiliate_url` text DEFAULT NULL,
    `borrower_category` varchar(50) DEFAULT 'any',
    `description` text DEFAULT NULL,
    `rating` decimal(2,1) DEFAULT 5.0,
    `review_count` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `age_min` int(11) DEFAULT 18,
    `age_max` int(11) DEFAULT 75,
    `meta_title` varchar(500) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `slug` varchar(500) NOT NULL,
    `excerpt` text DEFAULT NULL,
    `content` text NOT NULL,
    `meta_title` varchar(255) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `cover_image` text DEFAULT NULL,
    `is_published` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `author_name` varchar(255) NOT NULL,
    `rating` int(11) DEFAULT 5,
    `text` text NOT NULL,
    `pros` text DEFAULT NULL,
    `cons` text DEFAULT NULL,
    `is_approved` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `click_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `utm_source` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `geo_redirects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `city_name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offer_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `icon` varchar(10) DEFAULT NULL,
    `category` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `description` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `offer_tag_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `offer_tag` (`offer_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `category_type` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `page_views` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page` varchar(500) NOT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_login_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) DEFAULT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `success` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_ip_whitelist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `entity_type` varchar(50) DEFAULT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `city_seo` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `city_slug` varchar(255) NOT NULL,
    `page_type` varchar(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_favorites` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `offer_id` int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `conversions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `click_id` int(11) DEFAULT NULL,
    `status` varchar(50) DEFAULT NULL,
    `payout` decimal(10,2) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ab_tests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ab_variants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `views` int(11) DEFAULT 0,
    `clicks` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `newsletters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subject` varchar(500) NOT NULL,
    `content` text NOT NULL,
    `status` varchar(20) DEFAULT 'draft',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка KosmoEngine</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-900 to-purple-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg">
        <div class="text-center mb-6">
            <span class="text-5xl">🚀</span>
            <h1 class="text-2xl font-bold text-gray-900 mt-3">KosmoEngine</h1>
            <p class="text-gray-500 text-sm">Установка системы</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg p-4 mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($step === 'check'): ?>
        <h2 class="font-semibold text-lg mb-4">Шаг 1: Проверка требований</h2>
        <?php
        $checks = [
            ['PHP >= 7.4', version_compare(PHP_VERSION, '7.4.0', '>=')],
            ['PDO MySQL', extension_loaded('pdo_mysql')],
            ['cURL', extension_loaded('curl')],
            ['ZipArchive', class_exists('ZipArchive')],
            ['Запись в директорию', is_writable(__DIR__)],
        ];
        $allOk = true;
        foreach ($checks as [$name, $ok]) { $allOk = $allOk && $ok; ?>
        <div class="flex justify-between py-2 border-b">
            <span><?= $name ?></span>
            <span class="<?= $ok ? 'text-green-600' : 'text-red-600' ?>"><?= $ok ? '✓' : '✗' ?></span>
        </div>
        <?php } ?>
        
        <?php if ($allOk): ?>
        <form method="POST" action="?step=download" class="mt-6">
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">
                Скачать файлы с GitHub →
            </button>
        </form>
        <?php else: ?>
        <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg p-4">
            Устраните проблемы и обновите страницу
        </div>
        <?php endif; ?>

        <?php elseif ($step === 'config'): ?>
        <h2 class="font-semibold text-lg mb-4">Шаг 2: Настройка</h2>
        <form method="POST" action="?step=install">
            <div class="space-y-3 mb-4">
                <div><label class="block text-sm font-medium mb-1">Хост БД</label><input type="text" name="db_host" value="localhost" required class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Имя БД</label><input type="text" name="db_name" required class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Пользователь БД</label><input type="text" name="db_user" required class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Пароль БД</label><input type="password" name="db_pass" class="w-full border rounded-lg px-3 py-2"></div>
            </div>
            <div class="space-y-3 mb-4">
                <div><label class="block text-sm font-medium mb-1">Логин админа</label><input type="text" name="admin_user" value="admin" required class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">Пароль админа</label><input type="password" name="admin_pass" required minlength="6" class="w-full border rounded-lg px-3 py-2"></div>
            </div>
            <div class="space-y-3 mb-6">
                <div><label class="block text-sm font-medium mb-1">Название сайта</label><input type="text" name="site_name" value="Космозайм" class="w-full border rounded-lg px-3 py-2"></div>
                <div><label class="block text-sm font-medium mb-1">URL сайта</label><input type="url" name="site_url" placeholder="https://example.com" class="w-full border rounded-lg px-3 py-2"></div>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">🚀 Установить</button>
        </form>

        <?php elseif ($step === 'done'): ?>
        <div class="text-center">
            <span class="text-6xl">✅</span>
            <h2 class="text-xl font-bold mt-4 mb-2">Установка завершена!</h2>
            <div class="bg-gray-100 rounded-lg p-4 text-left mb-6 text-sm">
                <ol class="list-decimal list-inside space-y-1">
                    <li>Войдите в <a href="/admin" class="text-blue-600 underline">админку</a></li>
                    <li>Активируйте лицензию на <a href="/admin/license" class="text-blue-600 underline">/admin/license</a></li>
                    <li>Добавьте реальные предложения</li>
                    <li class="text-red-600 font-semibold">Удалите installer.php!</li>
                </ol>
            </div>
            <div class="flex space-x-3">
                <a href="/admin" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold text-center">Админка</a>
                <a href="/" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold text-center">Сайт</a>
            </div>
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                ⚠️ Удалите installer.php: <code>rm installer.php</code>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-6 pt-4 border-t text-center">
            <p class="text-gray-400 text-xs">KosmoEngine © <?= date('Y') ?></p>
        </div>
    </div>
</body>
</html>

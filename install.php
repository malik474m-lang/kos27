<?php
/**
 * KosmoEngine Installer
 * Установка системы с нуля
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Загрузите все файлы на хостинг
 * 2. Откройте в браузере: https://your-site.ru/install.php
 * 3. Следуйте инструкциям
 * 4. УДАЛИТЕ install.php после установки!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

$step = $_GET['step'] ?? 'check';
$error = '';
$success = '';

// Функция создания slug
function slugify($text) {
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya'];
    $text = mb_strtolower($text);
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') . '-' . substr(uniqid(), -6);
}

// Обработка установки
if ($step === 'install' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';
    $adminUser = trim($_POST['admin_user'] ?? 'admin');
    $adminPass = $_POST['admin_pass'] ?? '';
    $siteName = trim($_POST['site_name'] ?? 'Космозайм');
    $siteUrl = trim($_POST['site_url'] ?? '');
    
    // Валидация
    if (!$dbName || !$dbUser) {
        $error = 'Заполните данные базы данных';
    } elseif (strlen($adminPass) < 6) {
        $error = 'Пароль администратора минимум 6 символов';
    } else {
        try {
            // Подключение к БД
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Создаём БД если не существует
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$dbName`");
            
            // Создаём таблицы
            $sql = <<<SQL

-- Администраторы
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

-- Предложения (офферы)
CREATE TABLE IF NOT EXISTS `offers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(500) NOT NULL,
    `slug` varchar(500) NOT NULL,
    `category` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `amount_min` int(11) DEFAULT 1000,
    `amount_max` int(11) DEFAULT 100000,
    `term_min_days` int(11) DEFAULT 1,
    `term_max_days` int(11) DEFAULT 365,
    `psk` decimal(10,2) DEFAULT 0.00,
    `rate` decimal(10,2) DEFAULT 0.00,
    `rate_unit` enum('day','year') DEFAULT 'day',
    `free_term_days` int(11) DEFAULT 0,
    `logo_url` text DEFAULT NULL,
    `affiliate_url` text DEFAULT NULL,
    `borrower_category` varchar(50) DEFAULT 'any',
    `description` text DEFAULT NULL,
    `seo_keywords` text DEFAULT NULL,
    `regions` text DEFAULT NULL,
    `rating` decimal(2,1) DEFAULT 5.0,
    `review_count` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `age_min` int(11) DEFAULT 18,
    `age_max` int(11) DEFAULT 75,
    `documents` text DEFAULT NULL,
    `requirements` text DEFAULT NULL,
    `advantages` text DEFAULT NULL,
    `application_time` varchar(100) DEFAULT NULL,
    `review_time` varchar(100) DEFAULT NULL,
    `payout_methods` text DEFAULT NULL,
    `about_company` text DEFAULT NULL,
    `meta_title` varchar(500) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `features` text DEFAULT NULL,
    `faq` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Статьи
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

-- Отзывы
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
    PRIMARY KEY (`id`),
    KEY `offer_id` (`offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Клики
CREATE TABLE IF NOT EXISTS `click_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `user_agent` text DEFAULT NULL,
    `referer` text DEFAULT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `utm_source` varchar(255) DEFAULT NULL,
    `utm_medium` varchar(255) DEFAULT NULL,
    `utm_campaign` varchar(255) DEFAULT NULL,
    `utm_content` varchar(255) DEFAULT NULL,
    `utm_term` varchar(255) DEFAULT NULL,
    `page_from` text DEFAULT NULL,
    `ab_variant_id` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `offer_id` (`offer_id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Подписчики
CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `source` varchar(100) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Гео-редиректы
CREATE TABLE IF NOT EXISTS `geo_redirects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `city_name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `region` varchar(255) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Теги (типы предложений)
CREATE TABLE IF NOT EXISTS `offer_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `title` varchar(255) NOT NULL,
    `h1` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `content` text DEFAULT NULL,
    `icon` varchar(10) DEFAULT NULL,
    `category` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `features` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `search_queries` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Связи офферов и тегов
CREATE TABLE IF NOT EXISTS `offer_tag_links` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `tag_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `offer_tag` (`offer_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Категории
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slug` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `title` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `meta_title` varchar(500) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `icon` varchar(50) DEFAULT NULL,
    `parent_id` int(11) DEFAULT NULL,
    `category_type` enum('microloans','credits','credit_cards','debit_cards') DEFAULT 'microloans',
    `filter_json` text DEFAULT NULL,
    `content_top` text DEFAULT NULL,
    `content_bottom` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A/B тесты
CREATE TABLE IF NOT EXISTS `ab_tests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `offer_id` int(11) DEFAULT NULL,
    `scope` enum('offer','global') DEFAULT 'offer',
    `is_active` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ab_variants` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `test_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `button_text` varchar(255) DEFAULT NULL,
    `button_color` varchar(50) DEFAULT NULL,
    `cta_text` text DEFAULT NULL,
    `weight` int(11) DEFAULT 50,
    `views` int(11) DEFAULT 0,
    `clicks` int(11) DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Просмотры страниц
CREATE TABLE IF NOT EXISTS `page_views` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `page` varchar(500) NOT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `utm_source` varchar(255) DEFAULT NULL,
    `utm_medium` varchar(255) DEFAULT NULL,
    `utm_campaign` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Конверсии (постбеки)
CREATE TABLE IF NOT EXISTS `conversions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `click_id` int(11) DEFAULT NULL,
    `offer_id` int(11) DEFAULT NULL,
    `status` varchar(50) DEFAULT NULL,
    `payout` decimal(10,2) DEFAULT 0.00,
    `currency` varchar(10) DEFAULT 'RUB',
    `external_id` varchar(255) DEFAULT NULL,
    `raw_data` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Безопасность
CREATE TABLE IF NOT EXISTS `admin_login_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(100) DEFAULT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `success` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `admin_ip_whitelist` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip` varchar(50) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Аудит
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `entity_type` varchar(50) DEFAULT NULL,
    `entity_id` int(11) DEFAULT NULL,
    `old_data` text DEFAULT NULL,
    `new_data` text DEFAULT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Рассылки
CREATE TABLE IF NOT EXISTS `newsletters` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `subject` varchar(500) NOT NULL,
    `content` text NOT NULL,
    `status` enum('draft','scheduled','sending','sent') DEFAULT 'draft',
    `scheduled_at` datetime DEFAULT NULL,
    `sent_at` datetime DEFAULT NULL,
    `sent_count` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SEO городов
CREATE TABLE IF NOT EXISTS `city_seo` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `city_slug` varchar(255) NOT NULL,
    `page_type` varchar(50) NOT NULL,
    `h1` varchar(500) DEFAULT NULL,
    `title` varchar(500) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `content` text DEFAULT NULL,
    `faq` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `city_page` (`city_slug`, `page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Пользователи (регистрация)
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `password_hash` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_favorites` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `offer_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_offer` (`user_id`, `offer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_applications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `offer_id` int(11) NOT NULL,
    `click_stat_id` int(11) DEFAULT NULL,
    `ip` varchar(50) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SQL;
            
            // Выполняем SQL по частям
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($queries as $query) {
                if ($query) {
                    $pdo->exec($query);
                }
            }
            
            // Создаём администратора
            $adminHash = password_hash($adminPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = ?");
            $stmt->execute([$adminUser, $adminHash, $adminHash]);
            
            // Добавляем демо-теги
            $tags = [
                ['bez-otkaza', 'Займы без отказа', '✅', 'microloans', 'Займы с высоким одобрением'],
                ['bez-protsentov', 'Займы без процентов', '🆓', 'microloans', 'Первый займ под 0%'],
                ['na-kartu', 'Займы на карту', '💳', 'microloans', 'Мгновенный перевод на карту'],
                ['s-plohoj-ki', 'С плохой КИ', '📊', 'microloans', 'Без проверки кредитной истории'],
                ['dlya-pensionerov', 'Для пенсионеров', '👴', 'microloans', 'Возраст до 75 лет'],
                ['studentam', 'Для студентов', '🎓', 'microloans', 'От 18 лет без подтверждения дохода'],
            ];
            
            $stmt = $pdo->prepare("INSERT IGNORE INTO offer_tags (slug, title, icon, category, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?, 1, ?)");
            $order = 1;
            foreach ($tags as $tag) {
                $stmt->execute([$tag[0], $tag[1], $tag[2], $tag[3], $tag[4], $order++]);
            }
            
            // Добавляем демо-предложения (без партнерских ссылок)
            $offers = [
                ['Пример МФО 1', 'microloans', 1000, 30000, 0.8, 14, '💰', 'Быстрые займы онлайн'],
                ['Пример МФО 2', 'microloans', 5000, 100000, 0.99, 7, '⚡', 'Деньги за 5 минут'],
                ['Пример банка 1', 'credits', 50000, 5000000, 15.9, 0, '🏦', 'Кредит наличными'],
                ['Пример карты 1', 'credit_cards', 0, 500000, 24.9, 55, '💳', 'Кредитная карта с кэшбеком'],
            ];
            
            $stmt = $pdo->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, rate, rate_unit, free_term_days, logo_url, description, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $order = 1;
            foreach ($offers as $offer) {
                $slug = slugify($offer[0]);
                $rateUnit = $offer[1] === 'microloans' ? 'day' : 'year';
                $stmt->execute([
                    $offer[0], $slug, $offer[1], $offer[2], $offer[3], $offer[4], $rateUnit, $offer[5],
                    '', $offer[7], $order++
                ]);
            }
            
            // Связываем офферы с тегами
            $pdo->exec("INSERT IGNORE INTO offer_tag_links (offer_id, tag_id) SELECT o.id, t.id FROM offers o, offer_tags t WHERE o.category = 'microloans' AND t.category = 'microloans' LIMIT 10");
            
            // Создаём .env файл
            $envContent = "DB_HOST=$dbHost\nDB_NAME=$dbName\nDB_USER=$dbUser\nDB_PASS=$dbPass\nSESSION_SECRET=" . bin2hex(random_bytes(32)) . "\n";
            file_put_contents(__DIR__ . '/.env', $envContent);
            chmod(__DIR__ . '/.env', 0600);
            
            // Создаём настройки сайта
            $settings = [
                'site_name' => $siteName,
                'site_url' => $siteUrl ?: 'https://' . ($_SERVER['HTTP_HOST'] ?? 'example.com'),
            ];
            @mkdir(__DIR__ . '/data', 0755, true);
            file_put_contents(__DIR__ . '/data/site-settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $success = 'Установка завершена успешно!';
            $step = 'done';
            
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Установка KosmoEngine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>
</head>
<body class="bg-gradient-to-br from-blue-900 to-purple-900 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-lg">
        <div class="text-center mb-8">
            <span class="text-5xl">🚀</span>
            <h1 class="text-2xl font-bold text-gray-900 mt-3">KosmoEngine</h1>
            <p class="text-gray-500 text-sm">Установка системы</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 'check'): ?>
        <!-- Шаг 1: Проверка -->
        <div class="mb-6">
            <h2 class="font-semibold text-lg mb-4">Проверка требований</h2>
            <?php
            $checks = [
                ['PHP версия >= 7.4', version_compare(PHP_VERSION, '7.4.0', '>=')],
                ['PDO MySQL', extension_loaded('pdo_mysql')],
                ['cURL', extension_loaded('curl')],
                ['JSON', extension_loaded('json')],
                ['mbstring', extension_loaded('mbstring')],
                ['Запись в директорию', is_writable(__DIR__)],
            ];
            $allOk = true;
            foreach ($checks as [$name, $ok]):
                $allOk = $allOk && $ok;
            ?>
            <div class="flex items-center justify-between py-2 border-b">
                <span><?= $name ?></span>
                <span class="<?= $ok ? 'text-green-600' : 'text-red-600' ?>"><?= $ok ? '✓' : '✗' ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($allOk): ?>
        <a href="?step=config" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-blue-700">
            Продолжить →
        </a>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 rounded-lg p-4">
            Устраните проблемы и обновите страницу
        </div>
        <?php endif; ?>

        <?php elseif ($step === 'config'): ?>
        <!-- Шаг 2: Настройка -->
        <form method="POST" action="?step=install">
            <h2 class="font-semibold text-lg mb-4">База данных</h2>
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Хост</label>
                    <input type="text" name="db_host" value="localhost" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Имя базы данных</label>
                    <input type="text" name="db_name" required class="w-full border rounded-lg px-3 py-2" placeholder="kosmozaim">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Пользователь</label>
                    <input type="text" name="db_user" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Пароль БД</label>
                    <input type="password" name="db_pass" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <h2 class="font-semibold text-lg mb-4">Администратор</h2>
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Логин</label>
                    <input type="text" name="admin_user" value="admin" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Пароль (мин. 6 символов)</label>
                    <input type="password" name="admin_pass" required minlength="6" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <h2 class="font-semibold text-lg mb-4">Сайт</h2>
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Название сайта</label>
                    <input type="text" name="site_name" value="Космозайм" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">URL сайта</label>
                    <input type="url" name="site_url" placeholder="https://example.com" class="w-full border rounded-lg px-3 py-2">
                </div>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                🚀 Установить
            </button>
        </form>

        <?php elseif ($step === 'done'): ?>
        <!-- Готово -->
        <div class="text-center">
            <span class="text-6xl">✅</span>
            <h2 class="text-xl font-bold mt-4 mb-2">Установка завершена!</h2>
            <p class="text-gray-600 mb-6">KosmoEngine успешно установлен.</p>
            
            <div class="bg-gray-100 rounded-lg p-4 text-left mb-6">
                <p class="font-semibold mb-2">Что дальше:</p>
                <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1">
                    <li>Войдите в админку: <a href="/admin" class="text-blue-600">/admin</a></li>
                    <li>Активируйте лицензию</li>
                    <li>Добавьте реальные предложения</li>
                    <li><strong class="text-red-600">Удалите install.php!</strong></li>
                </ol>
            </div>
            
            <div class="flex space-x-3">
                <a href="/admin" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold text-center hover:bg-blue-700">
                    Войти в админку
                </a>
                <a href="/" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold text-center hover:bg-gray-300">
                    Открыть сайт
                </a>
            </div>
            
            <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 font-semibold">⚠️ ОБЯЗАТЕЛЬНО удалите файл install.php!</p>
                <code class="text-sm text-red-600">rm install.php</code>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-8 pt-4 border-t text-center">
            <p class="text-gray-400 text-xs">KosmoEngine © <?= date('Y') ?></p>
        </div>
    </div>
</body>
</html>

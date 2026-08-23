<?php
$GLOBALS['page_start_time'] = microtime(true);

// Логирование ошибок
require_once __DIR__ . '/includes/error-logger.php';
require_once __DIR__ . '/includes/breadcrumbs.php';
require_once __DIR__ . '/includes/page-meta.php';
require_once __DIR__ . '/includes/settings-storage.php';
set_error_handler('kosmozaimErrorHandler');
register_shutdown_function('kosmozaimShutdownHandler');

// UTF-8 по умолчанию
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');

// Конфигурация Космозайм
// Этот файл загружает .env и создаёт подключение к MySQL

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            putenv($line);
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}


// Загрузка настроек сайта из JSON (переопределяет .env)
function getSiteSettings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;
    
    $settingsFile = __DIR__ . '/data/site-settings.json';
    $settings = [
        'site_name' => 'Космозайм',
        'site_url' => getenv('NEXT_PUBLIC_SITE_URL') ?: 'https://kosmozaim.ru',
        'site_logo' => '',
        'site_favicon' => '',
        'yandex_gpt_api_key' => getenv('YANDEX_GPT_API_KEY') ?: '',
        'yandex_folder_id' => getenv('YANDEX_FOLDER_ID') ?: '',
        'yandex_metrika_id' => getenv('NEXT_PUBLIC_YANDEX_METRIKA_ID') ?: '',
        'google_analytics_id' => getenv('NEXT_PUBLIC_GOOGLE_ANALYTICS_ID') ?: '',
        'article_image_prompt_template' => 'нарисуй 16:9 {title}',
        'article_image_provider' => 'yandex',
        'stability_api_key' => '',
        'gigachat_auth_key' => '',
        'gigachat_scope' => 'GIGACHAT_API_PERS',
    ];
    
    if (file_exists($settingsFile) || file_exists($settingsFile . '.bak')) {
        $json = loadJsonSettingsSafe($settingsFile);
        if ($json) {
            $settings = array_merge($settings, $json);
        }
    }
    
    return $settings;
}

// Переопределяем константы из настроек
$_siteSettings = getSiteSettings();

// Настройки сайта (из JSON или .env)
define('SITE_URL', $_siteSettings['site_url']);
define('SITE_NAME', $_siteSettings['site_name']);
define('SITE_LOGO', $_siteSettings['site_logo']);
define('YANDEX_METRIKA_ID', $_siteSettings['yandex_metrika_id']);
define('GOOGLE_ANALYTICS_ID', $_siteSettings['google_analytics_id']);
define('YANDEX_GPT_API_KEY', $_siteSettings['yandex_gpt_api_key']);
define('YANDEX_FOLDER_ID', $_siteSettings['yandex_folder_id']);
define('SESSION_SECRET', getenv('SESSION_SECRET') ?: 'default-secret');
define('CRON_SECRET', getenv('CRON_SECRET') ?: 'kosmozaim-cron-2024');

// Подключение к MySQL
function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    
    $url = getenv('DATABASE_URL');
    if (!$url) {
        // Fallback: отдельные переменные
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $name = getenv('DB_NAME') ?: 'partnerka72_kosmonew';
        $user = getenv('DB_USER') ?: 'partnerka72';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
        return $pdo;
    }
    
    // Парсинг DATABASE_URL: mysql://user:pass@host:port/dbname
    $parts = parse_url($url);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 3306;
    $user = $parts['user'] ?? '';
    $pass = $parts['pass'] ?? '';
    $name = ltrim($parts['path'] ?? '', '/');
    
    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
    return $pdo;
}

// Утилиты
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatMoney(int $amount): string {
    return number_format($amount, 0, '', ' ') . ' ₽';
}

function formatDays(int $days): string {
    if ($days <= 0) return '0 дней';
    if ($days % 365 === 0) {
        $y = $days / 365;
        if ($y == 1) return '1 год';
        if ($y < 5) return "$y года";
        return "$y лет";
    }
    if ($days % 30 === 0) {
        $m = $days / 30;
        if ($m == 1) return '1 месяц';
        if ($m < 5) return "$m месяца";
        return "$m месяцев";
    }
    if ($days == 1) return '1 день';
    if ($days < 5) return "$days дня";
    return "$days дней";
}

function normalizeMediaUrl(?string $url): string {
    if (!$url) return '';
    $url = trim($url);
    if (!$url) return '';
    if (str_starts_with((string)($url), '/public/')) {
        return substr($url, 7); // убираем /public
    }
    return $url;
}

function getRateUnit(array $offer): string {
    $unit = $offer['rate_unit'] ?? 'day';
    return in_array($unit, ['day', 'year'], true) ? $unit : 'day';
}

function getRateUnitLabel(array $offer): string {
    return getRateUnit($offer) === 'year' ? 'в год' : 'в день';
}

function formatRateDisplay(array $offer, bool $withFrom = true): string {
    $prefix = $withFrom ? 'от ' : '';
    return $prefix . ($offer['rate'] ?? '0') . '% ' . getRateUnitLabel($offer);
}

function slugify(string $text): string {
    $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $text = mb_strtolower($text);
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Сессия для админки
function startAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function isTestHost(): bool {
    $host = strtolower(trim($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    return $host !== '' && (
        str_starts_with((string)($host), 'test.') ||
        str_contains((string)($host), '.test.') ||
        $host === 'localhost' ||
        str_starts_with((string)($host), '127.0.0.1')
    );
}

function getClientIp(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return trim(explode(',', $ip)[0]);
}

function isIpBlocked(string $ip): bool {
    try {
        $db = getDB();
        // Больше 10 неудачных попыток за 15 минут — блок
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM admin_login_log WHERE ip = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        return (int)$stmt->fetch()['cnt'] >= 10;
    } catch (Exception $e) {
        return false;
    }
}

function checkIpWhitelist(string $ip): bool {
    try {
        $db = getDB();
        $count = $db->query("SELECT COUNT(*) as cnt FROM admin_ip_whitelist")->fetch()['cnt'];
        if ((int)$count === 0) return true; // Пустой список = доступ всем
        $stmt = $db->prepare("SELECT id FROM admin_ip_whitelist WHERE ip = ?");
        $stmt->execute([$ip]);
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return true; // Если таблицы нет — пропускаем
    }
}

function logLoginAttempt(string $username, string $ip, bool $success): void {
    try {
        $db = getDB();
        $db->prepare("INSERT INTO admin_login_log (username, ip, user_agent, success) VALUES (?, ?, ?, ?)")
           ->execute([$username, $ip, $_SERVER['HTTP_USER_AGENT'] ?? '', $success ? 1 : 0]);
    } catch (Exception $e) {}
}

function isAdmin(): bool {
    startAdminSession();
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        if (str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        header('Location: /admin/login');
        exit;
    }
}

function requireAdminWithIpCheck(): void {
    $ip = getClientIp();
    if (!checkIpWhitelist($ip)) {
        http_response_code(403);
        echo str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
            ? json_encode(['error' => 'IP не в белом списке'])
            : 'Доступ запрещён';
        exit;
    }
    requireAdmin();
}

function dbTableHasColumn(string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $cache[$key] = ((int)$stmt->fetch()['cnt'] > 0);
    } catch (Exception $e) {
        return $cache[$key] = false;
    }
}

function dbDateColumn(string $table, array $preferredColumns): string {
    foreach ($preferredColumns as $column) {
        if (dbTableHasColumn($table, $column)) return $column;
    }
    return $preferredColumns[0];
}


function dbFirstExistingColumn(string $table, array $preferredColumns): string {
    foreach ($preferredColumns as $column) {
        if (dbTableHasColumn($table, $column)) return $column;
    }
    return $preferredColumns[0];
}

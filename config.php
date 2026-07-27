<?php
$GLOBALS['page_start_time'] = microtime(true);
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
        'yandex_gpt_api_key' => getenv('YANDEX_GPT_API_KEY') ?: '',
        'yandex_folder_id' => getenv('YANDEX_FOLDER_ID') ?: '',
        'yandex_metrika_id' => getenv('NEXT_PUBLIC_YANDEX_METRIKA_ID') ?: '',
        'google_analytics_id' => getenv('NEXT_PUBLIC_GOOGLE_ANALYTICS_ID') ?: '',
    ];
    
    if (file_exists($settingsFile)) {
        $json = json_decode(file_get_contents($settingsFile), true);
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
    if (str_starts_with($url, '/public/')) {
        return substr($url, 7); // убираем /public
    }
    return $url;
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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

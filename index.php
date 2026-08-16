<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/canonical.php';
require_once __DIR__ . '/includes/page-cache.php';
require_once __DIR__ . '/includes/license.php';

// Гео-редирект (до любого вывода)
require_once __DIR__ . '/includes/geo-redirect.php';

// Роутинг
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Проверка лицензии (кроме API и страницы лицензии в админке)
if (!str_starts_with($uri, '/api/')) {
    requireLicense();
}

// Сохраняем UTM в куки
foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term'] as $utm) {
    if (!empty($_GET[$utm])) {
        setcookie($utm, $_GET[$utm], time() + 86400 * 30, '/');
        $_COOKIE[$utm] = $_GET[$utm];
    }
}

// Каноникализация дублей URL (после сохранения UTM в cookies)
canonicalizeRequest();

// Трекинг просмотров страниц офферов (для конверсии)
if (preg_match('#^/offer/([a-z0-9-]+)$#', $uri)) {
    try {
        $pvIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $pvIp = trim(explode(',', $pvIp)[0]);
        getDB()->prepare("INSERT INTO page_views (page, ip, utm_source, utm_medium, utm_campaign) VALUES (?,?,?,?,?)")
            ->execute([$uri, $pvIp, $_COOKIE['utm_source'] ?? null, $_COOKIE['utm_medium'] ?? null, $_COOKIE['utm_campaign'] ?? null]);
    } catch (Exception $e) {}
}

// Кэш страниц
if (pageCacheStart()) exit;

// Авто-расписание генерации отзывов/статей
require_once __DIR__ . '/includes/auto-scheduler.php';
checkAutoScheduler();

// API роуты
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/_api/router.php';
    exit;
}

// Админка
if (str_starts_with($uri, '/admin')) {
    require __DIR__ . '/_admin/router.php';
    exit;
}

// Клик по офферу (редирект)
if (preg_match('#^/click/(\d+)$#', $uri, $m)) {
    $db = getDB();
    $offer = $db->prepare("SELECT affiliate_url FROM offers WHERE id = ? AND is_active = 1");
    $offer->execute([$m[1]]);
    $row = $offer->fetch();
    if ($row) {
        header("X-Robots-Tag: noindex, nofollow");
        $clickIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $clickIp = trim(explode(',', $clickIp)[0]);
        $abVarId = isset($_GET['ab']) ? (int)$_GET['ab'] : null;
        // Считаем клик для A/B варианта
        if ($abVarId) {
            try { $db->prepare("UPDATE ab_variants SET clicks = clicks + 1 WHERE id = ?")->execute([$abVarId]); } catch (Exception $e) {}
        }
        $db->prepare("INSERT INTO click_stats (offer_id, user_agent, referer, ip, utm_source, utm_medium, utm_campaign, utm_content, utm_term, page_from, ab_variant_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               $m[1],
               $_SERVER['HTTP_USER_AGENT'] ?? '',
               $_SERVER['HTTP_REFERER'] ?? '',
               $clickIp,
               $_GET['utm_source'] ?? $_COOKIE['utm_source'] ?? null,
               $_GET['utm_medium'] ?? $_COOKIE['utm_medium'] ?? null,
               $_GET['utm_campaign'] ?? $_COOKIE['utm_campaign'] ?? null,
               $_GET['utm_content'] ?? $_COOKIE['utm_content'] ?? null,
               $_GET['utm_term'] ?? $_COOKIE['utm_term'] ?? null,
               $_SERVER['HTTP_REFERER'] ?? null,
               $abVarId,
           ]);
        // Записываем заявку пользователя (если залогинен)
        require_once __DIR__ . '/includes/user-auth.php';
        $userId = getUserId();
        if ($userId) {
            try {
                $db->prepare("INSERT INTO user_applications (user_id, offer_id, click_stat_id, ip) VALUES (?,?,?,?)")
                   ->execute([$userId, $m[1], $lastClickId ?? null, $clickIp]);
            } catch (Exception $e) {}
        }

        // Добавляем aff_sub (наш click_stats.id) в affiliate URL для postback
        $lastClickId = $db->lastInsertId();
        $affUrl = $row['affiliate_url'];
        $separator = str_contains($affUrl, '?') ? '&' : '?';
        $affUrl .= $separator . 'aff_sub=' . $lastClickId;
        header("X-Robots-Tag: noindex, nofollow");
        header("Location: {$affUrl}");
    } else {
        header("Location: /");
    }
    exit;
}

// Публичные страницы
$routes = [
    '/' => 'home',
    '/zajmy' => 'zajmy',
    '/novye-mfo' => 'novye-mfo',
    '/kredity' => 'kredity',
    '/karty/kreditnye' => 'karty-kreditnye',
    '/karty/debetovye' => 'karty-debetovye',
    '/calculator' => 'calculator',
    '/compare' => 'compare',
    '/articles' => 'articles',
    '/faq' => 'faq',
    '/glossary' => 'glossary',
    '/favorites' => 'favorites',
    '/register' => 'register',
    '/login' => 'user-login',
    '/cabinet' => 'cabinet',
    '/search' => 'search',
    '/contact' => 'contact',
    '/about' => 'about',
    '/editorial-policy' => 'editorial-policy',
    '/how-we-rank' => 'how-we-rank',
    '/sources' => 'sources',
    '/giveaway' => 'giveaway',
    '/privacy' => 'privacy',
    '/terms' => 'terms',
    '/disclaimer' => 'disclaimer',
    '/app' => 'app',
    '/unsubscribe' => 'unsubscribe',
    '/thankyou' => 'thankyou',
    '/sitemap.xml' => 'sitemap',
    '/robots.txt' => 'robots',
    '/llms.txt' => 'llms',
];

// IndexNow key verification file
if (preg_match('#^/([a-f0-9]{32})\.txt$#', $uri, $m)) {
    $indexNowKeyFile = __DIR__ . '/data/indexnow-key.txt';
    if (file_exists($indexNowKeyFile) && trim(file_get_contents($indexNowKeyFile)) === $m[1]) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $m[1];
        exit;
    }
}

// Точное совпадение
if (isset($routes[$uri])) {
    $page = $routes[$uri];
    if ($page === 'sitemap') {
        header('Content-Type: application/xml; charset=UTF-8');
        require __DIR__ . '/pages/sitemap.php';
        exit;
    }
    if ($page === 'robots') {
        header('Content-Type: text/plain; charset=UTF-8');
        require __DIR__ . '/pages/robots.php';
        exit;
    }
    if ($page === 'llms') {
        header('Content-Type: text/plain; charset=UTF-8');
        require __DIR__ . '/pages/llms.php';
        exit;
    }
    require __DIR__ . "/pages/$page.php";
    exit;
}

// Динамические роуты
if (preg_match('#^/zajmy/([a-z0-9-]+)/type/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    $typeSlug = $m[2];
    $cityTypeCategory = 'microloans';
    require __DIR__ . '/pages/city-type.php';
    exit;
}

if (preg_match('#^/kredity/([a-z0-9-]+)/type/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    $typeSlug = $m[2];
    $cityTypeCategory = 'credits';
    require __DIR__ . '/pages/city-type.php';
    exit;
}

if (preg_match('#^/karty/kreditnye/([a-z0-9-]+)/type/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    $typeSlug = $m[2];
    $cityTypeCategory = 'credit_cards';
    require __DIR__ . '/pages/city-type.php';
    exit;
}

if (preg_match('#^/karty/debetovye/([a-z0-9-]+)/type/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    $typeSlug = $m[2];
    $cityTypeCategory = 'debit_cards';
    require __DIR__ . '/pages/city-type.php';
    exit;
}

if (preg_match('#^/zajmy/type/([a-z0-9-]+)$#', $uri, $m)) {
    $typeSlug = $m[1];
    require __DIR__ . '/pages/zajmy-type.php';
    exit;
}

if (preg_match('#^/kredity/type/([a-z0-9-]+)$#', $uri, $m)) {
    $typeSlug = $m[1];
    require __DIR__ . '/pages/zajmy-type.php';
    exit;
}

if (preg_match('#^/karty/kreditnye/type/([a-z0-9-]+)$#', $uri, $m)) {
    $typeSlug = $m[1];
    require __DIR__ . '/pages/zajmy-type.php';
    exit;
}

if (preg_match('#^/karty/debetovye/type/([a-z0-9-]+)$#', $uri, $m)) {
    $typeSlug = $m[1];
    require __DIR__ . '/pages/zajmy-type.php';
    exit;
}

if (preg_match('#^/zajmy/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    require __DIR__ . '/pages/zajmy-city.php';
    exit;
}

if (preg_match('#^/kredity/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    require __DIR__ . '/pages/kredity-city.php';
    exit;
}

if (preg_match('#^/karty/kreditnye/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    require __DIR__ . '/pages/karty-kreditnye-city.php';
    exit;
}

if (preg_match('#^/karty/debetovye/([a-z0-9-]+)$#', $uri, $m)) {
    $citySlug = $m[1];
    require __DIR__ . '/pages/karty-debetovye-city.php';
    exit;
}

if (preg_match('#^/karty/([a-z0-9-]+)$#', $uri, $m) && !in_array($m[1], ['kreditnye', 'debetovye'])) {
    $citySlug = $m[1];
    require __DIR__ . '/pages/karty-city.php';
    exit;
}

if (preg_match('#^/offer/([a-z0-9-]+)$#', $uri, $m)) {
    $offerSlug = $m[1];
    require __DIR__ . '/pages/offer.php';
    exit;
}

if (preg_match('#^/articles/([a-z0-9-]+)$#', $uri, $m)) {
    $articleSlug = $m[1];
    require __DIR__ . '/pages/article.php';
    exit;
}

if (preg_match('#^/glossary/([a-z0-9-]+)$#', $uri, $m)) {
    $termSlug = $m[1];
    require __DIR__ . '/pages/glossary-term.php';
    exit;
}

// Динамическая категория из БД
require_once __DIR__ . '/includes/categories.php';
$catSlug = ltrim($uri, '/');
$dynCat = findCategoryBySlug($catSlug);
if ($dynCat && $dynCat['is_active']) {
    require __DIR__ . '/pages/category.php';
    exit;
}

// 404
http_response_code(404);
$pageTitle = 'Страница не найдена';
$metaDescription = '';
ob_start();
echo '<div class="max-w-7xl mx-auto px-4 py-24 text-center">';
echo '<h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>';
echo '<p class="text-xl text-gray-500">Страница не найдена</p>';
echo '<a href="/" class="btn-primary inline-block mt-6">На главную</a>';
echo '</div>';
$content = ob_get_clean();
require __DIR__ . '/includes/layout.php';

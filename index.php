<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/page-cache.php';

// Гео-редирект (до любого вывода)
require_once __DIR__ . '/includes/geo-redirect.php';

// Кэш страниц
if (pageCacheStart()) exit;

// Авто-расписание генерации отзывов/статей
require_once __DIR__ . '/includes/auto-scheduler.php';
checkAutoScheduler();

// Роутинг
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

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
        $db->prepare("INSERT INTO click_stats (offer_id, user_agent, referer) VALUES (?, ?, ?)")
           ->execute([$m[1], $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['HTTP_REFERER'] ?? '']);
        header("Location: {$row['affiliate_url']}");
    } else {
        header("Location: /");
    }
    exit;
}

// Публичные страницы
$routes = [
    '/' => 'home',
    '/zajmy' => 'zajmy',
    '/kredity' => 'kredity',
    '/karty/kreditnye' => 'karty-kreditnye',
    '/karty/debetovye' => 'karty-debetovye',
    '/calculator' => 'calculator',
    '/compare' => 'compare',
    '/articles' => 'articles',
    '/faq' => 'faq',
    '/glossary' => 'glossary',
    '/favorites' => 'favorites',
    '/search' => 'search',
    '/privacy' => 'privacy',
    '/terms' => 'terms',
    '/disclaimer' => 'disclaimer',
    '/sitemap.xml' => 'sitemap',
    '/robots.txt' => 'robots',
];

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
    require __DIR__ . "/pages/$page.php";
    exit;
}

// Динамические роуты
if (preg_match('#^/zajmy/type/([a-z0-9-]+)$#', $uri, $m)) {
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

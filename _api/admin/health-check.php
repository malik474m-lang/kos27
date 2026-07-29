<?php
$db = getDB();
$issues = ['critical' => [], 'warning' => [], 'ok' => []];
$score = 100;

// === ОФФЕРЫ ===
$allOffers = $db->query("SELECT id, title, logo_url, affiliate_url, description, rate, category FROM offers WHERE is_active = 1")->fetchAll();

$noLogo = array_filter($allOffers, fn($o) => empty(trim($o['logo_url'] ?? '')));
if ($noLogo) { $issues['critical'][] = count($noLogo) . ' оффер(ов) без логотипа'; $score -= 5; }
else $issues['ok'][] = 'Все офферы с логотипами';

$noAff = array_filter($allOffers, fn($o) => empty(trim($o['affiliate_url'] ?? '')));
if ($noAff) { $issues['critical'][] = count($noAff) . ' оффер(ов) без партнёрской ссылки'; $score -= 10; }
else $issues['ok'][] = 'Все партнёрские ссылки заполнены';

$noDesc = array_filter($allOffers, fn($o) => empty(trim($o['description'] ?? '')));
if ($noDesc) { $issues['warning'][] = count($noDesc) . ' оффер(ов) без описания'; $score -= 2; }
else $issues['ok'][] = 'Все офферы с описанием';

$noRate = array_filter($allOffers, fn($o) => (float)$o['rate'] <= 0);
if ($noRate) { $issues['warning'][] = count($noRate) . ' оффер(ов) с нулевой ставкой'; $score -= 2; }

try {
    $noClicks = $db->query("SELECT COUNT(DISTINCT o.id) as cnt FROM offers o LEFT JOIN click_stats c ON o.id = c.offer_id AND c.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) WHERE o.is_active = 1 AND c.id IS NULL")->fetch()['cnt'];
    if ($noClicks > 0) { $issues['warning'][] = $noClicks . ' оффер(ов) без кликов за 30 дней'; $score -= 2; }
} catch (Exception $e) {}

// Офферы без тегов
try {
    $noTags = $db->query("SELECT COUNT(*) as cnt FROM offers o WHERE o.is_active = 1 AND NOT EXISTS (SELECT 1 FROM offer_tag_links l WHERE l.offer_id = o.id)")->fetch()['cnt'];
    if ($noTags > 0) { $issues['warning'][] = $noTags . ' оффер(ов) без тегов'; $score -= 2; }
    else $issues['ok'][] = 'Все офферы привязаны к тегам';
} catch (Exception $e) {}

// === ТЕГИ ===
try {
    $allTags = $db->query("SELECT t.id, t.title, t.content, t.meta_description, t.search_queries, (SELECT COUNT(*) FROM offer_tag_links l WHERE l.tag_id = t.id) as offer_cnt FROM offer_tags t WHERE t.is_active = 1")->fetchAll();
    $emptyTags = array_filter($allTags, fn($t) => (int)$t['offer_cnt'] === 0);
    if ($emptyTags) { $issues['critical'][] = count($emptyTags) . ' тег(ов) без привязанных офферов'; $score -= 5; }
    else $issues['ok'][] = 'Все теги с офферами';

    $noQueries = array_filter($allTags, fn($t) => empty(trim($t['search_queries'] ?? '')));
    if ($noQueries) { $issues['warning'][] = count($noQueries) . ' тег(ов) без поисковых запросов (перелинковка ослаблена)'; $score -= 2; }

    $noContent = array_filter($allTags, fn($t) => empty(trim($t['content'] ?? '')));
    if ($noContent) { $issues['warning'][] = count($noContent) . ' тег(ов) без SEO-текста'; $score -= 1; }

    $noMeta = array_filter($allTags, fn($t) => empty(trim($t['meta_description'] ?? '')));
    if ($noMeta) { $issues['warning'][] = count($noMeta) . ' тег(ов) без meta description'; $score -= 1; }
} catch (Exception $e) {}

// === КАТЕГОРИИ ===
try {
    $emptyCats = $db->query("SELECT c.name FROM categories c WHERE c.is_active = 1 AND c.parent_id IS NULL AND NOT EXISTS (SELECT 1 FROM offers o WHERE o.category = c.slug AND o.is_active = 1) AND c.slug NOT IN ('compare','calculator','articles','faq','glossary','novye-mfo')")->fetchAll();
    if ($emptyCats) { $issues['warning'][] = count($emptyCats) . ' категори(й) без офферов: ' . implode(', ', array_column($emptyCats, 'name')); $score -= 2; }
} catch (Exception $e) {}

// === SEO ===
try {
    $articles = $db->query("SELECT id, title, meta_description, cover_image FROM articles WHERE is_published = 1")->fetchAll();
    $noArtMeta = array_filter($articles, fn($a) => empty(trim($a['meta_description'] ?? '')));
    if ($noArtMeta) { $issues['warning'][] = count($noArtMeta) . ' стат(ей) без meta description'; $score -= 1; }
    $noArtCover = array_filter($articles, fn($a) => empty(trim($a['cover_image'] ?? '')));
    if ($noArtCover) { $issues['warning'][] = count($noArtCover) . ' стат(ей) без обложки'; $score -= 1; }
    if (count($articles) < 5) { $issues['warning'][] = 'Мало статей: ' . count($articles) . ' (рекомендуется 5+)'; $score -= 2; }
    else $issues['ok'][] = 'Статей достаточно: ' . count($articles);
} catch (Exception $e) {}

// Города без SEO
try {
    $totalCities = 41;
    $seoCities = $db->query("SELECT COUNT(DISTINCT city_slug) as cnt FROM city_seo_texts")->fetch()['cnt'];
    $missing = $totalCities - (int)$seoCities;
    if ($missing > 0) { $issues['warning'][] = $missing . ' город(ов) без SEO-текста'; $score -= 2; }
    else $issues['ok'][] = 'SEO-тексты для всех городов';
} catch (Exception $e) {}

// === ПАРТНЁРСКИЕ ССЫЛКИ ===
$brokenLinks = [];
foreach (array_slice($allOffers, 0, 20) as $o) {
    $affUrl = trim($o['affiliate_url'] ?? '');
    if (!$affUrl || !str_starts_with($affUrl, 'http')) continue;
    $ch = curl_init($affUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 0 || $code >= 400) {
        $brokenLinks[] = $o['title'] . ' (HTTP ' . $code . ')';
    }
}
if ($brokenLinks) { $issues['critical'][] = count($brokenLinks) . ' битых партнёрских ссылок: ' . implode(', ', array_slice($brokenLinks, 0, 5)); $score -= 10; }
else $issues['ok'][] = 'Все партнёрские ссылки доступны';

// === БЕЗОПАСНОСТЬ ===
try {
    $admin = $db->query("SELECT password_hash FROM admin_users WHERE username = 'admin' LIMIT 1")->fetch();
    if ($admin && password_verify('admin123', $admin['password_hash'])) {
        $issues['critical'][] = 'Стандартный пароль admin123 не сменён!'; $score -= 15;
    } else {
        $issues['ok'][] = 'Пароль администратора изменён';
    }
} catch (Exception $e) {}

try {
    $wl = $db->query("SELECT COUNT(*) as cnt FROM admin_ip_whitelist")->fetch()['cnt'];
    if ((int)$wl === 0) { $issues['warning'][] = 'IP whitelist пустой — доступ к админке открыт для всех'; $score -= 3; }
    else $issues['ok'][] = 'IP whitelist настроен (' . $wl . ' адресов)';
} catch (Exception $e) {}

// === БД ===
$requiredTables = ['offers','articles','reviews','subscribers','click_stats','offer_tags','offer_tag_links','categories','users','postback_conversions','newsletters','ab_tests','ab_variants','admin_users','admin_login_log','page_views','city_seo_texts'];
$existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$missingTables = array_diff($requiredTables, $existingTables);
if ($missingTables) { $issues['critical'][] = 'Отсутствуют таблицы: ' . implode(', ', $missingTables); $score -= 10; }
else $issues['ok'][] = 'Все таблицы БД на месте';

$issues['ok'][] = 'Подключение к БД работает';

// === КЭШ ===
$pageCacheDir = __DIR__ . '/../../data/page_cache';
$apiCacheDir = __DIR__ . '/../../data/api_cache';
$geoCacheDir = __DIR__ . '/../../data/geo_cache';
function dirSizeMB(string $dir): float {
    $size = 0;
    $files = glob($dir . '/*') ?: [];
    foreach ($files as $f) { $size += @filesize($f) ?: 0; }
    return round($size / 1024 / 1024, 1);
}
$pcSize = dirSizeMB($pageCacheDir);
$acSize = dirSizeMB($apiCacheDir);
$gcSize = dirSizeMB($geoCacheDir);
if ($pcSize > 50) { $issues['warning'][] = 'Page cache занимает ' . $pcSize . ' MB'; $score -= 1; }
if ($acSize > 20) { $issues['warning'][] = 'API cache занимает ' . $acSize . ' MB'; $score -= 1; }

// === ОТЗЫВЫ ===
try {
    $avgReviews = $db->query("SELECT AVG(review_count) as avg_r FROM offers WHERE is_active = 1")->fetch()['avg_r'];
    if ((float)$avgReviews < 2) { $issues['warning'][] = 'Мало отзывов: в среднем ' . round($avgReviews, 1) . ' на оффер'; $score -= 2; }
    else $issues['ok'][] = 'Отзывов достаточно: ~' . round($avgReviews, 1) . ' на оффер';
} catch (Exception $e) {}

$score = max(0, min(100, $score));

echo json_encode([
    'score' => $score,
    'critical' => $issues['critical'],
    'warnings' => $issues['warning'],
    'ok' => $issues['ok'],
    'stats' => [
        'offers' => count($allOffers),
        'pageCacheMB' => $pcSize,
        'apiCacheMB' => $acSize,
        'geoCacheMB' => $gcSize,
    ],
]);

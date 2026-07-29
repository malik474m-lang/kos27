<?php
$db = getDB();
$checks = [];
$score = 100;

// === ОФФЕРЫ ===
$allOffers = $db->query("SELECT id, title, logo_url, affiliate_url, description, rate, category FROM offers WHERE is_active = 1")->fetchAll();
$noLogo = array_filter($allOffers, fn($o) => empty(trim($o['logo_url'] ?? '')));
$noAffUrl = array_filter($allOffers, fn($o) => empty(trim($o['affiliate_url'] ?? '')));
$noDesc = array_filter($allOffers, fn($o) => empty(trim($o['description'] ?? '')));
$zeroRate = array_filter($allOffers, fn($o) => (float)$o['rate'] == 0);

// Офферы без тегов
$offersWithTags = $db->query("SELECT DISTINCT offer_id FROM offer_tag_links")->fetchAll(PDO::FETCH_COLUMN);
$noTags = array_filter($allOffers, fn($o) => !in_array($o['id'], $offersWithTags));

// Офферы без кликов 30 дней
$clickedOffers = $db->query("SELECT DISTINCT offer_id FROM click_stats WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchAll(PDO::FETCH_COLUMN);
$noClicks = array_filter($allOffers, fn($o) => !in_array($o['id'], $clickedOffers));

if ($noLogo) { $checks[] = ['level'=>'error','msg'=>count($noLogo).' оффер(ов) без логотипа','items'=>array_map(fn($o)=>$o['title'],$noLogo),'fixTab'=>'offers']; $score -= 5; }
if ($noAffUrl) { $checks[] = ['level'=>'error','msg'=>count($noAffUrl).' оффер(ов) без партнёрской ссылки','items'=>array_map(fn($o)=>$o['title'],$noAffUrl),'fixTab'=>'offers']; $score -= 10; }
if ($noTags) { $checks[] = ['level'=>'warning','msg'=>count($noTags).' оффер(ов) без тегов','items'=>array_map(fn($o)=>$o['title'],$noTags),'fixTab'=>'offers']; $score -= 3; }
if ($noDesc) { $checks[] = ['level'=>'warning','msg'=>count($noDesc).' оффер(ов) без описания','items'=>array_map(fn($o)=>$o['title'],$noDesc),'fixTab'=>'offers']; $score -= 2; }
if ($noClicks) { $checks[] = ['level'=>'warning','msg'=>count($noClicks).' оффер(ов) без кликов за 30 дней','items'=>array_map(fn($o)=>$o['title'],$noClicks),'fixTab'=>'offers']; $score -= 2; }
if (!$noLogo && !$noAffUrl) { $checks[] = ['level'=>'ok','msg'=>'Все офферы имеют логотип и ссылку']; }

// Проверенные битые партнёрские ссылки
try {
    $badLinks = $db->query("SELECT o.title, lc.http_code FROM offers o JOIN (SELECT t1.* FROM offer_link_checks t1 INNER JOIN (SELECT offer_id, MAX(id) as max_id FROM offer_link_checks GROUP BY offer_id) t2 ON t1.id = t2.max_id) lc ON lc.offer_id = o.id WHERE o.is_active = 1 AND lc.is_ok = 0")->fetchAll();
    if ($badLinks) {
        $checks[] = [
            'level' => 'error',
            'msg' => count($badLinks) . ' проблемных партнёрских ссылок',
            'items' => array_map(fn($r) => $r['title'] . ' (HTTP ' . $r['http_code'] . ')', $badLinks),
            'fixTab' => 'links',
        ];
        $score -= min(15, count($badLinks) * 3);
    } else {
        $checkedCount = (int)$db->query("SELECT COUNT(*) FROM offer_link_checks")->fetchColumn();
        if ($checkedCount > 0) $checks[] = ['level'=>'ok','msg'=>'Проверенные партнёрские ссылки работают'];
        else $checks[] = ['level'=>'warning','msg'=>'Партнёрские ссылки ещё не проверялись'];
    }
} catch (Exception $e) {
    $checks[] = ['level'=>'warning','msg'=>'Таблица проверки ссылок не создана — выполните миграцию'];
}

// === ТЕГИ ===
$allTags = $db->query("SELECT id, title, content, meta_description, search_queries FROM offer_tags WHERE is_active = 1")->fetchAll();
$tagsWithOffers = $db->query("SELECT DISTINCT tag_id FROM offer_tag_links")->fetchAll(PDO::FETCH_COLUMN);
$emptyTags = array_filter($allTags, fn($t) => !in_array($t['id'], $tagsWithOffers));
$noSearchQ = array_filter($allTags, fn($t) => empty(trim($t['search_queries'] ?? '')));
$noTagContent = array_filter($allTags, fn($t) => empty(trim($t['content'] ?? '')));
$noTagMeta = array_filter($allTags, fn($t) => empty(trim($t['meta_description'] ?? '')));

if ($emptyTags) { $checks[] = ['level'=>'error','msg'=>count($emptyTags).' тег(ов) без привязанных офферов','items'=>array_map(fn($t)=>$t['title'],$emptyTags),'fixTab'=>'tags']; $score -= 5; }
if ($noSearchQ) { $checks[] = ['level'=>'warning','msg'=>count($noSearchQ).' тег(ов) без поисковых запросов (перелинковка слабая)','items'=>array_map(fn($t)=>$t['title'],$noSearchQ),'fixTab'=>'tags']; $score -= 3; }
if ($noTagContent) { $checks[] = ['level'=>'warning','msg'=>count($noTagContent).' тег(ов) без SEO-текста','items'=>array_map(fn($t)=>$t['title'],$noTagContent),'fixTab'=>'tags']; $score -= 2; }
if ($noTagMeta) { $checks[] = ['level'=>'warning','msg'=>count($noTagMeta).' тег(ов) без meta description','items'=>array_map(fn($t)=>$t['title'],$noTagMeta),'fixTab'=>'tags']; $score -= 1; }
if (!$emptyTags) { $checks[] = ['level'=>'ok','msg'=>'Все теги имеют привязанные офферы']; }

// === СТАТЬИ ===
$articleCount = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();
$noMetaArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_published = 1 AND (meta_description IS NULL OR meta_description = '')")->fetchColumn();
$noCoverArticles = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_published = 1 AND (cover_image IS NULL OR cover_image = '')")->fetchColumn();

if ($articleCount < 5) { $checks[] = ['level'=>'warning','msg'=>"Мало статей: $articleCount (рекомендуется от 10)"]; $score -= 3; }
else { $checks[] = ['level'=>'ok','msg'=>"Статей: $articleCount"]; }
if ($noMetaArticles) { $checks[] = ['level'=>'warning','msg'=>"$noMetaArticles статей без meta description",'fixTab'=>'articles']; $score -= 2; }
if ($noCoverArticles) { $checks[] = ['level'=>'warning','msg'=>"$noCoverArticles статей без обложки",'fixTab'=>'articles']; $score -= 1; }

// === SEO ГОРОДОВ ===
$totalCities = 41;
$seoCount = (int)$db->query("SELECT COUNT(DISTINCT city_slug) FROM city_seo_texts")->fetchColumn();
$missingCitySeo = $totalCities - $seoCount;
if ($missingCitySeo > 0) { $checks[] = ['level'=>'warning','msg'=>"$missingCitySeo городов без SEO-текста",'fixTab'=>'cityseo']; $score -= min(5, $missingCitySeo); }
else { $checks[] = ['level'=>'ok','msg'=>'SEO-тексты для всех городов сгенерированы']; }

// === ОТЗЫВЫ ===
$reviewCount = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 1")->fetchColumn();
$avgPerOffer = count($allOffers) > 0 ? round($reviewCount / count($allOffers), 1) : 0;
if ($avgPerOffer < 2) { $checks[] = ['level'=>'warning','msg'=>"Мало отзывов: в среднем $avgPerOffer на оффер"]; $score -= 3; }
else { $checks[] = ['level'=>'ok','msg'=>"Отзывов: $reviewCount (ср. $avgPerOffer на оффер)"]; }

// === КАТЕГОРИИ ===
try {
    require_once __DIR__ . '/../../includes/categories.php';
    $allCats = getCategories(false);
    $activeOffersByCategory = $db->query("SELECT category, COUNT(*) as cnt FROM offers WHERE is_active = 1 GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);
    $emptyCats = [];

    foreach ($allCats as $cat) {
        if (empty($cat['is_active'])) continue;
        if (in_array($cat['slug'], ['compare','calculator','articles','faq','glossary','novye-mfo','privacy','terms','disclaimer','favorites','search'], true)) continue;

        // Если это родитель с подкатегориями — не считаем пустым
        $children = array_filter($allCats, fn($c) => (int)($c['parent_id'] ?? 0) === (int)$cat['id'] && !empty($c['is_active']));
        if ($children) continue;

        $offerKey = function_exists('getCategoryOfferKeyBySlug') ? getCategoryOfferKeyBySlug($cat['slug']) : $cat['slug'];
        $count = (int)($activeOffersByCategory[$offerKey] ?? 0);
        if ($count === 0) $emptyCats[] = $cat['name'];
    }

    if ($emptyCats) {
        $checks[] = ['level'=>'warning','msg'=>count($emptyCats).' категорий без офферов','items'=>$emptyCats,'fixTab'=>'cats'];
        $score -= 2;
    } else {
        $checks[] = ['level'=>'ok','msg'=>'Категории наполнены офферами или используются как родительские'];
    }
} catch (Exception $e) {}

// === БД ===
$requiredTables = ['offers','articles','reviews','subscribers','click_stats','offer_tags','offer_tag_links','categories','users','postback_conversions','newsletters','ab_tests','ab_variants','page_views','admin_users'];
$existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$missingTables = array_diff($requiredTables, $existingTables);
if ($missingTables) { $checks[] = ['level'=>'error','msg'=>count($missingTables).' таблиц не найдено (миграции?)','items'=>array_values($missingTables)]; $score -= 10; }
else { $checks[] = ['level'=>'ok','msg'=>'Все таблицы БД на месте']; }

// Проверка колонок
$missingCols = [];
try { $db->query("SELECT extra_fields FROM offers LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'offers.extra_fields'; }
try { $db->query("SELECT display_fields FROM offers LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'offers.display_fields'; }
try { $db->query("SELECT rate_unit FROM offers LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'offers.rate_unit'; }
try { $db->query("SELECT search_queries FROM offer_tags LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'offer_tags.search_queries'; }
try { $db->query("SELECT footer_section FROM categories LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'categories.footer_section'; }
try { $db->query("SELECT source FROM postback_conversions LIMIT 1"); } catch (Exception $e) { $missingCols[] = 'postback_conversions.source'; }
if ($missingCols) { $checks[] = ['level'=>'error','msg'=>count($missingCols).' колонок не найдено — выполните миграции','items'=>$missingCols]; $score -= 8; }
else { $checks[] = ['level'=>'ok','msg'=>'Все колонки БД актуальны']; }

// === ПАРТНЁРСКИЕ ССЫЛКИ ===
try {
    $brokenLinks = $db->query("SELECT COUNT(*) FROM (SELECT offer_id, MAX(id) as max_id FROM offer_link_checks GROUP BY offer_id) x JOIN offer_link_checks lc ON lc.id = x.max_id WHERE lc.is_ok = 0")->fetchColumn();
    $uncheckedLinks = $db->query("SELECT COUNT(*) FROM offers o WHERE o.is_active = 1 AND NOT EXISTS (SELECT 1 FROM offer_link_checks lc WHERE lc.offer_id = o.id)")->fetchColumn();
    if ((int)$brokenLinks > 0) { $checks[] = ['level'=>'warning','msg'=>(int)$brokenLinks.' партнёрских ссылок битые или неактуальные','fixTab'=>'links']; $score -= 4; }
    else { $checks[] = ['level'=>'ok','msg'=>'Битых партнёрских ссылок не найдено']; }
    if ((int)$uncheckedLinks > 0) { $checks[] = ['level'=>'info','msg'=>(int)$uncheckedLinks.' ссылок ещё не проверялись']; }
} catch (Exception $e) {}

// === КЭШИ ===
$pageCacheSize = 0;
$pageCacheFiles = glob(__DIR__ . '/../../data/page_cache/*.html') ?: [];
foreach ($pageCacheFiles as $f) $pageCacheSize += filesize($f);
$apiCacheFiles = glob(__DIR__ . '/../../data/api_cache/*.json') ?: [];
$apiCacheSize = 0;
foreach ($apiCacheFiles as $f) $apiCacheSize += filesize($f);

$checks[] = ['level'=>'info','msg'=>'Page cache: ' . count($pageCacheFiles) . ' файлов (' . round($pageCacheSize/1024/1024, 1) . ' MB)'];
$checks[] = ['level'=>'info','msg'=>'API cache: ' . count($apiCacheFiles) . ' файлов (' . round($apiCacheSize/1024, 1) . ' KB)'];

// === БЕЗОПАСНОСТЬ ===
$adminUser = $db->query("SELECT password_hash FROM admin_users WHERE username = 'admin' LIMIT 1")->fetch();
if ($adminUser && password_verify('admin123', $adminUser['password_hash'])) {
    $checks[] = ['level'=>'error','msg'=>'Стандартный пароль admin123 не сменён!','fixTab'=>'security']; $score -= 10;
} else {
    $checks[] = ['level'=>'ok','msg'=>'Пароль администратора изменён'];
}

try {
    $ipWhitelist = (int)$db->query("SELECT COUNT(*) FROM admin_ip_whitelist")->fetchColumn();
    if ($ipWhitelist === 0) { $checks[] = ['level'=>'info','msg'=>'IP whitelist пустой (это допустимо при динамическом IP)']; }
    else { $checks[] = ['level'=>'ok','msg'=>"IP whitelist: $ipWhitelist адресов"]; }
} catch (Exception $e) {}

// === ПОДПИСЧИКИ ===
$subCount = (int)$db->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 1")->fetchColumn();
$checks[] = ['level'=>'info','msg'=>"Активных подписчиков: $subCount"];

// === POSTBACK ===
try {
    $pbTotal = (int)$db->query("SELECT COUNT(*) FROM postback_conversions")->fetchColumn();
    $pbApproved = (int)$db->query("SELECT COUNT(*) FROM postback_conversions WHERE status = 'approved'")->fetchColumn();
    $checks[] = ['level'=>'info','msg'=>"Postback конверсий: $pbTotal (одобрено: $pbApproved)"];
} catch (Exception $e) {}

// === ПОЛЬЗОВАТЕЛИ ===
try {
    $userCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_verified = 1")->fetchColumn();
    $checks[] = ['level'=>'info','msg'=>"Зарегистрированных пользователей: $userCount"];
} catch (Exception $e) {}

$score = max(0, min(100, $score));

echo json_encode([
    'score' => $score,
    'checks' => $checks,
    'timestamp' => date('Y-m-d H:i:s'),
]);

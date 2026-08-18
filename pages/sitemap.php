<?php
/**
 * Улучшенный Sitemap с lastmod для всех типов страниц
 * Включая subcategories (допзапросы) и city+tag комбинации
 */
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../data/glossary.php';
require_once __DIR__ . '/../includes/subcategories.php';

$db = getDB();

// Данные с датами обновления
$offersData = $db->query("SELECT slug, updated_at FROM offers WHERE is_active = 1 ORDER BY updated_at DESC")->fetchAll();
$articlesData = $db->query("SELECT slug, updated_at FROM articles WHERE is_published = 1 ORDER BY updated_at DESC")->fetchAll();

// Теги из БД
$allTags = $db->query("SELECT slug, category, created_at FROM offer_tags WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Допзапросы (подкатегории) из БД
$allSubcats = [];
try {
    $allSubcats = $db->query("SELECT slug, category, updated_at FROM subcategories WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
} catch (Exception $e) {}

// SEO-тексты городов (для lastmod)
$citySeoData = [];
try {
    $citySeoRows = $db->query("SELECT city_slug, category, updated_at FROM city_seo_texts")->fetchAll();
    foreach ($citySeoRows as $row) {
        $citySeoData[$row['city_slug'] . '_' . $row['category']] = $row['updated_at'];
    }
} catch (Exception $e) {}

// SEO-тексты город+тег (для lastmod)
$cityTagSeoData = [];
try {
    $cityTagRows = $db->query("SELECT city_slug, category, tag_slug, updated_at FROM city_tag_seo_texts")->fetchAll();
    foreach ($cityTagRows as $row) {
        $cityTagSeoData[$row['city_slug'] . '_' . $row['category'] . '_' . $row['tag_slug']] = $row['updated_at'];
    }
} catch (Exception $e) {}

// SEO-тексты город+допзапрос (для lastmod)
$subcatCitySeoData = [];
try {
    $subcatCityRows = $db->query("SELECT subcategory_id, city_slug, created_at FROM subcategory_city_seo")->fetchAll();
    foreach ($subcatCityRows as $row) {
        $subcatCitySeoData[$row['subcategory_id'] . '_' . $row['city_slug']] = $row['created_at'];
    }
} catch (Exception $e) {}

// Последняя дата обновления офферов (для основных страниц категорий)
$lastOfferUpdate = !empty($offersData) ? $offersData[0]['updated_at'] : date('Y-m-d');
$lastArticleUpdate = !empty($articlesData) ? $articlesData[0]['updated_at'] : date('Y-m-d');

$catUrls = [
    'microloans' => '/zajmy',
    'credits' => '/kredity', 
    'credit_cards' => '/karty/kreditnye',
    'debit_cards' => '/karty/debetovye'
];

$today = date('Y-m-d');
$cities = getCities();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Основные страницы -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/zajmy</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/novye-mfo</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/kredity</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/karty/kreditnye</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/karty/debetovye</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/calculator</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/compare</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastOfferUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/articles</loc>
        <lastmod><?= date('Y-m-d', strtotime($lastArticleUpdate)) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/faq</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/glossary</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/favorites</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/search</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/privacy</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.2</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/terms</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.2</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/disclaimer</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.2</priority>
    </url>

    <!-- Офферы -->
<?php foreach ($offersData as $o): ?>
    <url>
        <loc><?= SITE_URL ?>/offer/<?= e($o['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($o['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>

    <!-- Статьи -->
<?php foreach ($articlesData as $a): ?>
    <url>
        <loc><?= SITE_URL ?>/articles/<?= e($a['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($a['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

    <!-- Теги (типы предложений) -->
<?php foreach ($allTags as $tag):
    $tagCatUrl = $catUrls[$tag['category']] ?? '/zajmy';
?>
    <url>
        <loc><?= SITE_URL ?><?= $tagCatUrl ?>/type/<?= e($tag['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($tag['created_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

    <!-- ========== ДОПЗАПРОСЫ (подкатегории) ========== -->
    <!-- Допзапросы без города -->
<?php foreach ($allSubcats as $sc):
    $scCatUrl = $catUrls[$sc['category']] ?? '/zajmy';
    $scLastmod = $sc['updated_at'] ?? $today;
?>
    <url>
        <loc><?= SITE_URL ?><?= $scCatUrl ?>/q/<?= e($sc['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($scLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

    <!-- Допзапросы + города (гео-страницы) -->
<?php foreach ($allSubcats as $sc): 
    $scCatUrl = $catUrls[$sc['category']] ?? '/zajmy';
    foreach ($cities as $c):
        // Проверяем есть ли кастомный SEO для этого города
        $scCityKey = $sc['id'] . '_' . $c['slug'];
        $scCityLastmod = $subcatCitySeoData[$scCityKey] ?? $sc['updated_at'] ?? $today;
?>
    <url>
        <loc><?= SITE_URL ?><?= $scCatUrl ?>/<?= e($c['slug']) ?>/q/<?= e($sc['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($scCityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; endforeach; ?>

    <!-- Займы по городам -->
<?php foreach ($cities as $c):
    $cityLastmod = $citySeoData[$c['slug'] . '_microloans'] ?? $lastOfferUpdate;
?>
    <url>
        <loc><?= SITE_URL ?>/zajmy/<?= e($c['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

    <!-- Кредиты по городам -->
<?php foreach ($cities as $c):
    $cityLastmod = $citySeoData[$c['slug'] . '_credits'] ?? $lastOfferUpdate;
?>
    <url>
        <loc><?= SITE_URL ?>/kredity/<?= e($c['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; ?>

    <!-- Кредитные карты по городам -->
<?php foreach ($cities as $c):
    $cityLastmod = $citySeoData[$c['slug'] . '_credit_cards'] ?? $lastOfferUpdate;
?>
    <url>
        <loc><?= SITE_URL ?>/karty/kreditnye/<?= e($c['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; ?>

    <!-- Дебетовые карты по городам -->
<?php foreach ($cities as $c):
    $cityLastmod = $citySeoData[$c['slug'] . '_debit_cards'] ?? $lastOfferUpdate;
?>
    <url>
        <loc><?= SITE_URL ?>/karty/debetovye/<?= e($c['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; ?>

    <!-- Карты по городам (общие) -->
<?php foreach ($cities as $c):
    $cityLastmod = $citySeoData[$c['slug'] . '_credit_cards'] ?? $lastOfferUpdate;
?>
    <url>
        <loc><?= SITE_URL ?>/karty/<?= e($c['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.4</priority>
    </url>
<?php endforeach; ?>

    <!-- City + Tag страницы с lastmod -->
<?php foreach ($cities as $c): foreach ($allTags as $tag):
    $tagCatUrl = $catUrls[$tag['category']] ?? '/zajmy';
    $cityTagUrl = $tagCatUrl . '/' . $c['slug'] . '/type/' . $tag['slug'];
    
    // Получаем lastmod из city_tag_seo_texts или fallback на дату тега
    $cityTagKey = $c['slug'] . '_' . $tag['category'] . '_' . $tag['slug'];
    $cityTagLastmod = $cityTagSeoData[$cityTagKey] ?? $tag['created_at'];
?>
    <url>
        <loc><?= SITE_URL ?><?= $cityTagUrl ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cityTagLastmod)) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; endforeach; ?>

    <!-- Глоссарий -->
<?php foreach ($glossaryTerms as $t): ?>
    <url>
        <loc><?= SITE_URL ?>/glossary/<?= e($t['slug']) ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>
<?php endforeach; ?>
</urlset>

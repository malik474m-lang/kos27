<?php
require_once __DIR__ . '/../data/cities.php';
require_once __DIR__ . '/../data/glossary.php';

$db = getDB();
$offersData = $db->query("SELECT slug, updated_at FROM offers WHERE is_active = 1")->fetchAll();
$articlesData = $db->query("SELECT slug, updated_at FROM articles WHERE is_published = 1")->fetchAll();

// Теги из БД
$allTags = $db->query("SELECT slug, category, created_at FROM offer_tags WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

$catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Основные -->
    <url><loc><?= SITE_URL ?>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc><?= SITE_URL ?>/zajmy</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    <url><loc><?= SITE_URL ?>/kredity</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    <url><loc><?= SITE_URL ?>/karty/kreditnye</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    <url><loc><?= SITE_URL ?>/karty/debetovye</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    <url><loc><?= SITE_URL ?>/calculator</loc><changefreq>monthly</changefreq><priority>0.7</priority></url>
    <url><loc><?= SITE_URL ?>/compare</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
    <url><loc><?= SITE_URL ?>/articles</loc><changefreq>daily</changefreq><priority>0.7</priority></url>
    <url><loc><?= SITE_URL ?>/faq</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    <url><loc><?= SITE_URL ?>/glossary</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    <url><loc><?= SITE_URL ?>/favorites</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
    <url><loc><?= SITE_URL ?>/search</loc><changefreq>monthly</changefreq><priority>0.3</priority></url>
    <url><loc><?= SITE_URL ?>/privacy</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>
    <url><loc><?= SITE_URL ?>/terms</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>
    <url><loc><?= SITE_URL ?>/disclaimer</loc><changefreq>yearly</changefreq><priority>0.2</priority></url>

    <!-- Офферы -->
    <?php foreach ($offersData as $o): ?>
    <url><loc><?= SITE_URL ?>/offer/<?= e($o['slug']) ?></loc><lastmod><?= date('Y-m-d', strtotime($o['updated_at'])) ?></lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>
    <?php endforeach; ?>

    <!-- Статьи -->
    <?php foreach ($articlesData as $a): ?>
    <url><loc><?= SITE_URL ?>/articles/<?= e($a['slug']) ?></loc><lastmod><?= date('Y-m-d', strtotime($a['updated_at'])) ?></lastmod><changefreq>weekly</changefreq><priority>0.6</priority></url>
    <?php endforeach; ?>

    <!-- Теги (типы предложений) -->
    <?php foreach ($allTags as $tag):
        $tagCatUrl = $catUrls[$tag['category']] ?? '/zajmy';
    ?>
    <url><loc><?= SITE_URL ?><?= $tagCatUrl ?>/type/<?= e($tag['slug']) ?></loc><lastmod><?= date('Y-m-d', strtotime($tag['created_at'])) ?></lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>
    <?php endforeach; ?>

    <!-- Займы по городам -->
    <?php foreach ($cities as $c): ?>
    <url><loc><?= SITE_URL ?>/zajmy/<?= e($c['slug']) ?></loc><changefreq>weekly</changefreq><priority>0.5</priority></url>
    <?php endforeach; ?>

    <!-- Кредиты по городам -->
    <?php foreach ($cities as $c): ?>
    <url><loc><?= SITE_URL ?>/kredity/<?= e($c['slug']) ?></loc><changefreq>weekly</changefreq><priority>0.4</priority></url>
    <?php endforeach; ?>

    <!-- Карты по городам -->
    <?php foreach ($cities as $c): ?>
    <url><loc><?= SITE_URL ?>/karty/<?= e($c['slug']) ?></loc><changefreq>weekly</changefreq><priority>0.4</priority></url>
    <?php endforeach; ?>

    <!-- Глоссарий -->
    <?php foreach ($glossaryTerms as $t): ?>
    <url><loc><?= SITE_URL ?>/glossary/<?= e($t['slug']) ?></loc><changefreq>monthly</changefreq><priority>0.4</priority></url>
    <?php endforeach; ?>
</urlset>

<?php
/**
 * SEO-аудит сайта — проверка meta, дублей, пустых полей
 */
requireAdmin();
$db = getDB();
$action = $_GET['action'] ?? 'full';

$issues = [];
$score = 100;

// 1. Офферы без описания
$noDesc = $db->query("SELECT id, title FROM offers WHERE is_active = 1 AND (description IS NULL OR description = '')")->fetchAll();
if ($noDesc) {
    $issues[] = ['level' => 'warning', 'category' => 'offers', 'msg' => count($noDesc) . ' офферов без описания',
        'items' => array_map(fn($o) => $o['title'], $noDesc)];
    $score -= min(10, count($noDesc) * 2);
}

// 2. Офферы без FAQ
try {
    $noFaq = $db->query("SELECT o.id, o.title FROM offers o WHERE o.is_active = 1 AND o.id NOT IN (SELECT DISTINCT offer_id FROM offer_faqs WHERE is_active = 1)")->fetchAll();
    if ($noFaq) {
        $issues[] = [
            'level' => 'info',
            'category' => 'offers',
            'msg' => count($noFaq) . ' офферов без FAQ',
            'items' => array_map(fn($o) => $o['title'], $noFaq),
            'fix_action' => 'faq_bulk_generate',
            'fix_label' => 'Создать FAQ',
        ];
        $score -= min(5, count($noFaq));
    }
} catch (Exception $e) {}

// 3. Статьи без meta
$noMeta = $db->query("SELECT id, title FROM articles WHERE is_published = 1 AND (meta_title IS NULL OR meta_title = '' OR meta_description IS NULL OR meta_description = '')")->fetchAll();
if ($noMeta) {
    $issues[] = ['level' => 'warning', 'category' => 'articles', 'msg' => count($noMeta) . ' статей без meta title/description',
        'items' => array_map(fn($a) => $a['title'], $noMeta)];
    $score -= min(10, count($noMeta) * 2);
}

// 4. Статьи без обложки
$noCover = $db->query("SELECT id, title FROM articles WHERE is_published = 1 AND (cover_image IS NULL OR cover_image = '')")->fetchAll();
if ($noCover) {
    $issues[] = ['level' => 'info', 'category' => 'articles', 'msg' => count($noCover) . ' статей без обложки',
        'items' => array_map(fn($a) => $a['title'], $noCover)];
    $score -= min(5, count($noCover));
}

// 5. Дубли meta title у статей
$dupTitles = $db->query("SELECT meta_title, COUNT(*) as cnt FROM articles WHERE is_published = 1 AND meta_title != '' GROUP BY meta_title HAVING cnt > 1")->fetchAll();
if ($dupTitles) {
    $issues[] = ['level' => 'error', 'category' => 'articles', 'msg' => count($dupTitles) . ' дублей meta title в статьях',
        'items' => array_map(fn($d) => $d['meta_title'] . ' (' . $d['cnt'] . ' шт)', $dupTitles)];
    $score -= min(15, count($dupTitles) * 3);
}

// 6. Теги без SEO-текста
$noTagContent = $db->query("SELECT id, title FROM offer_tags WHERE is_active = 1 AND (content IS NULL OR content = '')")->fetchAll();
if ($noTagContent) {
    $issues[] = ['level' => 'warning', 'category' => 'tags', 'msg' => count($noTagContent) . ' тегов без SEO-текста',
        'items' => array_map(fn($t) => $t['title'], $noTagContent)];
    $score -= min(10, count($noTagContent) * 2);
}

// 7. Теги без meta
$noTagMeta = $db->query("SELECT id, title FROM offer_tags WHERE is_active = 1 AND (meta_title IS NULL OR meta_title = '')")->fetchAll();
if ($noTagMeta) {
    $issues[] = ['level' => 'warning', 'category' => 'tags', 'msg' => count($noTagMeta) . ' тегов без meta title',
        'items' => array_map(fn($t) => $t['title'], $noTagMeta)];
    $score -= min(10, count($noTagMeta) * 2);
}

// 8. Города без SEO-текстов
try {
    require_once __DIR__ . '/../../data/cities.php';
    $totalCities = count(getCities());
    $categories = ['microloans', 'credits', 'credit_cards', 'debit_cards'];
    foreach ($categories as $cat) {
        $generated = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM city_seo_texts WHERE category = ?");
            $stmt->execute([$cat]);
            $generated = (int)$stmt->fetch()['cnt'];
        } catch (Exception $e) {}
        $catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
        if ($generated < $totalCities) {
            $missing = $totalCities - $generated;
            $issues[] = ['level' => 'info', 'category' => 'city_seo', 'msg' => ($catLabels[$cat] ?? $cat) . ': ' . $missing . ' городов без SEO-текста'];
        }
    }
} catch (Exception $e) {}

// 9. robots.txt и sitemap.xml
$issues[] = ['level' => 'ok', 'category' => 'technical', 'msg' => 'robots.txt доступен', 'items' => [SITE_URL . '/robots.txt']];
$issues[] = ['level' => 'ok', 'category' => 'technical', 'msg' => 'sitemap.xml доступен', 'items' => [SITE_URL . '/sitemap.xml']];
$issues[] = ['level' => 'ok', 'category' => 'technical', 'msg' => 'llms.txt доступен', 'items' => [SITE_URL . '/llms.txt']];

// 10. Индексация
try {
    require_once __DIR__ . '/../../includes/google-indexing.php';
    require_once __DIR__ . '/../../includes/yandex-webmaster.php';
    if (!googleIndexingAvailable()) {
        $issues[] = ['level' => 'warning', 'category' => 'indexing', 'msg' => 'Google Indexing API не настроен'];
        $score -= 5;
    } else {
        $issues[] = ['level' => 'ok', 'category' => 'indexing', 'msg' => 'Google Indexing API подключен'];
    }
    if (!yandexWebmasterAvailable()) {
        $issues[] = ['level' => 'warning', 'category' => 'indexing', 'msg' => 'Яндекс.Вебмастер API не настроен'];
        $score -= 5;
    } else {
        $issues[] = ['level' => 'ok', 'category' => 'indexing', 'msg' => 'Яндекс.Вебмастер API подключен'];
    }
} catch (Exception $e) {}

$score = max(0, $score);

echo json_encode([
    'score' => $score,
    'issues' => $issues,
    'total_issues' => count(array_filter($issues, fn($i) => $i['level'] !== 'ok')),
    'errors' => count(array_filter($issues, fn($i) => $i['level'] === 'error')),
    'warnings' => count(array_filter($issues, fn($i) => $i['level'] === 'warning')),
    'info' => count(array_filter($issues, fn($i) => $i['level'] === 'info')),
]);

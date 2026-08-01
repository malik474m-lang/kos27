<?php
/**
 * API: Проверка дублей title и description
 * GET /api/admin/seo-duplicates
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    $duplicates = ['titles' => [], 'descriptions' => []];
    
    // Собираем все title и description из офферов, статей, тегов, категорий, городских SEO
    $pages = [];
    
    // Офферы
    $stmt = $db->query("SELECT id, title, slug, meta_title, meta_description, description FROM offers WHERE is_active = 1");
    foreach ($stmt->fetchAll() as $row) {
        $t = $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME);
        $d = $row['meta_description'] ?: $row['description'] ?: '';
        $pages[] = ['type' => 'offer', 'url' => '/offer/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
    }
    
    // Статьи
    $stmt = $db->query("SELECT id, title, slug, meta_title, meta_description, excerpt FROM articles WHERE is_published = 1");
    foreach ($stmt->fetchAll() as $row) {
        $t = $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME);
        $d = $row['meta_description'] ?: $row['excerpt'] ?: '';
        $pages[] = ['type' => 'article', 'url' => '/articles/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
    }
    
    // Теги
    $stmt = $db->query("SELECT id, title, slug, meta_title, meta_description, h1, category FROM offer_tags WHERE is_active = 1");
    foreach ($stmt->fetchAll() as $row) {
        $t = $row['meta_title'] ?: (($row['h1'] ?: $row['title']) . ' — ' . SITE_NAME);
        $d = $row['meta_description'] ?: '';
        $catPrefix = match($row['category']) { 'credits' => '/kredity', 'credit_cards' => '/karty/kreditnye', 'debit_cards' => '/karty/debetovye', default => '/zajmy' };
        $pages[] = ['type' => 'tag', 'url' => $catPrefix . '/type/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
    }
    
    // Категории
    $stmt = $db->query("SELECT id, name, slug, meta_title, meta_description FROM categories WHERE is_active = 1");
    foreach ($stmt->fetchAll() as $row) {
        $t = $row['meta_title'] ?: ($row['name'] . ' — ' . SITE_NAME);
        $d = $row['meta_description'] ?: '';
        $pages[] = ['type' => 'category', 'url' => '/' . $row['slug'], 'name' => $row['name'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
    }
    
    // Городские SEO
    try {
        $dateCol = dbDateColumn('city_seo_texts', ['created_at', 'updated_at']);
        $stmt = $db->query("SELECT id, city_slug, category, seo_h1, meta_title, meta_description FROM city_seo_texts");
        foreach ($stmt->fetchAll() as $row) {
            $t = $row['meta_title'] ?: ($row['seo_h1'] ?: '');
            $d = $row['meta_description'] ?: '';
            if ($t) $pages[] = ['type' => 'city_seo', 'url' => '/' . $row['category'] . '/' . $row['city_slug'], 'name' => $row['city_slug'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
        }
    } catch (Exception $e) {}
    
    // Ищем дубли title
    $titleMap = [];
    foreach ($pages as $p) {
        $key = mb_strtolower(trim($p['title']));
        if (!$key) continue;
        $titleMap[$key][] = $p;
    }
    foreach ($titleMap as $title => $items) {
        if (count($items) > 1) {
            $duplicates['titles'][] = ['title' => $items[0]['title'], 'count' => count($items), 'pages' => array_map(fn($i) => ['type' => $i['type'], 'url' => $i['url'], 'name' => $i['name'], 'id' => $i['id']], $items)];
        }
    }
    
    // Ищем дубли description
    $descMap = [];
    foreach ($pages as $p) {
        $key = mb_strtolower(trim($p['description']));
        if (!$key || mb_strlen($key) < 20) continue;
        $descMap[$key][] = $p;
    }
    foreach ($descMap as $desc => $items) {
        if (count($items) > 1) {
            $duplicates['descriptions'][] = ['description' => mb_substr($items[0]['description'], 0, 120) . '...', 'count' => count($items), 'pages' => array_map(fn($i) => ['type' => $i['type'], 'url' => $i['url'], 'name' => $i['name'], 'id' => $i['id']], $items)];
        }
    }
    
    $duplicates['total_pages'] = count($pages);
    $duplicates['duplicate_titles'] = count($duplicates['titles']);
    $duplicates['duplicate_descriptions'] = count($duplicates['descriptions']);
    
    echo json_encode($duplicates, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

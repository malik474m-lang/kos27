<?php
/**
 * API: Проверка дублей title и description
 * GET /api/admin/seo-duplicates
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    $duplicates = ['titles' => [], 'descriptions' => []];
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
    $pages = [];

    $hasCol = function(string $table, string $column) use ($db): bool {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetch()['cnt'] > 0;
        } catch (Throwable $e) {
            return false;
        }
    };

    $expr = function(string $table, string $column, string $alias = null) use ($hasCol): string {
        $alias = $alias ?: $column;
        return $hasCol($table, $column) ? "`$column` AS `$alias`" : "NULL AS `$alias`";
    };

    // Офферы
    try {
        $sql = "SELECT id, title, slug, " . $expr('offers', 'meta_title') . ", " . $expr('offers', 'meta_description') . ", " . $expr('offers', 'description') . " FROM offers WHERE is_active = 1";
        foreach ($db->query($sql)->fetchAll() as $row) {
            $t = $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME);
            $d = $row['meta_description'] ?: $row['description'] ?: '';
            $pages[] = ['type' => 'offer', 'url' => '/offer/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
        }
    } catch (Throwable $e) {}

    // Статьи
    try {
        $sql = "SELECT id, title, slug, " . $expr('articles', 'meta_title') . ", " . $expr('articles', 'meta_description') . ", " . $expr('articles', 'excerpt') . ", " . $expr('articles', 'is_published') . " FROM articles";
        foreach ($db->query($sql)->fetchAll() as $row) {
            if (isset($row['is_published']) && (int)$row['is_published'] !== 1) continue;
            $t = $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME);
            $d = $row['meta_description'] ?: $row['excerpt'] ?: '';
            $pages[] = ['type' => 'article', 'url' => '/articles/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
        }
    } catch (Throwable $e) {}

    // Теги
    try {
        $sql = "SELECT id, title, slug, " . $expr('offer_tags', 'meta_title') . ", " . $expr('offer_tags', 'meta_description') . ", " . $expr('offer_tags', 'h1') . ", " . $expr('offer_tags', 'category') . ", " . $expr('offer_tags', 'is_active') . " FROM offer_tags";
        foreach ($db->query($sql)->fetchAll() as $row) {
            if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
            $t = $row['meta_title'] ?: (($row['h1'] ?: $row['title']) . ' — ' . SITE_NAME);
            $d = $row['meta_description'] ?: '';
            $catPrefix = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
            $pages[] = ['type' => 'tag', 'url' => $catPrefix . '/type/' . $row['slug'], 'name' => $row['title'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
        }
    } catch (Throwable $e) {}

    // Категории
    try {
        $sql = "SELECT id, name, slug, " . $expr('categories', 'meta_title') . ", " . $expr('categories', 'meta_description') . ", " . $expr('categories', 'is_active') . " FROM categories";
        foreach ($db->query($sql)->fetchAll() as $row) {
            if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
            $t = $row['meta_title'] ?: ($row['name'] . ' — ' . SITE_NAME);
            $d = $row['meta_description'] ?: '';
            $pages[] = ['type' => 'category', 'url' => '/' . ltrim($row['slug'], '/'), 'name' => $row['name'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
        }
    } catch (Throwable $e) {}

    // Городские SEO
    try {
        if ($hasCol('city_seo_texts', 'city_slug')) {
            $sql = "SELECT id, city_slug, " . $expr('city_seo_texts', 'category') . ", " . $expr('city_seo_texts', 'seo_h1') . ", " . $expr('city_seo_texts', 'meta_title') . ", " . $expr('city_seo_texts', 'meta_description') . " FROM city_seo_texts";
            foreach ($db->query($sql)->fetchAll() as $row) {
                $t = $row['meta_title'] ?: ($row['seo_h1'] ?: '');
                $d = $row['meta_description'] ?: '';
                if ($t) {
                    $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                    $pages[] = ['type' => 'city_seo', 'url' => $base . '/' . $row['city_slug'], 'name' => $row['city_slug'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
                }
            }
        }
    } catch (Throwable $e) {}

    // Город + тег SEO
    try {
        if ($hasCol('city_tag_seo_texts', 'city_slug')) {
            $sql = "SELECT id, city_slug, " . $expr('city_tag_seo_texts', 'category') . ", " . $expr('city_tag_seo_texts', 'tag_slug') . ", " . $expr('city_tag_seo_texts', 'seo_h1') . ", " . $expr('city_tag_seo_texts', 'meta_title') . ", " . $expr('city_tag_seo_texts', 'meta_description') . " FROM city_tag_seo_texts";
            foreach ($db->query($sql)->fetchAll() as $row) {
                $t = $row['meta_title'] ?: ($row['seo_h1'] ?: '');
                $d = $row['meta_description'] ?: '';
                if ($t) {
                    $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                    $pages[] = ['type' => 'city_tag_seo', 'url' => $base . '/' . $row['city_slug'] . '/type/' . $row['tag_slug'], 'name' => $row['city_slug'] . ' / ' . $row['tag_slug'], 'title' => $t, 'description' => $d, 'id' => $row['id']];
                }
            }
        }
    } catch (Throwable $e) {}

    // Дубли title
    $titleMap = [];
    foreach ($pages as $p) {
        $key = mb_strtolower(trim((string)$p['title']));
        if ($key === '') continue;
        $titleMap[$key][] = $p;
    }
    foreach ($titleMap as $items) {
        if (count($items) > 1) {
            $duplicates['titles'][] = [
                'title' => $items[0]['title'],
                'count' => count($items),
                'pages' => array_map(function($i){ return ['type'=>$i['type'],'url'=>$i['url'],'name'=>$i['name'],'id'=>$i['id']]; }, $items),
            ];
        }
    }

    // Дубли description
    $descMap = [];
    foreach ($pages as $p) {
        $key = mb_strtolower(trim((string)$p['description']));
        if ($key === '' || mb_strlen($key) < 20) continue;
        $descMap[$key][] = $p;
    }
    foreach ($descMap as $items) {
        if (count($items) > 1) {
            $duplicates['descriptions'][] = [
                'description' => mb_substr($items[0]['description'], 0, 120) . '...',
                'count' => count($items),
                'pages' => array_map(function($i){ return ['type'=>$i['type'],'url'=>$i['url'],'name'=>$i['name'],'id'=>$i['id']]; }, $items),
            ];
        }
    }

    $duplicates['total_pages'] = count($pages);
    $duplicates['duplicate_titles'] = count($duplicates['titles']);
    $duplicates['duplicate_descriptions'] = count($duplicates['descriptions']);

    echo json_encode($duplicates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

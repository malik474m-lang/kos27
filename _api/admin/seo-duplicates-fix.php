<?php
require_once __DIR__ . "/../../includes/ai-compat.php";
/**
 * API: Исправление дублей title / description через Yandex GPT
 * POST /api/admin/seo-duplicates-fix
 * body: { scope: 'titles'|'descriptions'|'all' }
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $scope = $input['scope'] ?? 'all';

    $hasCol = function(string $table, string $column) use ($db): bool {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return (int)$stmt->fetch()['cnt'] > 0;
        } catch (Throwable $e) {
            return false;
        }
    };

    $callYandex = function(string $prompt, string $systemPrompt = ''): ?string {
        if (!defined('YANDEX_GPT_API_KEY') || !defined('YANDEX_FOLDER_ID') || !YANDEX_GPT_API_KEY || !YANDEX_FOLDER_ID) return null;
        $messages = [];
        if ($systemPrompt) $messages[] = ['role' => 'system', 'text' => $systemPrompt];
        $messages[] = ['role' => 'user', 'text' => $prompt];
        $response = kosmozaimAIComplete('Ты помощник', $prompt);
if (!$response) return null;
        $json = json_decode($response, true);
        return trim((string)($json['result']['alternatives'][0]['message']['text'] ?? '')) ?: null;
    };

    $fetchPages = function() use ($db, $hasCol) {
        $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
        $pages = [];
        $expr = function(string $table, string $column, ?string $alias = null) use ($hasCol): string {
            $alias = $alias ?: $column;
            return $hasCol($table, $column) ? "`$column` AS `$alias`" : "NULL AS `$alias`";
        };

        // offers
        try {
            $sql = "SELECT id, title, slug, " . $expr('offers', 'meta_title') . ", " . $expr('offers', 'meta_description') . ", " . $expr('offers', 'description') . ", category FROM offers WHERE is_active = 1";
            foreach ($db->query($sql)->fetchAll() as $row) {
                $pages[] = ['entity' => 'offer', 'table' => 'offers', 'id' => (int)$row['id'], 'name' => $row['title'], 'slug' => $row['slug'], 'url' => '/offer/' . $row['slug'], 'title' => $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME), 'description' => $row['meta_description'] ?: ($row['description'] ?: ''), 'category' => $row['category'] ?? 'microloans'];
            }
        } catch (Throwable $e) {}

        // articles
        try {
            $sql = "SELECT id, title, slug, " . $expr('articles', 'meta_title') . ", " . $expr('articles', 'meta_description') . ", " . $expr('articles', 'excerpt') . ", " . $expr('articles', 'is_published') . " FROM articles";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_published']) && (int)$row['is_published'] !== 1) continue;
                $pages[] = ['entity' => 'article', 'table' => 'articles', 'id' => (int)$row['id'], 'name' => $row['title'], 'slug' => $row['slug'], 'url' => '/articles/' . $row['slug'], 'title' => $row['meta_title'] ?: ($row['title'] . ' — ' . SITE_NAME), 'description' => $row['meta_description'] ?: ($row['excerpt'] ?: '')];
            }
        } catch (Throwable $e) {}

        // tags
        try {
            $sql = "SELECT id, title, slug, " . $expr('offer_tags', 'meta_title') . ", " . $expr('offer_tags', 'meta_description') . ", " . $expr('offer_tags', 'h1') . ", " . $expr('offer_tags', 'category') . ", " . $expr('offer_tags', 'is_active') . " FROM offer_tags";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
                $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                $pages[] = ['entity' => 'tag', 'table' => 'offer_tags', 'id' => (int)$row['id'], 'name' => $row['title'], 'slug' => $row['slug'], 'url' => $base . '/type/' . $row['slug'], 'title' => $row['meta_title'] ?: (($row['h1'] ?: $row['title']) . ' — ' . SITE_NAME), 'description' => $row['meta_description'] ?: '', 'category' => $row['category'] ?? 'microloans'];
            }
        } catch (Throwable $e) {}

        // categories
        try {
            $sql = "SELECT id, name, slug, " . $expr('categories', 'meta_title') . ", " . $expr('categories', 'meta_description') . ", " . $expr('categories', 'is_active') . " FROM categories";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
                $pages[] = ['entity' => 'category', 'table' => 'categories', 'id' => (int)$row['id'], 'name' => $row['name'], 'slug' => $row['slug'], 'url' => '/' . ltrim($row['slug'], '/'), 'title' => $row['meta_title'] ?: ($row['name'] . ' — ' . SITE_NAME), 'description' => $row['meta_description'] ?: ''];
            }
        } catch (Throwable $e) {}

        // city seo
        try {
            if ($hasCol('city_seo_texts', 'city_slug')) {
                $sql = "SELECT id, city_slug, " . $expr('city_seo_texts', 'category') . ", " . $expr('city_seo_texts', 'seo_h1') . ", " . $expr('city_seo_texts', 'meta_title') . ", " . $expr('city_seo_texts', 'meta_description') . " FROM city_seo_texts";
                foreach ($db->query($sql)->fetchAll() as $row) {
                    $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                    $pages[] = ['entity' => 'city_seo', 'table' => 'city_seo_texts', 'id' => (int)$row['id'], 'name' => $row['city_slug'], 'slug' => $row['city_slug'], 'url' => $base . '/' . $row['city_slug'], 'title' => $row['meta_title'] ?: ($row['seo_h1'] ?: ''), 'description' => $row['meta_description'] ?: '', 'category' => $row['category'] ?? 'microloans'];
                }
            }
        } catch (Throwable $e) {}

        // city tag seo
        try {
            if ($hasCol('city_tag_seo_texts', 'city_slug')) {
                $sql = "SELECT id, city_slug, tag_slug, " . $expr('city_tag_seo_texts', 'category') . ", " . $expr('city_tag_seo_texts', 'seo_h1') . ", " . $expr('city_tag_seo_texts', 'meta_title') . ", " . $expr('city_tag_seo_texts', 'meta_description') . " FROM city_tag_seo_texts";
                foreach ($db->query($sql)->fetchAll() as $row) {
                    $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                    $pages[] = ['entity' => 'city_tag_seo', 'table' => 'city_tag_seo_texts', 'id' => (int)$row['id'], 'name' => $row['city_slug'] . ' / ' . $row['tag_slug'], 'slug' => $row['city_slug'] . '/' . $row['tag_slug'], 'url' => $base . '/' . $row['city_slug'] . '/type/' . $row['tag_slug'], 'title' => $row['meta_title'] ?: ($row['seo_h1'] ?: ''), 'description' => $row['meta_description'] ?: '', 'category' => $row['category'] ?? 'microloans'];
                }
            }
        } catch (Throwable $e) {}

        return $pages;
    };

    $makeUniqueTitle = function(array $page) use ($callYandex) {
        $prompt = "Перепиши SEO title для страницы финансового сайта так, чтобы он был уникальным, до 70 символов, без кавычек и без markdown. "
            . "Название/сущность: {$page['name']}. URL: {$page['url']}. Текущий title: {$page['title']}. Сайт: " . SITE_NAME . ".";
        $ai = $callYandex($prompt, 'Ты SEO-редактор. Возвращай только одну строку title без пояснений.');
        if ($ai) return trim($ai);
        $suffix = ($page['entity'] === 'city_seo' || $page['entity'] === 'city_tag_seo') ? ' — региональная подборка' : ' — ' . $page['name'];
        return trim(mb_substr(($page['title'] ?: $page['name']) . $suffix, 0, 70));
    };

    $makeUniqueDescription = function(array $page) use ($callYandex) {
        $prompt = "Перепиши meta description для страницы финансового сайта так, чтобы он был уникальным, до 160 символов, без markdown. "
            . "Название/сущность: {$page['name']}. URL: {$page['url']}. Текущее описание: {$page['description']}. Сайт: " . SITE_NAME . ".";
        $ai = $callYandex($prompt, 'Ты SEO-редактор. Возвращай только одну строку description без пояснений.');
        if ($ai) return trim($ai);
        return trim(mb_substr(($page['description'] ?: ('Актуальные условия и подбор предложений для «' . $page['name'] . '».')) . ' На сайте ' . SITE_NAME . '.', 0, 160));
    };

    $updateMeta = function(array $page, ?string $metaTitle, ?string $metaDescription) use ($db, $hasCol) {
        $table = $page['table'];
        $set = [];
        $params = [];
        if ($metaTitle !== null && $hasCol($table, 'meta_title')) { $set[] = 'meta_title = ?'; $params[] = $metaTitle; }
        if ($metaDescription !== null && $hasCol($table, 'meta_description')) { $set[] = 'meta_description = ?'; $params[] = $metaDescription; }
        if (!$set) return false;
        $params[] = $page['id'];
        $stmt = $db->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE id = ?');
        return $stmt->execute($params);
    };

    $pages = $fetchPages();
    $fixedTitles = 0;
    $fixedDescriptions = 0;
    $processed = [];

    if ($scope === 'titles' || $scope === 'all') {
        $map = [];
        foreach ($pages as $page) {
            $key = mb_strtolower(trim((string)$page['title']));
            if ($key === '') continue;
            $map[$key][] = $page;
        }
        foreach ($map as $items) {
            if (count($items) < 2) continue;
            foreach ($items as $page) {
                $newTitle = $makeUniqueTitle($page);
                if ($newTitle && $newTitle !== $page['title'] && $updateMeta($page, $newTitle, null)) {
                    $fixedTitles++;
                    $processed[] = ['type' => 'title', 'url' => $page['url'], 'value' => $newTitle];
                }
            }
        }
    }

    if ($scope === 'descriptions' || $scope === 'all') {
        $map = [];
        foreach ($pages as $page) {
            $key = mb_strtolower(trim((string)$page['description']));
            if ($key === '' || mb_strlen($key) < 20) continue;
            $map[$key][] = $page;
        }
        foreach ($map as $items) {
            if (count($items) < 2) continue;
            foreach ($items as $page) {
                $newDescription = $makeUniqueDescription($page);
                if ($newDescription && $newDescription !== $page['description'] && $updateMeta($page, null, $newDescription)) {
                    $fixedDescriptions++;
                    $processed[] = ['type' => 'description', 'url' => $page['url'], 'value' => $newDescription];
                }
            }
        }
    }

    apiCacheClear();
    pageCacheClear();

    echo json_encode([
        'success' => true,
        'fixed_titles' => $fixedTitles,
        'fixed_descriptions' => $fixedDescriptions,
        'processed' => array_slice($processed, 0, 50),
        'scope' => $scope,
        'provider' => (defined('YANDEX_GPT_API_KEY') && YANDEX_GPT_API_KEY) ? 'YandexGPT' : 'Fallback template'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

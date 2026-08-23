<?php
require_once __DIR__ . "/../../includes/ai-providers.php";
/**
 * API: Исправление дублей title / description (пакетная обработка)
 * POST /api/admin/seo-duplicates/fix
 * body: { scope: 'titles'|'descriptions'|'all', offset: int, limit: int }
 * Возвращает JSON-прогресс, frontend вызывает повторно до done=true.
 */
header('Content-Type: application/json; charset=UTF-8');

// Любой fatal → JSON, не HTML страница (фиксит "Unexpected token <")
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) { header('Content-Type: application/json; charset=UTF-8'); http_response_code(500); }
        echo json_encode(['error' => 'Fatal: ' . $err['message'] . ' @ ' . basename($err['file']) . ':' . $err['line']], JSON_UNESCAPED_UNICODE);
    }
});

try {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $scope = $input['scope'] ?? 'all';
    $offset = max(0, (int)($input['offset'] ?? 0));
    $limit = max(1, min(5, (int)($input['limit'] ?? 3)));

    $hasCol = function (string $table, string $column) use ($db): bool {
        static $cache = [];
        $k = $table . '.' . $column;
        if (isset($cache[$k])) return $cache[$k];
        try {
            $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return $cache[$k] = ((int)$stmt->fetch()['cnt'] > 0);
        } catch (Throwable $e) { return $cache[$k] = false; }
    };

    // AI — единый pipeline (OdiRouter/Yandex/GigaChat по приоритету). Никакого json_decode на текст.
    $aiText = function (string $prompt, string $systemPrompt = ''): ?string {
        $result = aiGenerateText($prompt, $systemPrompt);
        if (empty($result['success']) || empty($result['text'])) return null;
        $text = trim((string)$result['text']);
        $text = preg_replace('/^```(?:html|text)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        $firstLine = trim(explode("\n", $text)[0]);
        return $firstLine !== '' ? $firstLine : null;
    };

    $fetchPages = function () use ($db, $hasCol) {
        $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
        $pages = [];
        $expr = function (string $table, string $column, ?string $alias = null) use ($hasCol): string {
            $alias = $alias ?: $column;
            return $hasCol($table, $column) ? "`$column` AS `$alias`" : "NULL AS `$alias`";
        };

        try {
            $sql = "SELECT id, title, slug, " . $expr('offers','meta_title') . ", " . $expr('offers','meta_description') . ", " . $expr('offers','description') . ", category FROM offers WHERE is_active = 1";
            foreach ($db->query($sql)->fetchAll() as $row) {
                $pages[] = ['entity'=>'offer','table'=>'offers','id'=>(int)$row['id'],'name'=>(string)$row['title'],'slug'=>(string)$row['slug'],'url'=>'/offer/'.$row['slug'],'title'=>($row['meta_title'] ?: ($row['title'].' — '.SITE_NAME)),'description'=>($row['meta_description'] ?: ($row['description'] ?? '')),'category'=>$row['category'] ?? 'microloans'];
            }
        } catch (Throwable $e) {}

        try {
            $sql = "SELECT id, title, slug, " . $expr('articles','meta_title') . ", " . $expr('articles','meta_description') . ", " . $expr('articles','excerpt') . ", " . $expr('articles','is_published') . " FROM articles";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_published']) && (int)$row['is_published'] !== 1) continue;
                $pages[] = ['entity'=>'article','table'=>'articles','id'=>(int)$row['id'],'name'=>(string)$row['title'],'slug'=>(string)$row['slug'],'url'=>'/articles/'.$row['slug'],'title'=>($row['meta_title'] ?: ($row['title'].' — '.SITE_NAME)),'description'=>($row['meta_description'] ?: ($row['excerpt'] ?? ''))];
            }
        } catch (Throwable $e) {}

        try {
            $sql = "SELECT id, title, slug, " . $expr('offer_tags','meta_title') . ", " . $expr('offer_tags','meta_description') . ", " . $expr('offer_tags','h1') . ", " . $expr('offer_tags','category') . ", " . $expr('offer_tags','is_active') . " FROM offer_tags";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
                $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                $pages[] = ['entity'=>'tag','table'=>'offer_tags','id'=>(int)$row['id'],'name'=>(string)$row['title'],'slug'=>(string)$row['slug'],'url'=>$base.'/type/'.$row['slug'],'title'=>($row['meta_title'] ?: (($row['h1'] ?: $row['title']).' — '.SITE_NAME)),'description'=>($row['meta_description'] ?? ''),'category'=>$row['category'] ?? 'microloans'];
            }
        } catch (Throwable $e) {}

        try {
            $sql = "SELECT id, name, slug, " . $expr('categories','meta_title') . ", " . $expr('categories','meta_description') . ", " . $expr('categories','is_active') . " FROM categories";
            foreach ($db->query($sql)->fetchAll() as $row) {
                if (isset($row['is_active']) && (int)$row['is_active'] !== 1) continue;
                $pages[] = ['entity'=>'category','table'=>'categories','id'=>(int)$row['id'],'name'=>(string)$row['name'],'slug'=>(string)$row['slug'],'url'=>'/'.ltrim($row['slug'],'/'),'title'=>($row['meta_title'] ?: ($row['name'].' — '.SITE_NAME)),'description'=>($row['meta_description'] ?? '')];
            }
        } catch (Throwable $e) {}

        foreach ([['city_seo_texts','city_seo'], ['city_tag_seo_texts','city_tag_seo']] as [$seot,$entity]) {
            try {
                if (!$hasCol($seot,'city_slug')) continue;
                $tagField = $seot === 'city_tag_seo_texts' ? ", tag_slug" : '';
                $sql = "SELECT id, city_slug{$tagField}, " . $expr($seot,'category') . ", " . $expr($seot,'seo_h1') . ", " . $expr($seot,'meta_title') . ", " . $expr($seot,'meta_description') . " FROM {$seot}";
                foreach ($db->query($sql)->fetchAll() as $row) {
                    $base = $catUrls[$row['category'] ?? 'microloans'] ?? '/zajmy';
                    $name = isset($row['tag_slug']) ? ($row['city_slug'].' / '.$row['tag_slug']) : (string)$row['city_slug'];
                    $url = isset($row['tag_slug'])
                        ? $base . '/' . $row['city_slug'] . '/type/' . $row['tag_slug']
                        : $base . '/' . $row['city_slug'];
                    $pages[] = ['entity'=>$entity,'table'=>$seot,'id'=>(int)$row['id'],'name'=>$name,'slug'=>$name,'url'=>$url,
                        'title'=>$row['meta_title'] ? mb_substr(trim((string)$row['meta_title']),0,250) : '',
                        'description'=>$row['meta_description'] ? mb_substr(trim((string)$row['meta_description']),0,300) : '',
                        'category'=>$row['category'] ?? 'microloans'];
                }
            } catch (Throwable $e) {}
        }
        return $pages;
    };

    $makeUniqueTitle = function (array $page, array $siblings) use ($aiText) {
        $list = implode('; ', array_map(fn($s) => $s['name'], $siblings));
        $prompt = "Перепиши SEO title до 70 символов, уникальный среди похожих: {$list}. Название: {$page['name']}. Текущий title: {$page['title']}. Одна строка, без кавычек, без markdown.";
        $ai = $aiText($prompt, 'Ты SEO-редактор. Отвечай только текстом title одной строкой.');
        if ($ai && mb_strlen($ai) >= 10) return mb_substr(trim($ai,'" '), 0, 70);
        $suffix = in_array($page['entity'], ['city_seo','city_tag_seo'], true) ? ' — региональная подборка' : ' — ' . $page['name'];
        return mb_substr(trim(($page['title'] ?: $page['name']) . $suffix), 0, 70);
    };

    $makeUniqueDescription = function (array $page) use ($aiText) {
        $prompt = "Напиши уникальное meta description до 160 символов. Страница: {$page['name']} (URL {$page['url']}). Текущее описание: " . mb_substr((string)$page['description'], 0, 200) . ". Одна строка, без markdown.";
        $ai = $aiText($prompt, 'Ты SEO-редактор. Отвечай только текстом description одной строкой.');
        if ($ai && mb_strlen($ai) >= 30) return mb_substr(trim($ai,'" '), 0, 160);
        return mb_substr(trim(($page['description'] ?: ('Актуальные условия и подбор предложений для «' . $page['name'] . '».')) . ' На сайте ' . SITE_NAME . '.'), 0, 160);
    };

    $updateMeta = function (array $page, ?string $metaTitle, ?string $metaDescription) use ($db, $hasCol) {
        $table = $page['table'];
        $set = []; $params = [];
        if ($metaTitle !== null && $hasCol($table,'meta_title')) { $set[] = 'meta_title = ?'; $params[] = $metaTitle; }
        if ($metaDescription !== null && $hasCol($table,'meta_description')) { $set[] = 'meta_description = ?'; $params[] = $metaDescription; }
        if (!$set) return false;
        $params[] = $page['id'];
        return (bool)$db->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($params);
    };

    $pages = $fetchPages();

    // Очередь задач: первая страница группы остаётся оригиналом
    $tasks = [];
    if ($scope === 'titles' || $scope === 'all') {
        $map = [];
        foreach ($pages as $page) {
            $key = mb_strtolower(trim((string)$page['title']));
            if ($key === '') continue;
            $map[$key][] = $page;
        }
        foreach ($map as $items) {
            if (count($items) < 2) continue;
            foreach (array_slice($items, 1) as $page) {
                $tasks[] = ['field' => 'title', 'page' => $page, 'siblings' => array_slice($items, 0, 3)];
            }
        }
    }
    if ($scope === 'descriptions' || $scope === 'all') {
        $dmap = [];
        foreach ($pages as $page) {
            $key = mb_strtolower(trim((string)$page['description']));
            if ($key === '' || mb_strlen($key) < 20) continue;
            $dmap[$key][] = $page;
        }
        foreach ($dmap as $items) {
            if (count($items) < 2) continue;
            foreach (array_slice($items, 1) as $page) {
                $tasks[] = ['field' => 'description', 'page' => $page, 'siblings' => []];
            }
        }
    }

    $total = count($tasks);
    if ($total === 0) {
        echo json_encode(['success'=>true,'done'=>true,'total'=>0,'processed'=>0,'fixed'=>0,'fixed_titles'=>0,'fixed_descriptions'=>0,'remaining'=>0,'provider'=>'—'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $batch = array_slice($tasks, $offset, $limit);
    $processed = [];
    $fixedTitles = 0; $fixedDescriptions = 0; $failed = 0;

    foreach ($batch as $task) {
        $page = $task['page'];
        if ($task['field'] === 'title') {
            $newTitle = $makeUniqueTitle($page, $task['siblings']);
            if ($newTitle && $newTitle !== $page['title'] && $updateMeta($page, $newTitle, null)) {
                $fixedTitles++;
                $processed[] = ['type'=>'title','url'=>$page['url'],'value'=>$newTitle];
            } else { $failed++; }
        } else {
            $newDesc = $makeUniqueDescription($page);
            if ($newDesc && $newDesc !== $page['description'] && $updateMeta($page, null, $newDesc)) {
                $fixedDescriptions++;
                $processed[] = ['type'=>'description','url'=>$page['url'],'value'=>mb_substr($newDesc,0,90).'…'];
            } else { $failed++; }
        }
    }

    $nextOffset = $offset + count($batch);
    $done = $nextOffset >= $total;

    echo json_encode([
        'success'=>true,'done'=>$done,'total'=>$total,'offset'=>$offset,
        'batch_size'=>count($batch),'processed'=>$nextOffset,
        'remaining'=>max(0,$total-$nextOffset),
        'fixed'=>count($processed),'fixed_titles'=>$fixedTitles,'fixed_descriptions'=>$fixedDescriptions,
        'failed'=>$failed,'items'=>array_slice($processed,0,5),
        'scope'=>$scope,'provider'=>'ai-pipeline',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

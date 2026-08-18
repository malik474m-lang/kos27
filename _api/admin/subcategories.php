<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
require_once __DIR__ . '/../../includes/subcategories.php';
require_once __DIR__ . '/../../includes/ai-providers.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
ensureSubcategoriesTable();

// GET — список
if ($method === 'GET') {
    $cat = $_GET['category'] ?? '';
    if ($cat) {
        $rows = $db->prepare("SELECT * FROM subcategories WHERE category = ? ORDER BY sort_order ASC, title ASC");
        $rows->execute([$cat]);
    } else {
        $rows = $db->query("SELECT * FROM subcategories ORDER BY category ASC, sort_order ASC, title ASC");
    }
    echo json_encode($rows->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

// POST — создать / сгенерировать SEO
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? 'create';

    if ($action === 'generate-seo') {
        $id = (int)($data['id'] ?? 0);
        $row = $db->prepare("SELECT * FROM subcategories WHERE id = ?");
        $row->execute([$id]);
        $sc = $row->fetch();
        if (!$sc) { echo json_encode(['error' => 'Не найдено']); exit; }

        $catLabels = ['microloans'=>'займы','credits'=>'кредиты','credit_cards'=>'кредитные карты','debit_cards'=>'дебетовые карты'];
        $catLabel = $catLabels[$sc['category']] ?? 'финансовые продукты';
        $prompt = "Напиши SEO-текст для страницы подкатегории финансового сайта.\nТема: {$sc['title']}\nКатегория: {$catLabel}\nНапиши 2-3 абзаца HTML (h3 + p). Без markdown. Полезный текст для посетителей.";
        $sys = "Ты SEO-копирайтер финансового сайта. Пиши на русском. Возвращай HTML-текст.";

        $aiResult = aiGenerateText($prompt, $sys);
        $seoText = '';
        if (!empty($aiResult['success']) && !empty($aiResult['text'])) {
            $seoText = trim($aiResult['text']);
            $seoText = preg_replace('/^```\s*html?\s*/i', '', $seoText);
            $seoText = preg_replace('/\s*```$/i', '', $seoText);
        }

        $h1 = $sc['title'];
        $metaTitle = mb_substr($sc['title'] . ' | ' . SITE_NAME, 0, 70);
        $metaDesc = mb_substr("Сравните лучшие {$catLabel} по запросу «{$sc['title']}». Актуальные условия онлайн.", 0, 160);
        $desc = mb_substr("Подборка {$catLabel} по запросу «{$sc['title']}». Сравните условия и выберите лучшее предложение.", 0, 250);

        $db->prepare("UPDATE subcategories SET h1=?, description=?, meta_title=?, meta_description=?, seo_text=? WHERE id=?")
           ->execute([$h1, $desc, $metaTitle, $metaDesc, $seoText, $id]);

        echo json_encode(['success' => true, 'seo_text' => $seoText, 'provider' => $aiResult['provider'] ?? 'template']);
        exit;
    }

    // Создать
    $title = trim((string)($data['title'] ?? ''));
    $category = trim((string)($data['category'] ?? 'microloans'));
    if (!$title) { echo json_encode(['error' => 'Название обязательно']); exit; }

    $slug = function_exists('slugify') ? slugify($title) : preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($title));
    $icon = trim((string)($data['icon'] ?? '📋'));
    $rules = $data['filter_rules'] ?? [];
    if (is_array($rules)) $rules = json_encode($rules, JSON_UNESCAPED_UNICODE);

    $db->prepare("INSERT INTO subcategories (title, slug, category, icon, filter_rules, sort_order, is_active) VALUES (?,?,?,?,?,?,1)")
       ->execute([$title, $slug, $category, $icon, $rules, (int)($data['sort_order'] ?? 0)]);

    echo json_encode(['success' => true, 'id' => (int)$db->lastInsertId()]);
    exit;
}

// PUT — обновить
if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id обязателен']); exit; }

    $fields = [];
    $params = [];
    foreach (['title','slug','icon','h1','description','meta_title','meta_description','seo_text','category'] as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $params[] = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
        }
    }
    if (array_key_exists('filter_rules', $data)) {
        $fields[] = "filter_rules = ?";
        $params[] = is_array($data['filter_rules']) ? json_encode($data['filter_rules'], JSON_UNESCAPED_UNICODE) : (string)$data['filter_rules'];
    }
    if (array_key_exists('is_active', $data)) { $fields[] = "is_active = ?"; $params[] = $data['is_active'] ? 1 : 0; }
    if (array_key_exists('sort_order', $data)) { $fields[] = "sort_order = ?"; $params[] = (int)$data['sort_order']; }

    if ($fields) {
        $params[] = $id;
        $db->prepare("UPDATE subcategories SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
    }
    echo json_encode(['success' => true]);
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($data['id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM subcategory_city_seo WHERE subcategory_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM subcategories WHERE id = ?")->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

<?php
require_once __DIR__ . '/../../includes/subcategories.php';
ensureSubcategoriesTable();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $cat = $_GET['category'] ?? '';
    if ($cat) {
        $stmt = $db->prepare("SELECT * FROM subcategories WHERE category = ? ORDER BY sort_order ASC, title ASC");
        $stmt->execute([$cat]);
    } else {
        $stmt = $db->query("SELECT * FROM subcategories ORDER BY category ASC, sort_order ASC, title ASC");
    }
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    
    if ($action === 'generate-seo') {
        require_once __DIR__ . '/../../includes/ai-providers.php';
        $title = trim((string)($data['title'] ?? ''));
        $category = trim((string)($data['category'] ?? 'microloans'));
        $catLabels = ['microloans'=>'займы','credits'=>'кредиты','credit_cards'=>'кредитные карты','debit_cards'=>'дебетовые карты'];
        $catLabel = $catLabels[$category] ?? 'финансовые продукты';
        $cityName = trim((string)($data['cityName'] ?? ''));
        $cityPrep = trim((string)($data['cityPrep'] ?? ''));
        
        $geoContext = $cityName ? " в городе {$cityName} ({$cityPrep})" : '';
        $prompt = "Напиши SEO-текст для страницы \"{$title}{$geoContext}\" финансового сайта. "
            . "Категория: {$catLabel}. Текст 200-400 слов. HTML: h3, p, ul, li. Без markdown.";
        $system = "Ты SEO-копирайтер финансового сайта. Пиши на русском. Форматируй HTML-тегами.";
        $result = aiGenerateText($prompt, $system);
        
        $text = ($result['success'] && !empty($result['text'])) ? trim($result['text']) : '';
        $text = preg_replace('/^```(?:html)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        
        echo json_encode([
            'success' => !empty($text),
            'seo_text' => $text,
            'seo_h1' => $title . ($cityPrep ? ' в ' . $cityPrep : ''),
            'meta_title' => mb_substr($title . ($cityPrep ? ' в ' . $cityPrep : '') . ' | ' . SITE_NAME, 0, 70),
            'meta_description' => mb_substr('Сравните лучшие ' . $catLabel . ' по запросу «' . $title . '»' . ($cityPrep ? ' в ' . $cityPrep : '') . '. Актуальные условия.', 0, 160),
            'provider' => $result['provider'] ?? 'template',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $title = trim((string)($data['title'] ?? ''));
    $slug = trim((string)($data['slug'] ?? '')) ?: slugify($title);
    $category = trim((string)($data['category'] ?? 'microloans'));
    
    if (!$title || !$slug) {
        echo json_encode(['error' => 'Название и slug обязательны']);
        exit;
    }
    
    $filterRules = is_array($data['filter_rules'] ?? null) ? json_encode($data['filter_rules'], JSON_UNESCAPED_UNICODE) : ($data['filter_rules'] ?? '{}');
    
    $db->prepare("INSERT INTO subcategories (slug, title, category, filter_rules, seo_h1, meta_title, meta_description, seo_text, icon, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $slug, $title, $category, $filterRules,
           $data['seo_h1'] ?? $title,
           $data['meta_title'] ?? ($title . ' | ' . SITE_NAME),
           $data['meta_description'] ?? '',
           $data['seo_text'] ?? '',
           $data['icon'] ?? '📋',
           (int)($data['sort_order'] ?? 0),
           !empty($data['is_active'] ?? true) ? 1 : 0,
       ]);
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($data['id'] ?? $_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id обязателен']); exit; }
    
    $filterRules = is_array($data['filter_rules'] ?? null) ? json_encode($data['filter_rules'], JSON_UNESCAPED_UNICODE) : ($data['filter_rules'] ?? '{}');
    
    $db->prepare("UPDATE subcategories SET title=?, slug=?, category=?, filter_rules=?, seo_h1=?, meta_title=?, meta_description=?, seo_text=?, icon=?, sort_order=?, is_active=? WHERE id=?")
       ->execute([
           trim((string)($data['title'] ?? '')),
           trim((string)($data['slug'] ?? '')),
           trim((string)($data['category'] ?? 'microloans')),
           $filterRules,
           $data['seo_h1'] ?? '',
           $data['meta_title'] ?? '',
           $data['meta_description'] ?? '',
           $data['seo_text'] ?? '',
           $data['icon'] ?? '📋',
           (int)($data['sort_order'] ?? 0),
           !empty($data['is_active']) ? 1 : 0,
           $id,
       ]);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM subcategory_city_seo WHERE subcategory_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM subcategories WHERE id = ?")->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

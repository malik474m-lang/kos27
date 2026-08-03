<?php
require_once __DIR__ . '/../../includes/page-cache.php';
require_once __DIR__ . '/../../includes/audit-log.php';

function pickTagIcon(string $title, string $category): string {
    $t = mb_strtolower(trim($title));
    $map = [
        'кэшбэк' => '💸', 'кешбек' => '💸', 'бонус' => '🎁', 'льгот' => '🗓️',
        'без процент' => '🆓', '0%' => '🆓', 'без отказ' => '✅', 'студент' => '🎓',
        'пенсион' => '👴', 'на карту' => '💳', 'сроч' => '⚡', 'плохой кредитной истории' => '📊',
        'рефинанс' => '♻️', 'наличными' => '💵', 'дебет' => '🪪', 'кредитн' => '💳',
        'ипотек' => '🏠', 'вклад' => '🏦', 'страхов' => '🛡️', 'авто' => '🚗'
    ];
    foreach ($map as $needle => $emoji) { if (str_contains($t, $needle)) return $emoji; }
    return match($category) {
        'microloans' => '💵',
        'credits' => '🏦',
        'credit_cards' => '💳',
        'debit_cards' => '🪪',
        default => '🏷️',
    };
}
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$title = trim($data['title'] ?? '');
if (!$title) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }

$slug = $data['slug'] ?? '';
if (!$slug) $slug = slugify($title) . '-' . time();

$ex = $db->prepare("SELECT id FROM offer_tags WHERE slug = ?"); $ex->execute([$slug]);
if ($ex->fetch()) { http_response_code(400); echo json_encode(['error' => "Slug '$slug' уже существует"]); exit; }

$icon = trim((string)($data['icon'] ?? ''));
if ($icon === '') $icon = pickTagIcon((string)($data['title'] ?? ''), (string)($data['category'] ?? 'microloans'));

$features = $data['features'] ?? '[]';
if (is_array($features)) $features = json_encode($features, JSON_UNESCAPED_UNICODE);

$db->prepare("INSERT INTO offer_tags (slug, title, h1, description, meta_title, meta_description, content, icon, category, features, search_queries, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
   ->execute([$slug, $title, $data['h1'] ?? $title, $data['description'] ?? '', $data['metaTitle'] ?? '', $data['metaDescription'] ?? '', $data['content'] ?? '', $icon, $data['category'] ?? 'microloans', $features, $data['searchQueries'] ?? '', $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0)]);

$newId = $db->lastInsertId();

// Аудит
auditLog('create', 'tag', (int)$newId, $title);

@unlink(__DIR__ . '/../../data/tag-links-cache.json');
require_once __DIR__ . '/../../includes/auto-indexing.php';
$catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
$catU = $catUrls[$data['category'] ?? 'microloans'] ?? '/zajmy';
autoSubmitUrl($catU . '/type/' . $slug);
pageCacheClear();
echo json_encode(['success' => true, 'id' => $newId]);

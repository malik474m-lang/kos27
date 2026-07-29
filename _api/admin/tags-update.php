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

// Старые данные
$oldStmt = $db->prepare("SELECT * FROM offer_tags WHERE id = ?");
$oldStmt->execute([$itemId]);
$oldData = $oldStmt->fetch();

$icon = trim((string)($data['icon'] ?? ''));
if ($icon === '') $icon = pickTagIcon((string)($data['title'] ?? ''), (string)($data['category'] ?? 'microloans'));

$features = $data['features'] ?? '[]';
if (is_array($features)) $features = json_encode($features, JSON_UNESCAPED_UNICODE);

$db->prepare("UPDATE offer_tags SET slug=?, title=?, h1=?, description=?, meta_title=?, meta_description=?, content=?, icon=?, category=?, features=?, search_queries=?, is_active=?, sort_order=? WHERE id=?")
   ->execute([$data['slug'] ?? '', trim($data['title'] ?? ''), $data['h1'] ?? '', $data['description'] ?? '', $data['metaTitle'] ?? '', $data['metaDescription'] ?? '', $data['content'] ?? '', $icon, $data['category'] ?? 'microloans', $features, $data['searchQueries'] ?? '', $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0), $itemId]);

// Аудит
$newData = ['title' => $data['title'] ?? '', 'is_active' => $data['isActive'] ?? true];
$changes = $oldData ? auditDiff($oldData, $newData, ['title', 'is_active']) : null;
auditLog('update', 'tag', $itemId, $data['title'] ?? $oldData['title'] ?? '', $changes);

@unlink(__DIR__ . '/../../data/tag-links-cache.json');
pageCacheClear();
echo json_encode(['success' => true]);

<?php
require_once __DIR__ . '/../../includes/page-cache.php';
register_shutdown_function('pageCacheClear');

header('Content-Type: application/json; charset=UTF-8');

$db = getDB();

function sanitizeArticleMetaField(?string $value): string {
    $value = (string)($value ?? '');
    $value = strip_tags($value);
    $value = preg_replace('/\x{FFFD}+/u', '', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
}

$rows = $db->query("SELECT id, title, excerpt, meta_title, meta_description FROM articles ORDER BY id ASC")->fetchAll();
$updated = 0;
$checked = count($rows);

foreach ($rows as $row) {
    $cleanTitle = sanitizeArticleMetaField($row['title'] ?? '');
    $cleanExcerpt = sanitizeArticleMetaField($row['excerpt'] ?? '');
    $cleanMetaTitle = sanitizeArticleMetaField($row['meta_title'] ?? '');
    $cleanMetaDescription = sanitizeArticleMetaField($row['meta_description'] ?? '');

    if (
        $cleanTitle !== (string)($row['title'] ?? '') ||
        $cleanExcerpt !== (string)($row['excerpt'] ?? '') ||
        $cleanMetaTitle !== (string)($row['meta_title'] ?? '') ||
        $cleanMetaDescription !== (string)($row['meta_description'] ?? '')
    ) {
        $db->prepare("UPDATE articles SET title = ?, excerpt = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$cleanTitle, $cleanExcerpt, $cleanMetaTitle, $cleanMetaDescription, (int)$row['id']]);
        $updated++;
    }
}

echo json_encode([
    'success' => true,
    'checked' => $checked,
    'updated' => $updated,
    'message' => "Проверено {$checked} статей, очищено {$updated}."
], JSON_UNESCAPED_UNICODE);

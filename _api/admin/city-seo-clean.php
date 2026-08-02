<?php
// Очистка GPT/markdown мусора в SEO-текстах городов
require_once __DIR__ . '/../../includes/city-seo.php';

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$category = trim((string)($data['category'] ?? ''));
$citySlugs = $data['citySlugs'] ?? [];
if (!is_array($citySlugs)) $citySlugs = [];
$citySlugs = array_values(array_filter(array_map('strval', $citySlugs)));

$sql = "SELECT id, city_slug, category, seo_h1, seo_text, meta_title, meta_description FROM city_seo_texts WHERE 1=1";
$params = [];
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
if ($citySlugs) {
    $placeholders = implode(',', array_fill(0, count($citySlugs), '?'));
    $sql .= " AND city_slug IN ($placeholders)";
    foreach ($citySlugs as $slug) $params[] = $slug;
}
$sql .= " ORDER BY city_slug ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$cleaned = 0;
$updatedFields = 0;
$updateStmt = $db->prepare("UPDATE city_seo_texts SET seo_h1 = ?, seo_text = ?, meta_title = ?, meta_description = ? WHERE id = ?");

$cleanPlain = static function(?string $text): string {
    $text = (string)$text;
    if ($text === '') return '';
    $text = preg_replace('/^```\s*html?\s*\n?/i', '', $text);
    $text = preg_replace('/\n?```\s*$/', '', $text);
    $text = preg_replace('/```/', '', $text);
    $text = preg_replace('/^#+\s+/m', '', $text);
    return trim($text);
};

foreach ($rows as $row) {
    $newSeoText = cleanGptHtml((string)($row['seo_text'] ?? ''));
    $newSeoH1 = $cleanPlain($row['seo_h1'] ?? '');
    $newMetaTitle = $cleanPlain($row['meta_title'] ?? '');
    $newMetaDescription = $cleanPlain($row['meta_description'] ?? '');

    $fieldChanges = 0;
    if ($newSeoText !== (string)($row['seo_text'] ?? '')) $fieldChanges++;
    if ($newSeoH1 !== (string)($row['seo_h1'] ?? '')) $fieldChanges++;
    if ($newMetaTitle !== (string)($row['meta_title'] ?? '')) $fieldChanges++;
    if ($newMetaDescription !== (string)($row['meta_description'] ?? '')) $fieldChanges++;

    if ($fieldChanges > 0) {
        $updateStmt->execute([$newSeoH1, $newSeoText, $newMetaTitle, $newMetaDescription, $row['id']]);
        $cleaned++;
        $updatedFields += $fieldChanges;
    }
}

require_once __DIR__ . '/../../includes/page-cache.php';
apiCacheClear();
pageCacheClear();

echo json_encode([
    'success' => true,
    'cleaned' => $cleaned,
    'updated_fields' => $updatedFields,
    'total' => count($rows),
    'category' => $category,
    'city_slugs' => $citySlugs,
]);

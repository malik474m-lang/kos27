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
$updatedSeoText = 0;
$updatedSeoH1 = 0;
$updatedMetaTitle = 0;
$updatedMetaDescription = 0;
$updateStmt = $db->prepare("UPDATE city_seo_texts SET seo_h1 = ?, seo_text = ?, meta_title = ?, meta_description = ? WHERE id = ?");

foreach ($rows as $row) {
    $newSeoText = cleanSeoTextToPlain((string)($row['seo_text'] ?? ''));
    $newSeoH1 = cleanGptPlain((string)($row['seo_h1'] ?? ''));
    $newMetaTitle = cleanGptPlain((string)($row['meta_title'] ?? ''));
    $newMetaDescription = cleanGptPlain((string)($row['meta_description'] ?? ''));

    $fieldChanges = 0;
    $seoTextChanged = $newSeoText !== (string)($row['seo_text'] ?? '');
    $seoH1Changed = $newSeoH1 !== (string)($row['seo_h1'] ?? '');
    $metaTitleChanged = $newMetaTitle !== (string)($row['meta_title'] ?? '');
    $metaDescriptionChanged = $newMetaDescription !== (string)($row['meta_description'] ?? '');
    if ($seoTextChanged) $fieldChanges++;
    if ($seoH1Changed) $fieldChanges++;
    if ($metaTitleChanged) $fieldChanges++;
    if ($metaDescriptionChanged) $fieldChanges++;

    if ($fieldChanges > 0) {
        $updateStmt->execute([$newSeoH1, $newSeoText, $newMetaTitle, $newMetaDescription, $row['id']]);
        $cleaned++;
        $updatedFields += $fieldChanges;
        if ($seoTextChanged) $updatedSeoText++;
        if ($seoH1Changed) $updatedSeoH1++;
        if ($metaTitleChanged) $updatedMetaTitle++;
        if ($metaDescriptionChanged) $updatedMetaDescription++;
    }
}

require_once __DIR__ . '/../../includes/page-cache.php';
apiCacheClear();
pageCacheClear();

echo json_encode([
    'success' => true,
    'cleaned' => $cleaned,
    'updated_fields' => $updatedFields,
    'updated_seo_text' => $updatedSeoText,
    'updated_seo_h1' => $updatedSeoH1,
    'updated_meta_title' => $updatedMetaTitle,
    'updated_meta_description' => $updatedMetaDescription,
    'total' => count($rows),
    'category' => $category,
    'city_slugs' => $citySlugs,
]);

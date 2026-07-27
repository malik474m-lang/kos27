<?php
// Очистка всех существующих GPT-текстов от ```html``` и markdown
require_once __DIR__ . '/../../includes/city-seo.php';

$db = getDB();
$all = $db->query("SELECT id, seo_text FROM city_seo_texts WHERE seo_text IS NOT NULL")->fetchAll();

$cleaned = 0;
$stmt = $db->prepare("UPDATE city_seo_texts SET seo_text = ? WHERE id = ?");

foreach ($all as $row) {
    $original = $row['seo_text'];
    $clean = cleanGptHtml($original);
    if ($clean !== $original) {
        $stmt->execute([$clean, $row['id']]);
        $cleaned++;
    }
}

echo json_encode(['success' => true, 'cleaned' => $cleaned, 'total' => count($all)]);

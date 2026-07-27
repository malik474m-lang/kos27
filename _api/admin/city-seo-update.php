<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$db->prepare("UPDATE city_seo_texts SET seo_h1=?, seo_text=?, meta_description=?, generated_by='manual' WHERE id=?")
   ->execute([$data['seoH1'] ?? '', $data['seoText'] ?? '', $data['metaDescription'] ?? '', $itemId]);

echo json_encode(['success' => true]);

<?php
require_once __DIR__ . '/../../includes/city-seo.php';
ensureCityTagSeoTable();
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("UPDATE city_tag_seo_texts SET meta_title=?, seo_h1=?, seo_text=?, meta_description=?, generated_by='manual' WHERE id=?")
   ->execute([$data['metaTitle'] ?? '', $data['seoH1'] ?? '', $data['seoText'] ?? '', $data['metaDescription'] ?? '', $itemId]);
apiCacheClear();
echo json_encode(['success' => true]);

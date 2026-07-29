<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$features = $data['features'] ?? '[]';
if (is_array($features)) $features = json_encode($features, JSON_UNESCAPED_UNICODE);

$db->prepare("UPDATE offer_tags SET slug=?, title=?, h1=?, description=?, meta_description=?, content=?, icon=?, category=?, features=?, search_queries=?, is_active=?, sort_order=? WHERE id=?")
   ->execute([$data['slug'] ?? '', trim($data['title'] ?? ''), $data['h1'] ?? '', $data['description'] ?? '', $data['metaDescription'] ?? '', $data['content'] ?? '', $data['icon'] ?? '🏷️', $data['category'] ?? 'microloans', $features, $data['searchQueries'] ?? '', $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0), $itemId]);

@unlink(__DIR__ . '/../../data/tag-links-cache.json');
@unlink(__DIR__ . '/../../data/page_cache/' . md5('/zajmy') . '.html');
echo json_encode(['success' => true]);

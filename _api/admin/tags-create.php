<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$title = trim($data['title'] ?? '');
if (!$title) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }

$slug = $data['slug'] ?? '';
if (!$slug) $slug = slugify($title) . '-' . time();

$ex = $db->prepare("SELECT id FROM offer_tags WHERE slug = ?"); $ex->execute([$slug]);
if ($ex->fetch()) { http_response_code(400); echo json_encode(['error' => "Slug '$slug' уже существует"]); exit; }

$features = $data['features'] ?? '[]';
if (is_array($features)) $features = json_encode($features, JSON_UNESCAPED_UNICODE);

$db->prepare("INSERT INTO offer_tags (slug, title, h1, description, meta_description, content, icon, category, features, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
   ->execute([$slug, $title, $data['h1'] ?? $title, $data['description'] ?? '', $data['metaDescription'] ?? '', $data['content'] ?? '', $data['icon'] ?? '🏷️', $data['category'] ?? 'microloans', $features, $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0)]);

echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

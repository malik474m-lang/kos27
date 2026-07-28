<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$slug = trim($data['slug'] ?? '');
if (!$name) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }
if (!$slug) $slug = slugify($name);

if ($id) {
    $db->prepare("UPDATE categories SET name=?, slug=?, icon=?, h1=?, description=?, meta_title=?, meta_description=?, seo_text=?, parent_id=?, show_in_header=?, show_in_footer=?, footer_section=?, is_active=?, sort_order=? WHERE id=?")
       ->execute([$name, $slug, $data['icon'] ?? null, $data['h1'] ?? null, $data['description'] ?? null, $data['metaTitle'] ?? null, $data['metaDescription'] ?? null, $data['seoText'] ?? null, $data['parentId'] ?: null, $data['showInHeader'] ?? true ? 1 : 0, $data['showInFooter'] ?? true ? 1 : 0, $data['footerSection'] ?? 'products', $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0), $id]);
} else {
    $exists = $db->prepare("SELECT id FROM categories WHERE slug = ?"); $exists->execute([$slug]);
    if ($exists->fetch()) { http_response_code(400); echo json_encode(['error' => "Slug '$slug' уже существует"]); exit; }
    $db->prepare("INSERT INTO categories (name, slug, icon, h1, description, meta_title, meta_description, seo_text, parent_id, show_in_header, show_in_footer, footer_section, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$name, $slug, $data['icon'] ?? null, $data['h1'] ?? null, $data['description'] ?? null, $data['metaTitle'] ?? null, $data['metaDescription'] ?? null, $data['seoText'] ?? null, $data['parentId'] ?: null, $data['showInHeader'] ?? true ? 1 : 0, $data['showInFooter'] ?? true ? 1 : 0, $data['footerSection'] ?? 'products', $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0)]);
}
echo json_encode(['success' => true]);

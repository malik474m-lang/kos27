<?php
require_once __DIR__ . '/../../includes/audit-log.php';

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$slug = trim($data['slug'] ?? '');
if (!$name) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }
if (!$slug) $slug = slugify($name);

if ($id) {
    // Старые данные
    $oldStmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $oldStmt->execute([$id]);
    $oldData = $oldStmt->fetch();
    
    $db->prepare("UPDATE categories SET name=?, slug=?, icon=?, h1=?, description=?, meta_title=?, meta_description=?, seo_text=?, parent_id=?, show_in_header=?, show_in_footer=?, footer_section=?, field_templates=?, is_active=?, sort_order=? WHERE id=?")
       ->execute([$name, $slug, $data['icon'] ?? null, $data['h1'] ?? null, $data['description'] ?? null, $data['metaTitle'] ?? null, $data['metaDescription'] ?? null, $data['seoText'] ?? null, $data['parentId'] ?: null, $data['showInHeader'] ?? true ? 1 : 0, $data['showInFooter'] ?? true ? 1 : 0, $data['footerSection'] ?? 'products', $data['fieldTemplates'] ?? null, $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0), $id]);
    
    // Аудит
    $newData = ['name' => $name, 'is_active' => $data['isActive'] ?? true];
    $changes = $oldData ? auditDiff($oldData, $newData, ['name', 'is_active']) : null;
    $action = isset($changes['is_active']) ? ($newData['is_active'] ? 'enable' : 'disable') : 'update';
    auditLog($action, 'category', $id, $name, $changes);
} else {
    $exists = $db->prepare("SELECT id FROM categories WHERE slug = ?"); $exists->execute([$slug]);
    if ($exists->fetch()) { http_response_code(400); echo json_encode(['error' => "Slug '$slug' уже существует"]); exit; }
    $db->prepare("INSERT INTO categories (name, slug, icon, h1, description, meta_title, meta_description, seo_text, parent_id, show_in_header, show_in_footer, footer_section, field_templates, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$name, $slug, $data['icon'] ?? null, $data['h1'] ?? null, $data['description'] ?? null, $data['metaTitle'] ?? null, $data['metaDescription'] ?? null, $data['seoText'] ?? null, $data['parentId'] ?: null, $data['showInHeader'] ?? true ? 1 : 0, $data['showInFooter'] ?? true ? 1 : 0, $data['footerSection'] ?? 'products', $data['fieldTemplates'] ?? null, $data['isActive'] ?? true ? 1 : 0, (int)($data['sortOrder'] ?? 0)]);
    
    // Аудит
    auditLog('create', 'category', (int)$db->lastInsertId(), $name);
}
echo json_encode(['success' => true]);

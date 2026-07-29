<?php
require_once __DIR__ . '/../../includes/audit-log.php';

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

if ($action === 'list' && $method === 'GET') {
    $profiles = $db->query("SELECT * FROM postback_profiles ORDER BY created_at DESC")->fetchAll();
    echo json_encode($profiles);
    exit;
}

if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    $notes = trim($data['notes'] ?? '');
    if (!$name) { http_response_code(400); echo json_encode(['error' => 'Название обязательно']); exit; }
    if (!$slug) $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(strtr($name, ' ', '-')));
    $exists = $db->prepare("SELECT id FROM postback_profiles WHERE slug = ?"); $exists->execute([$slug]);
    if ($exists->fetch()) { http_response_code(400); echo json_encode(['error' => "Slug '$slug' уже существует"]); exit; }
    $db->prepare("INSERT INTO postback_profiles (name, slug, notes) VALUES (?,?,?)")->execute([$name, $slug, $notes]);
    $newId = $db->lastInsertId();
    
    // Аудит
    auditLog('create', 'postback_profile', (int)$newId, $name);
    
    echo json_encode(['success' => true, 'id' => $newId]);
    exit;
}

if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $notes = trim($data['notes'] ?? '');
    if ($id && $name) {
        $db->prepare("UPDATE postback_profiles SET name=?, notes=? WHERE id=?")->execute([$name, $notes, $id]);
        
        // Аудит
        auditLog('update', 'postback_profile', $id, $name);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id) {
        // Получаем название перед удалением
        $stmt = $db->prepare("SELECT name FROM postback_profiles WHERE id = ?");
        $stmt->execute([$id]);
        $profile = $stmt->fetch();
        
        $db->prepare("DELETE FROM postback_profiles WHERE id = ?")->execute([$id]);
        
        // Аудит
        auditLog('delete', 'postback_profile', $id, $profile['name'] ?? "ID $id");
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

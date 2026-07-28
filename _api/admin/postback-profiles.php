<?php
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
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
    exit;
}

if ($action === 'delete' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id) $db->prepare("DELETE FROM postback_profiles WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

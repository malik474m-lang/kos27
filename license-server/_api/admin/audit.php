<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * 100;
    
    $stmt = $db->query("SELECT al.*, a.username FROM audit_log al LEFT JOIN admins a ON al.admin_id = a.id ORDER BY al.created_at DESC LIMIT 100 OFFSET $offset");
    $logs = $stmt->fetchAll();
    
    echo json_encode(['logs' => $logs, 'page' => $page], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}

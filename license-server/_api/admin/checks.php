<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($page - 1) * 100;
    
    // LIMIT и OFFSET напрямую (безопасно, т.к. $offset уже int)
    $stmt = $db->query("SELECT * FROM license_checks ORDER BY created_at DESC LIMIT 100 OFFSET $offset");
    $checks = $stmt->fetchAll();
    
    echo json_encode(['checks' => $checks, 'page' => $page], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}

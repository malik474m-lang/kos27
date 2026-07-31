<?php
/**
 * Тест API — УДАЛИТЕ ПОСЛЕ ПРОВЕРКИ!
 */
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$test = $_GET['test'] ?? 'checks';

try {
    $db = getDB();
    
    if ($test === 'checks') {
        $stmt = $db->prepare("SELECT * FROM license_checks ORDER BY created_at DESC LIMIT 100");
        $stmt->execute();
        $data = ['checks' => $stmt->fetchAll(), 'test' => 'direct'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    if ($test === 'audit') {
        $stmt = $db->prepare("SELECT al.*, a.username FROM audit_log al LEFT JOIN admins a ON al.admin_id = a.id ORDER BY al.created_at DESC LIMIT 100");
        $stmt->execute();
        $data = ['logs' => $stmt->fetchAll(), 'test' => 'direct'];
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

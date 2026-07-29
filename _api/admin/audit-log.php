<?php
/**
 * API: История изменений (аудит-лог)
 * GET /api/admin/audit-log
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    
    // Проверяем что таблица существует
    try {
        $db->query("SELECT 1 FROM admin_audit_log LIMIT 1");
    } catch (Exception $e) {
        // Таблица не существует — создаём
        $db->exec("
            CREATE TABLE IF NOT EXISTS `admin_audit_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `admin_id` int(11) DEFAULT NULL,
              `admin_name` varchar(100) DEFAULT NULL,
              `action` varchar(50) NOT NULL,
              `entity` varchar(50) NOT NULL,
              `entity_id` int(11) DEFAULT NULL,
              `entity_name` varchar(255) DEFAULT NULL,
              `changes` text DEFAULT NULL,
              `ip` varchar(45) DEFAULT NULL,
              `user_agent` varchar(500) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_entity` (`entity`, `entity_id`),
              KEY `idx_action` (`action`),
              KEY `idx_admin` (`admin_id`),
              KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    
    // Параметры фильтрации
    $entity = $_GET['entity'] ?? '';
    $entityId = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 0;
    $action = $_GET['action'] ?? '';
    $adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Строим запрос
    $where = [];
    $params = [];
    
    if ($entity !== '') {
        $where[] = "entity = ?";
        $params[] = $entity;
    }
    if ($entityId > 0) {
        $where[] = "entity_id = ?";
        $params[] = $entityId;
    }
    if ($action !== '') {
        $where[] = "action = ?";
        $params[] = $action;
    }
    if ($adminId > 0) {
        $where[] = "admin_id = ?";
        $params[] = $adminId;
    }
    if ($dateFrom !== '') {
        $where[] = "created_at >= ?";
        $params[] = $dateFrom . ' 00:00:00';
    }
    if ($dateTo !== '') {
        $where[] = "created_at <= ?";
        $params[] = $dateTo . ' 23:59:59';
    }
    
    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Общее количество
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM admin_audit_log $whereStr");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['total'];
    
    // Записи — LIMIT/OFFSET вставляем напрямую (целые числа, безопасно)
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $stmt = $db->prepare("
        SELECT * FROM admin_audit_log 
        $whereStr 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Форматируем
    require_once __DIR__ . '/../../includes/audit-log.php';
    
    foreach ($logs as &$log) {
        $log['action_label'] = auditActionLabel($log['action']);
        $log['entity_label'] = auditEntityLabel($log['entity']);
        $log['action_icon'] = auditActionIcon($log['action']);
        if (!empty($log['changes'])) {
            $decoded = json_decode($log['changes'], true);
            $log['changes'] = $decoded ?: null;
        } else {
            $log['changes'] = null;
        }
    }
    unset($log);
    
    echo json_encode([
        'logs' => $logs,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'logs' => [],
        'total' => 0,
        'limit' => 50,
        'offset' => 0,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

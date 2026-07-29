<?php
/**
 * API: История изменений (аудит-лог)
 * GET /api/admin/audit-log
 * GET /api/admin/audit-log?entity=offer&entity_id=5
 */
header('Content-Type: application/json; charset=UTF-8');

$db = getDB();

// Параметры фильтрации
$entity = $_GET['entity'] ?? null;
$entityId = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
$action = $_GET['action'] ?? null;
$adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$limit = min((int)($_GET['limit'] ?? 100), 500);
$offset = (int)($_GET['offset'] ?? 0);

// Строим запрос
$where = [];
$params = [];

if ($entity) {
    $where[] = "entity = ?";
    $params[] = $entity;
}
if ($entityId) {
    $where[] = "entity_id = ?";
    $params[] = $entityId;
}
if ($action) {
    $where[] = "action = ?";
    $params[] = $action;
}
if ($adminId) {
    $where[] = "admin_id = ?";
    $params[] = $adminId;
}
if ($dateFrom) {
    $where[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}
if ($dateTo) {
    $where[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Получаем общее количество
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM admin_audit_log $whereStr");
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['total'];

// Получаем записи
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare("
    SELECT * FROM admin_audit_log 
    $whereStr 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Форматируем
require_once __DIR__ . '/../../includes/audit-log.php';

foreach ($logs as &$log) {
    $log['action_label'] = auditActionLabel($log['action']);
    $log['entity_label'] = auditEntityLabel($log['entity']);
    $log['action_icon'] = auditActionIcon($log['action']);
    $log['changes'] = $log['changes'] ? json_decode($log['changes'], true) : null;
}

echo json_encode([
    'logs' => $logs,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset
], JSON_UNESCAPED_UNICODE);

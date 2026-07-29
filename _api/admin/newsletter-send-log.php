<?php
/**
 * Лог отправки рассылок
 * GET /api/admin/newsletter-send-log?newsletter_id=5&limit=50
 */
require_once __DIR__ . '/../../includes/newsletter-helpers.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    ensureSendLogTable($db);
    
    $nlId = isset($_GET['newsletter_id']) ? (int)$_GET['newsletter_id'] : 0;
    $onlyFailed = !empty($_GET['failed']);
    $onlyTest = !empty($_GET['test']);
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $where = [];
    $params = [];
    
    if ($nlId > 0) {
        $where[] = "l.newsletter_id = ?";
        $params[] = $nlId;
    }
    if ($onlyFailed) {
        $where[] = "l.status = 'failed'";
    }
    if ($onlyTest) {
        $where[] = "l.is_test = 1";
    }
    
    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Итого
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM newsletter_send_log l $whereStr");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['total'];
    
    // Статистика
    $statsWhere = $nlId > 0 ? "WHERE newsletter_id = $nlId" : '';
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_sends,
            SUM(status = 'sent') as total_sent,
            SUM(status = 'failed') as total_failed,
            SUM(is_test = 1) as total_test,
            SUM(is_test = 0 AND status = 'sent') as real_sent,
            SUM(is_test = 0 AND status = 'failed') as real_failed
        FROM newsletter_send_log $statsWhere
    ")->fetch();
    
    // Записи
    $stmt = $db->prepare("
        SELECT l.*, n.subject as newsletter_subject
        FROM newsletter_send_log l
        LEFT JOIN newsletters n ON n.id = l.newsletter_id
        $whereStr
        ORDER BY l.sent_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    echo json_encode([
        'logs' => $logs,
        'stats' => $stats,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'logs' => [],
        'stats' => [],
        'total' => 0,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

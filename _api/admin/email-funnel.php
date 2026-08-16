<?php
/**
 * API для управления email-воронкой
 */
require_once __DIR__ . '/../../includes/email-funnel.php';

$action = $_GET['action'] ?? 'stats';

try {
    $db = getDB();
    ensureFunnelTables($db);
    seedDefaultFunnelSteps($db);

    switch ($action) {
        case 'stats':
            echo json_encode(getFunnelStats());
            break;

        case 'steps':
            echo json_encode($db->query("SELECT * FROM email_funnel_steps ORDER BY step_order ASC")->fetchAll());
            break;

        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if (!$id) { echo json_encode(['error' => 'ID обязателен']); exit; }
            $db->prepare("UPDATE email_funnel_steps SET name=?, subject=?, body_html=?, delay_hours=?, is_active=? WHERE id=?")
               ->execute([$data['name'], $data['subject'], $data['body_html'], (int)$data['delay_hours'], $data['is_active'] ? 1 : 0, $id]);
            echo json_encode(['success' => true]);
            break;

        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            $maxOrder = (int)$db->query("SELECT COALESCE(MAX(step_order),0) FROM email_funnel_steps")->fetchColumn();
            $db->prepare("INSERT INTO email_funnel_steps (name, subject, body_html, delay_hours, step_order, is_active) VALUES (?,?,?,?,?,1)")
               ->execute([$data['name'] ?? 'Новый шаг', $data['subject'] ?? '', $data['body_html'] ?? '', (int)($data['delay_hours'] ?? 24), $maxOrder + 1]);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;

        case 'delete':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id) {
                $db->prepare("DELETE FROM email_funnel_steps WHERE id = ?")->execute([$id]);
                $db->prepare("DELETE FROM email_funnel_log WHERE step_id = ?")->execute([$id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'toggle':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = (int)($data['id'] ?? 0);
            if ($id) {
                $db->prepare("UPDATE email_funnel_steps SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'run':
            $result = processFunnel(30);
            echo json_encode($result);
            break;

        case 'log':
            $rows = $db->query("SELECT l.*, s.name as step_name FROM email_funnel_log l LEFT JOIN email_funnel_steps s ON l.step_id = s.id ORDER BY l.sent_at DESC LIMIT 100")->fetchAll();
            echo json_encode($rows);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

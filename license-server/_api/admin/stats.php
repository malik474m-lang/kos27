<?php
try {
    $db = getDB();
    $s = [];
    $s['by_status'] = $db->query("SELECT status, COUNT(*) as cnt FROM licenses GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $s['by_plan'] = $db->query("SELECT p.name, COUNT(l.id) as cnt FROM plans p LEFT JOIN licenses l ON p.id = l.plan_id GROUP BY p.id ORDER BY p.sort_order")->fetchAll();
    $s['checks_24h'] = $db->query("SELECT status, COUNT(*) as cnt FROM license_checks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $s['expiring_soon'] = (int)$db->query("SELECT COUNT(*) FROM licenses WHERE expires_at IS NOT NULL AND expires_at > NOW() AND expires_at < DATE_ADD(NOW(), INTERVAL 7 DAY) AND status='active'")->fetchColumn();
    $s['total_licenses'] = (int)$db->query("SELECT COUNT(*) FROM licenses")->fetchColumn();
    $s['total_active'] = (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status='active'")->fetchColumn();
    jsonResponse(['stats' => $s]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

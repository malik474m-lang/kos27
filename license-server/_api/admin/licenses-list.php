<?php
try {
    $db = getDB();
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $where = []; $params = [];
    if ($status) { $where[] = "l.status = ?"; $params[] = $status; }
    if ($search) { $where[] = "(l.license_key LIKE ? OR l.domain LIKE ? OR l.owner_email LIKE ?)"; $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]); }
    $w = $where ? "WHERE " . implode(" AND ", $where) : "";
    $total = (int)$db->prepare("SELECT COUNT(*) FROM licenses l $w")->execute($params) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
    $cs = $db->prepare("SELECT COUNT(*) FROM licenses l $w"); $cs->execute($params); $total = (int)$cs->fetchColumn();
    $stmt = $db->prepare("SELECT l.*, p.name as plan_name FROM licenses l JOIN plans p ON l.plan_id = p.id $w ORDER BY l.created_at DESC LIMIT 50 OFFSET " . (($page-1)*50));
    $stmt->execute($params);
    jsonResponse(['licenses' => $stmt->fetchAll(), 'total' => $total, 'page' => $page]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

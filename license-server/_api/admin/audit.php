<?php
try {
    $db = getDB();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $stmt = $db->prepare("SELECT al.*, a.username FROM audit_log al LEFT JOIN admins a ON al.admin_id = a.id ORDER BY al.created_at DESC LIMIT 100 OFFSET ?");
    $stmt->execute([($page-1)*100]);
    jsonResponse(['logs' => $stmt->fetchAll(), 'page' => $page]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

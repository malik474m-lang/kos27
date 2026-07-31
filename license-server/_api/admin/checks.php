<?php
try {
    $db = getDB();
    $page = max(1, (int)($_GET['page'] ?? 1));
    $stmt = $db->prepare("SELECT * FROM license_checks ORDER BY created_at DESC LIMIT 100 OFFSET ?");
    $stmt->execute([($page-1)*100]);
    jsonResponse(['checks' => $stmt->fetchAll(), 'page' => $page]);
} catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }

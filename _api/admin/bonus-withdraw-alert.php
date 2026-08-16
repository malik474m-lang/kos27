<?php
require_once __DIR__ . '/../../includes/kosmobonus.php';
ensureKosmoBonusTables();
$db = getDB();
try {
    $pending = (int)$db->query("SELECT COUNT(*) FROM bonus_withdraw_requests WHERE status = 'pending'")->fetchColumn();
    echo json_encode(['success' => true, 'pending' => $pending]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'pending' => 0, 'error' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/../../includes/audit-log.php';

$db = getDB();

// Получаем название перед удалением
$stmt = $db->prepare("SELECT title FROM offers WHERE id = ?");
$stmt->execute([$itemId]);
$offer = $stmt->fetch();

$db->prepare("DELETE FROM offers WHERE id = ?")->execute([$itemId]);

// Аудит
auditLog('delete', 'offer', $itemId, $offer['title'] ?? "ID $itemId");

echo json_encode(['success' => true]);

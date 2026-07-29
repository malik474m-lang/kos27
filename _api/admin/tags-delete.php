<?php
require_once __DIR__ . '/../../includes/page-cache.php';
require_once __DIR__ . '/../../includes/audit-log.php';

$db = getDB();

// Получаем название перед удалением
$stmt = $db->prepare("SELECT title FROM offer_tags WHERE id = ?");
$stmt->execute([$itemId]);
$tag = $stmt->fetch();

$db->prepare("DELETE FROM offer_tags WHERE id = ?")->execute([$itemId]);
$db->prepare("DELETE FROM offer_tag_links WHERE tag_id = ?")->execute([$itemId]);

// Аудит
auditLog('delete', 'tag', $itemId, $tag['title'] ?? "ID $itemId");

@unlink(__DIR__ . '/../../data/tag-links-cache.json');
pageCacheClear();
echo json_encode(['success' => true]);

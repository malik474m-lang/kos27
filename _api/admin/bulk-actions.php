<?php
// Массовые действия
// POST /api/admin/bulk-actions
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../../includes/audit-log.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$entity = $data['entity'] ?? '';
$ids = $data['ids'] ?? [];

if (!$action || !$entity || !is_array($ids) || empty($ids)) {
    echo json_encode(['error' => 'Укажите action, entity и ids']);
    exit;
}

$db = getDB();
$count = 0;

try {
    switch ("$entity:$action") {

        // === ОФФЕРЫ ===
        case 'offers:enable':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE offers SET is_active = 1 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('enable', 'offer', null, "Массовое включение: $count шт.");
            break;

        case 'offers:disable':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE offers SET is_active = 0 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('disable', 'offer', null, "Массовое выключение: $count шт.");
            break;

        case 'offers:delete':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM offers WHERE id IN ($ph)")->execute($ids);
            try { $db->prepare("DELETE FROM offer_tag_links WHERE offer_id IN ($ph)")->execute($ids); } catch (Exception $e) {}
            $count = count($ids);
            auditLog('delete', 'offer', null, "Массовое удаление: $count шт.");
            break;

        case 'offers:assign-tags':
            $tagIds = $data['tagIds'] ?? [];
            if (empty($tagIds)) { echo json_encode(['error' => 'Укажите tagIds']); exit; }
            $stmt = $db->prepare("INSERT IGNORE INTO offer_tag_links (offer_id, tag_id) VALUES (?, ?)");
            foreach ($ids as $offerId) {
                foreach ($tagIds as $tagId) {
                    $stmt->execute([(int)$offerId, (int)$tagId]);
                    $count++;
                }
            }
            auditLog('update', 'offer', null, "Массовое назначение тегов: " . count($ids) . " офферов × " . count($tagIds) . " тегов");
            break;

        case 'offers:remove-tags':
            $tagIds = $data['tagIds'] ?? [];
            if (empty($tagIds)) { echo json_encode(['error' => 'Укажите tagIds']); exit; }
            $offPh = implode(',', array_fill(0, count($ids), '?'));
            $tagPh = implode(',', array_fill(0, count($tagIds), '?'));
            $db->prepare("DELETE FROM offer_tag_links WHERE offer_id IN ($offPh) AND tag_id IN ($tagPh)")
               ->execute(array_merge($ids, $tagIds));
            $count = count($ids);
            auditLog('update', 'offer', null, "Массовое снятие тегов: $count офферов");
            break;

        // === ТЕГИ ===
        case 'tags:enable':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE offer_tags SET is_active = 1 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('enable', 'tag', null, "Массовое включение: $count шт.");
            break;

        case 'tags:disable':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE offer_tags SET is_active = 0 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('disable', 'tag', null, "Массовое выключение: $count шт.");
            break;

        case 'tags:delete':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM offer_tags WHERE id IN ($ph)")->execute($ids);
            try { $db->prepare("DELETE FROM offer_tag_links WHERE tag_id IN ($ph)")->execute($ids); } catch (Exception $e) {}
            $count = count($ids);
            auditLog('delete', 'tag', null, "Массовое удаление: $count шт.");
            break;

        // === СТАТЬИ ===
        case 'articles:publish':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE articles SET is_published = 1 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('enable', 'article', null, "Массовая публикация: $count шт.");
            break;

        case 'articles:unpublish':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE articles SET is_published = 0 WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('disable', 'article', null, "Массовое снятие: $count шт.");
            break;

        case 'articles:delete':
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("DELETE FROM articles WHERE id IN ($ph)")->execute($ids);
            $count = count($ids);
            auditLog('delete', 'article', null, "Массовое удаление: $count шт.");
            break;

        // === ГЕНЕРАЦИЯ META ===
        case 'offers:generate-meta':
        case 'tags:generate-meta':
        case 'articles:generate-meta':
        case 'categories:generate-meta':
            // Перенаправляем в batch-generate
            $entityMap = ['offers'=>'offers','tags'=>'tags','articles'=>'articles','categories'=>'categories'];
            $batchEntity = $entityMap[$entity] ?? $entity;
            $batchData = [
                'entity' => $batchEntity,
                'ids' => $ids,
                'fields' => $data['fields'] ?? ['meta_title', 'meta_description'],
                'overwrite' => $data['overwrite'] ?? false,
            ];
            $_POST = $batchData;
            // Эмулируем вызов batch-generate
            $GLOBALS['_bulk_batch_input'] = json_encode($batchData);
            // Переопределяем php://input через буфер
            require __DIR__ . '/batch-generate-inline.php';
            exit;

        default:
            echo json_encode(['error' => "Неизвестное действие: $entity:$action"]);
            exit;
    }

    // Сброс кэша
    try {
        require_once __DIR__ . '/../../includes/page-cache.php';
        pageCacheClear();
    } catch (Exception $e) {}
    try {
        require_once __DIR__ . '/../../includes/api-cache.php';
        apiCacheClear();
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'count' => $count]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

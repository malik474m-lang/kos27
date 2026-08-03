<?php
require_once __DIR__ . '/../../includes/content-quality.php';
try { $db = getDB(); $db->query("SELECT content_status FROM articles LIMIT 1"); } catch (Exception $e) { try { $db = getDB(); $db->exec("ALTER TABLE articles ADD COLUMN content_status varchar(20) NOT NULL DEFAULT 'draft' AFTER is_published, ADD COLUMN quality_score int(11) NOT NULL DEFAULT 0 AFTER content_status"); } catch (Exception $e2) {} }
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$status = $data['contentStatus'] ?? cq_recommend_status((int)($data['qualityScore'] ?? 0));
$qualityScore = (int)($data['qualityScore'] ?? 0);

$db->prepare("UPDATE articles SET title=?, excerpt=?, content=?, meta_title=?, meta_description=?, cover_image=?, is_published=?, content_status=?, quality_score=? WHERE id=?")
->execute([
    $data['title'] ?? '', $data['excerpt'] ?? '', $data['content'] ?? '',
    $data['metaTitle'] ?? '', $data['metaDescription'] ?? '',
    $data['coverImage'] ?? '', $data['isPublished'] ?? false, $status, $qualityScore, $itemId,
]);

try { require_once __DIR__ . '/../../includes/auto-indexing.php';
    $slugRow = $db->prepare('SELECT slug FROM articles WHERE id = ?'); $slugRow->execute([$itemId]); $sl = $slugRow->fetchColumn();
    if ($sl) autoSubmitUrl('/articles/' . $sl);
} catch (Exception $e) {}
echo json_encode(['success' => true]);

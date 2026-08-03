<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$db->prepare("UPDATE articles SET title=?, excerpt=?, content=?, meta_title=?, meta_description=?, cover_image=?, is_published=? WHERE id=?")
->execute([
    $data['title'] ?? '', $data['excerpt'] ?? '', $data['content'] ?? '',
    $data['metaTitle'] ?? '', $data['metaDescription'] ?? '',
    $data['coverImage'] ?? '', $data['isPublished'] ?? false, $itemId,
]);

try { require_once __DIR__ . '/../../includes/auto-indexing.php';
    $slugRow = $db->prepare('SELECT slug FROM articles WHERE id = ?'); $slugRow->execute([$itemId]); $sl = $slugRow->fetchColumn();
    if ($sl) autoSubmitUrl('/articles/' . $sl);
} catch (Exception $e) {}
echo json_encode(['success' => true]);

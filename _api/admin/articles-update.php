<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$db->prepare("UPDATE articles SET title=?, excerpt=?, content=?, meta_title=?, meta_description=?, cover_image=?, is_published=? WHERE id=?")
->execute([
    $data['title'] ?? '', $data['excerpt'] ?? '', $data['content'] ?? '',
    $data['metaTitle'] ?? '', $data['metaDescription'] ?? '',
    $data['coverImage'] ?? '', $data['isPublished'] ?? false, $itemId,
]);

echo json_encode(['success' => true]);

<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$slug = slugify($data['title'] ?? 'article') . '-' . time();

$db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, cover_image, is_published) VALUES (?,?,?,?,?,?,?,?)")
->execute([
    $data['title'] ?? '', $slug, $data['excerpt'] ?? '',
    $data['content'] ?? '', $data['metaTitle'] ?? '',
    $data['metaDescription'] ?? '', $data['coverImage'] ?? '',
    $data['isPublished'] ?? false,
]);

require_once __DIR__ . '/../../includes/auto-indexing.php';
autoSubmitUrl('/articles/' . $slug);
echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

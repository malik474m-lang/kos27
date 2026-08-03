<?php
require_once __DIR__ . '/../../includes/content-quality.php';
try { $db = getDB(); $db->query("SELECT content_status FROM articles LIMIT 1"); } catch (Exception $e) { try { $db = getDB(); $db->exec("ALTER TABLE articles ADD COLUMN content_status varchar(20) NOT NULL DEFAULT 'draft' AFTER is_published, ADD COLUMN quality_score int(11) NOT NULL DEFAULT 0 AFTER content_status"); } catch (Exception $e2) {} }
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$slug = slugify($data['title'] ?? 'article') . '-' . time();
$status = $data['contentStatus'] ?? cq_recommend_status((int)($data['qualityScore'] ?? 0));
$qualityScore = (int)($data['qualityScore'] ?? 0);

$db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, cover_image, is_published, content_status, quality_score) VALUES (?,?,?,?,?,?,?,?,?,?)")
->execute([
    $data['title'] ?? '', $slug, $data['excerpt'] ?? '',
    $data['content'] ?? '', $data['metaTitle'] ?? '',
    $data['metaDescription'] ?? '', $data['coverImage'] ?? '',
    $data['isPublished'] ?? false, $status, $qualityScore,
]);

require_once __DIR__ . '/../../includes/auto-indexing.php';
autoSubmitUrl('/articles/' . $slug);
echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

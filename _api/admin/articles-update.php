<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
require_once __DIR__ . '/../../includes/content-quality.php';

// Авто-миграция для content_status
try { $db = getDB(); $db->query("SELECT content_status FROM articles LIMIT 1"); } catch (Exception $e) { try { $db = getDB(); $db->exec("ALTER TABLE articles ADD COLUMN content_status varchar(20) NOT NULL DEFAULT 'draft' AFTER is_published, ADD COLUMN quality_score int(11) NOT NULL DEFAULT 0 AFTER content_status"); } catch (Exception $e2) {} }

// Авто-миграция для E-E-A-T полей
try { $db = getDB(); $db->query("SELECT author_name FROM articles LIMIT 1"); } catch (Exception $e) {
    try {
        $db = getDB();
        $db->exec("ALTER TABLE articles 
            ADD COLUMN author_name VARCHAR(255) DEFAULT 'Редакция Космозайм' AFTER cover_image,
            ADD COLUMN author_title VARCHAR(255) DEFAULT 'Финансовый редактор' AFTER author_name,
            ADD COLUMN reviewer_name VARCHAR(255) DEFAULT NULL AFTER author_title,
            ADD COLUMN reviewer_title VARCHAR(255) DEFAULT NULL AFTER reviewer_name,
            ADD COLUMN fact_checked_at TIMESTAMP NULL DEFAULT NULL AFTER reviewer_title,
            ADD COLUMN sources TEXT DEFAULT NULL AFTER fact_checked_at");
    } catch (Exception $e2) {}
}

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$status = $data['contentStatus'] ?? cq_recommend_status((int)($data['qualityScore'] ?? 0));
$qualityScore = (int)($data['qualityScore'] ?? 0);
$cleanExcerpt = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)($data['excerpt'] ?? ''))));
$cleanMetaTitle = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)($data['metaTitle'] ?? ''))));
$cleanMetaDescription = trim((string)preg_replace('/\s+/u', ' ', strip_tags((string)($data['metaDescription'] ?? ''))));

// E-E-A-T поля
$authorName = trim($data['authorName'] ?? '') ?: 'Редакция Космозайм';
$authorTitle = trim($data['authorTitle'] ?? '') ?: 'Финансовый редактор';
$reviewerName = trim($data['reviewerName'] ?? '') ?: null;
$reviewerTitle = trim($data['reviewerTitle'] ?? '') ?: null;
$factCheckedAt = !empty($data['factCheckedAt']) ? $data['factCheckedAt'] : date('Y-m-d H:i:s');
$sources = !empty($data['sources']) ? json_encode($data['sources']) : null;

$db->prepare("UPDATE articles SET title=?, excerpt=?, content=?, meta_title=?, meta_description=?, cover_image=?, is_published=?, content_status=?, quality_score=?, author_name=?, author_title=?, reviewer_name=?, reviewer_title=?, fact_checked_at=?, sources=? WHERE id=?")
->execute([
    $data['title'] ?? '', $cleanExcerpt, $data['content'] ?? '',
    $cleanMetaTitle, $cleanMetaDescription,
    $data['coverImage'] ?? '', $data['isPublished'] ?? false, $status, $qualityScore,
    $authorName, $authorTitle, $reviewerName, $reviewerTitle, $factCheckedAt, $sources,
    $itemId,
]);

try { require_once __DIR__ . '/../../includes/auto-indexing.php';
    $slugRow = $db->prepare('SELECT slug FROM articles WHERE id = ?'); $slugRow->execute([$itemId]); $sl = $slugRow->fetchColumn();
    if ($sl) autoSubmitUrl('/articles/' . $sl);
} catch (Exception $e) {}
echo json_encode(['success' => true]);

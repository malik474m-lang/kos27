<?php
if (apiCacheStart('public_articles', 120)) exit;

$db = getDB();

// Одна статья по slug
$slug = $_GET['slug'] ?? '';
if ($slug) {
    $stmt = $db->prepare("SELECT id, title, slug, excerpt, content, cover_image, meta_title, meta_description, created_at FROM articles WHERE slug = ? AND is_published = 1 LIMIT 1");
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
    apiCacheEnd($article ?: null);
    exit;
}

// Список статей (с content для мобильного)
$stmt = $db->query("SELECT id, title, slug, excerpt, content, cover_image, created_at FROM articles WHERE is_published = 1 ORDER BY created_at DESC");
apiCacheEnd($stmt->fetchAll());

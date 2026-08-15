<?php
/**
 * НОВЫЙ API ENDPOINT для мобильного приложения
 * 
 * Файл: /api/articles (добавить в _api/router.php)
 * 
 * Этот файл нужно разместить в: ~/domains/kosmozaim.ru/_api/articles.php
 * И добавить маршрут в _api/router.php:
 * 
 *   if ($apiUri === '/articles') { require __DIR__ . '/articles.php'; exit; }
 */

if (apiCacheStart('public_articles', 120)) exit;

$db = getDB();
$stmt = $db->query("SELECT id, title, slug, excerpt, content, meta_title, meta_description, cover_image, created_at, updated_at FROM articles WHERE is_published = 1 ORDER BY created_at DESC");
$articles = $stmt->fetchAll();

apiCacheEnd($articles);

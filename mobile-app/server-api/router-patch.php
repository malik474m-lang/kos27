<?php
/**
 * ПАТЧ для _api/router.php
 * 
 * Добавить эти строки ПОСЛЕ строки:
 *   if ($apiUri === '/health') { echo json_encode(['ok' => true]); exit; }
 * 
 * И ПЕРЕД строкой:
 *   if ($apiUri === '/subscribe' && ...
 */

// === CORS для мобильного приложения ===
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// === Новые публичные API для мобильного приложения ===

// Список статей
if ($apiUri === '/articles') { require __DIR__ . '/articles.php'; exit; }

// Одна статья по slug
if (preg_match('#^/articles/([a-z0-9-]+)$#', $apiUri, $m)) {
    $articleSlug = $m[1];
    require __DIR__ . '/article-detail.php';
    exit;
}

// FAQ
if ($apiUri === '/faq') { require __DIR__ . '/faq.php'; exit; }

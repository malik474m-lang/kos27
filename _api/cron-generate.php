<?php
/**
 * Крон-эндпоинт для автогенерации контента
 * POST /api/cron-generate?type=review&secret=...
 * POST /api/cron-generate?type=article&secret=...
 * 
 * Вызывается из auto-scheduler.php без авторизации,
 * проверяется по CRON_SECRET
 */
header('Content-Type: application/json; charset=UTF-8');

$secret = $_GET['secret'] ?? $_POST['secret'] ?? '';
if ($secret !== CRON_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid secret']);
    exit;
}

$type = $_GET['type'] ?? '';

if ($type === 'review') {
    // Подключаем генерацию отзыва напрямую
    require __DIR__ . '/admin/generate-review.php';
    exit;
}

if ($type === 'article') {
    require __DIR__ . '/admin/generate-article.php';
    exit;
}

echo json_encode(['error' => 'Unknown type. Use: review, article']);

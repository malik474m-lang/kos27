<?php
/**
 * Крон-эндпоинт для автогенерации контента
 * GET /api/cron-generate?type=review&secret=...
 * GET /api/cron-generate?type=article&secret=...
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
    ob_start();
    try {
        require __DIR__ . '/../cron/review-cron.php';
    } catch (Throwable $e) {
        ob_end_clean();
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    $output = ob_get_clean();
    echo json_encode(['success' => true, 'type' => 'review', 'output' => trim($output)]);
    exit;
}

if ($type === 'article') {
    ob_start();
    try {
        require __DIR__ . '/../cron/article-cron.php';
    } catch (Throwable $e) {
        ob_end_clean();
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    $output = ob_get_clean();
    echo json_encode(['success' => true, 'type' => 'article', 'output' => trim($output)]);
    exit;
}

echo json_encode(['error' => 'Unknown type. Use: review, article']);

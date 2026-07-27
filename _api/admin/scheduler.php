<?php
require_once __DIR__ . '/../../includes/auto-scheduler.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — получить настройки и статистику
if ($method === 'GET') {
    echo json_encode([
        'settings' => getSchedulerSettings(),
        'stats' => getSchedulerStats(),
    ]);
    exit;
}

// POST — сохранить настройки
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $settings = [
        'reviews_enabled' => (bool)($data['reviews_enabled'] ?? true),
        'reviews_per_day' => max(0, min(50, (int)($data['reviews_per_day'] ?? 5))),
        'review_start_hour' => max(0, min(23, (int)($data['review_start_hour'] ?? 6))),
        'review_end_hour' => max(0, min(23, (int)($data['review_end_hour'] ?? 22))),
        'articles_enabled' => (bool)($data['articles_enabled'] ?? true),
        'articles_per_day' => max(0, min(10, (int)($data['articles_per_day'] ?? 1))),
        'article_start_hour' => max(0, min(23, (int)($data['article_start_hour'] ?? 8))),
        'article_end_hour' => max(0, min(23, (int)($data['article_end_hour'] ?? 20))),
    ];
    
    if (saveSchedulerSettings($settings)) {
        echo json_encode(['success' => true, 'settings' => $settings]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить настройки']);
    }
    exit;
}

// DELETE — сбросить счётчики на сегодня
if ($method === 'DELETE') {
    $dataDir = __DIR__ . '/../../data';
    date_default_timezone_set('Europe/Moscow');
    $today = date('Y-m-d');
    
    @unlink("$dataDir/review_count_{$today}.txt");
    @unlink("$dataDir/article_count_{$today}.txt");
    @unlink("$dataDir/last_review_time.txt");
    @unlink("$dataDir/last_article_time.txt");
    
    echo json_encode(['success' => true, 'message' => 'Счётчики сброшены']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

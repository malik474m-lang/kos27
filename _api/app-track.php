<?php
/**
 * API трекинга действий пользователей мобильного приложения
 * POST /api/app-track
 * 
 * События: app_open, page_view, offer_click, offer_apply, article_view, 
 *          calculator_use, favorite_add, review_submit, search
 */
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo '{}'; exit; }

$data = json_decode(file_get_contents('php://input'), true);
$event = $data['event'] ?? '';
$validEvents = ['app_open','page_view','offer_click','offer_apply','article_view','calculator_use','favorite_add','review_submit','search','category_view','faq_view','giveaway_view'];

if (!in_array($event, $validEvents)) { echo json_encode(['ok'=>true]); exit; }

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$platform = preg_match('/iPhone|iPad|iPod/i', $ua) ? 'ios' : (preg_match('/Android/i', $ua) ? 'android' : 'desktop');
$device = '';
if (preg_match('/iPhone/', $ua)) $device = 'iPhone';
elseif (preg_match('/iPad/', $ua)) $device = 'iPad';
elseif (preg_match('/; ([^;)]+) Build/i', $ua, $m)) $device = trim($m[1]);
$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        platform VARCHAR(20),
        device_model VARCHAR(100),
        screen_name VARCHAR(100),
        offer_id INT DEFAULT NULL,
        offer_title VARCHAR(200),
        article_id INT DEFAULT NULL,
        article_title VARCHAR(200),
        category VARCHAR(50),
        search_query VARCHAR(200),
        extra_data TEXT,
        ip VARCHAR(45),
        user_agent TEXT,
        session_id VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_event (event_type),
        KEY idx_date (created_at),
        KEY idx_session (session_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->prepare("INSERT INTO app_events (event_type, platform, device_model, screen_name, offer_id, offer_title, article_id, article_title, category, search_query, extra_data, ip, user_agent, session_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $event, $platform, $device ?: null,
           $data['screen'] ?? null,
           $data['offerId'] ?? null, $data['offerTitle'] ?? null,
           $data['articleId'] ?? null, $data['articleTitle'] ?? null,
           $data['category'] ?? null,
           $data['query'] ?? null,
           isset($data['extra']) ? json_encode($data['extra']) : null,
           $ip, $ua, $data['sessionId'] ?? null
       ]);
} catch (Exception $e) {}
echo json_encode(['ok'=>true]);

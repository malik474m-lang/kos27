<?php
// Трекинг-пиксель открытия письма: /api/nl-open?n=ID&s=SUB_ID
$nlId = (int)($_GET['n'] ?? 0);
$subId = (int)($_GET['s'] ?? 0);

if ($nlId && $subId) {
    try {
        $db = getDB();
        // Не дублируем открытия от одного подписчика
        $exists = $db->prepare("SELECT id FROM newsletter_events WHERE newsletter_id = ? AND subscriber_id = ? AND event_type = 'open' LIMIT 1");
        $exists->execute([$nlId, $subId]);
        if (!$exists->fetch()) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $ip = trim(explode(',', $ip)[0]);
            $db->prepare("INSERT INTO newsletter_events (newsletter_id, subscriber_id, event_type, ip) VALUES (?, ?, 'open', ?)")
               ->execute([$nlId, $subId, $ip]);
        }
    } catch (Exception $e) {}
}

// Отдаём прозрачный 1x1 пиксель
header('Content-Type: image/gif');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

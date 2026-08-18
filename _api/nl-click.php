<?php
// Трекинг клика по ссылке из письма: /api/nl-click?n=ID&s=SUB_ID&url=ENCODED_URL
$nlId = (int)($_GET['n'] ?? 0);
$subId = (int)($_GET['s'] ?? 0);
$url = $_GET['url'] ?? '/';

if ($nlId && $subId && $url) {
    try {
        $db = getDB();
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip = trim(explode(',', $ip)[0]);
        $db->prepare("INSERT INTO newsletter_events (newsletter_id, subscriber_id, event_type, url, ip) VALUES (?, ?, 'click', ?, ?)")
           ->execute([$nlId, $subId, $url, $ip]);
    } catch (Exception $e) {}
}

// Редирект на целевой URL
if (!$url || $url === '/') $url = SITE_URL;
if (!str_starts_with((string)($url), 'http')) $url = SITE_URL . $url;
header('Location: ' . $url, true, 302);
exit;

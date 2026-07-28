<?php
if (apiCacheStart('admin_newsletter_stats', 20)) exit;

$db = getDB();
$nlId = (int)($m[1] ?? $_GET['id'] ?? 0);

$opens = $db->prepare("SELECT COUNT(DISTINCT subscriber_id) as cnt FROM newsletter_events WHERE newsletter_id = ? AND event_type = 'open'");
$opens->execute([$nlId]);
$openCount = (int)$opens->fetch()['cnt'];

$clicks = $db->prepare("SELECT COUNT(*) as cnt FROM newsletter_events WHERE newsletter_id = ? AND event_type = 'click'");
$clicks->execute([$nlId]);
$clickCount = (int)$clicks->fetch()['cnt'];

$uniqueClicks = $db->prepare("SELECT COUNT(DISTINCT subscriber_id) as cnt FROM newsletter_events WHERE newsletter_id = ? AND event_type = 'click'");
$uniqueClicks->execute([$nlId]);
$uniqueClickCount = (int)$uniqueClicks->fetch()['cnt'];

$topLinks = $db->prepare("SELECT url, COUNT(*) as cnt FROM newsletter_events WHERE newsletter_id = ? AND event_type = 'click' AND url IS NOT NULL GROUP BY url ORDER BY cnt DESC LIMIT 10");
$topLinks->execute([$nlId]);
$topLinksData = $topLinks->fetchAll();

$nl = $db->prepare("SELECT sent_count FROM newsletters WHERE id = ?");
$nl->execute([$nlId]);
$sentCount = (int)($nl->fetch()['sent_count'] ?? 0);

$newsletterStatsPayload = [
    'opens' => $openCount,
    'clicks' => $clickCount,
    'uniqueClicks' => $uniqueClickCount,
    'sentCount' => $sentCount,
    'openRate' => $sentCount > 0 ? round(($openCount / $sentCount) * 100, 1) : 0,
    'clickRate' => $sentCount > 0 ? round(($uniqueClickCount / $sentCount) * 100, 1) : 0,
    'topLinks' => $topLinksData,
];

apiCacheEnd($newsletterStatsPayload);

<?php
$db = getDB();
$offers = $db->query("SELECT COUNT(*) as cnt FROM offers WHERE is_active = 1")->fetch()['cnt'];
$articles = $db->query("SELECT COUNT(*) as cnt FROM articles WHERE is_published = 1")->fetch()['cnt'];
$reviews = $db->query("SELECT COUNT(*) as cnt FROM reviews")->fetch()['cnt'];
$subscribers = $db->query("SELECT COUNT(*) as cnt FROM subscribers WHERE is_active = 1")->fetch()['cnt'];
$clicksToday = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE clicked_at >= CURDATE()")->fetch()['cnt'];
$clicksWeek = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch()['cnt'];
$clicksMonth = $db->query("SELECT COUNT(*) as cnt FROM click_stats WHERE clicked_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetch()['cnt'];

$topOffers = $db->query("SELECT o.title, COUNT(c.id) as clicks FROM click_stats c JOIN offers o ON c.offer_id = o.id WHERE c.clicked_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY o.id, o.title ORDER BY clicks DESC LIMIT 10")->fetchAll();

echo json_encode([
    'offers' => (int)$offers,
    'articles' => (int)$articles,
    'reviews' => (int)$reviews,
    'subscribers' => (int)$subscribers,
    'clicksToday' => (int)$clicksToday,
    'clicksWeek' => (int)$clicksWeek,
    'clicksMonth' => (int)$clicksMonth,
    'topOffers' => $topOffers,
]);

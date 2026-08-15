<?php
requireAdmin();
$db = getDB();
$period = (int)($_GET['period'] ?? 30);
$dateFrom = date('Y-m-d', strtotime("-{$period} days"));

$result = ['downloads'=>[], 'events'=>[], 'screens'=>[], 'offers'=>[], 'devices'=>[], 'daily'=>[], 'total'=>['downloads'=>0,'opens'=>0,'offer_clicks'=>0,'applies'=>0,'unique_devices'=>0]];

// Скачивания APK
try {
    $db->query("SELECT 1 FROM app_downloads LIMIT 1");
    $dl = $db->prepare("SELECT COUNT(*) as cnt FROM app_downloads WHERE created_at >= ?"); $dl->execute([$dateFrom]);
    $result['total']['downloads'] = (int)$dl->fetch()['cnt'];
    $dlDaily = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as cnt FROM app_downloads WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY date"); $dlDaily->execute([$dateFrom]);
    $result['downloads'] = $dlDaily->fetchAll();
} catch (Exception $e) {}

// События приложения
try {
    $db->query("SELECT 1 FROM app_events LIMIT 1");

    // Общие метрики
    $t = $db->prepare("SELECT 
        SUM(event_type='app_open') as opens,
        SUM(event_type='offer_click') as offer_clicks,
        SUM(event_type='offer_apply') as applies,
        SUM(event_type='page_view') as page_views,
        SUM(event_type='article_view') as article_views,
        SUM(event_type='calculator_use') as calc_uses,
        SUM(event_type='favorite_add') as favorites,
        COUNT(DISTINCT CONCAT(ip, device_model)) as unique_devices
    FROM app_events WHERE created_at >= ?");
    $t->execute([$dateFrom]); $totals = $t->fetch();
    $result['total'] = array_merge($result['total'], [
        'opens'=>(int)($totals['opens']??0), 'offer_clicks'=>(int)($totals['offer_clicks']??0),
        'applies'=>(int)($totals['applies']??0), 'page_views'=>(int)($totals['page_views']??0),
        'article_views'=>(int)($totals['article_views']??0), 'calc_uses'=>(int)($totals['calc_uses']??0),
        'favorites'=>(int)($totals['favorites']??0), 'unique_devices'=>(int)($totals['unique_devices']??0)
    ]);

    // По экранам
    $s = $db->prepare("SELECT screen_name, COUNT(*) as cnt FROM app_events WHERE created_at >= ? AND screen_name IS NOT NULL GROUP BY screen_name ORDER BY cnt DESC LIMIT 15");
    $s->execute([$dateFrom]); $result['screens'] = $s->fetchAll();

    // Популярные офферы
    $o = $db->prepare("SELECT offer_title, offer_id, SUM(event_type='offer_click') as clicks, SUM(event_type='offer_apply') as applies FROM app_events WHERE created_at >= ? AND offer_id IS NOT NULL GROUP BY offer_id, offer_title ORDER BY clicks DESC LIMIT 15");
    $o->execute([$dateFrom]); $result['offers'] = $o->fetchAll();

    // Устройства
    $d = $db->prepare("SELECT device_model, platform, COUNT(DISTINCT CONCAT(ip, device_model)) as users, COUNT(*) as events FROM app_events WHERE created_at >= ? AND device_model IS NOT NULL AND device_model != '' GROUP BY device_model, platform ORDER BY users DESC LIMIT 15");
    $d->execute([$dateFrom]); $result['devices'] = $d->fetchAll();

    // По дням
    $dy = $db->prepare("SELECT DATE(created_at) as date, SUM(event_type='app_open') as opens, SUM(event_type='offer_click') as clicks, SUM(event_type='offer_apply') as applies, COUNT(DISTINCT CONCAT(ip,device_model)) as users FROM app_events WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
    $dy->execute([$dateFrom]); $result['daily'] = $dy->fetchAll();

    // Последние действия
    $r = $db->prepare("SELECT event_type, screen_name, offer_title, device_model, platform, created_at FROM app_events WHERE created_at >= ? ORDER BY created_at DESC LIMIT 20");
    $r->execute([$dateFrom]); $result['recent'] = $r->fetchAll();

} catch (Exception $e) { $result['message'] = 'Таблица событий создастся при первом использовании приложения'; }

echo json_encode($result, JSON_UNESCAPED_UNICODE);

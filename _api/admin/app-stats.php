<?php
requireAdmin();
$db = getDB();
$period = (int)($_GET['period'] ?? 30);
$dateFrom = date('Y-m-d', strtotime("-{$period} days"));

$result = [
    'downloads'=>[], 'events'=>[], 'screens'=>[], 'offers'=>[], 
    'devices'=>[], 'daily'=>[], 'recent'=>[],
    'total'=>['downloads'=>0,'opens'=>0,'offer_clicks'=>0,'applies'=>0,
              'unique_devices'=>0,'page_views'=>0,'article_views'=>0,
              'calc_uses'=>0,'favorites'=>0]
];

// Генерируем массив всех дат периода для непрерывного графика
$allDates = [];
for ($i = $period; $i >= 0; $i--) {
    $allDates[date('Y-m-d', strtotime("-{$i} days"))] = [
        'date' => date('Y-m-d', strtotime("-{$i} days")),
        'opens' => 0, 'clicks' => 0, 'applies' => 0, 'users' => 0, 'downloads' => 0
    ];
}

// Скачивания APK
$dlByDate = [];
try {
    $db->query("SELECT 1 FROM app_downloads LIMIT 1");
    $dl = $db->prepare("SELECT COUNT(*) as cnt FROM app_downloads WHERE created_at >= ?");
    $dl->execute([$dateFrom]);
    $result['total']['downloads'] = (int)$dl->fetch()['cnt'];

    $dlDaily = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as cnt FROM app_downloads WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
    $dlDaily->execute([$dateFrom]);
    $result['downloads'] = $dlDaily->fetchAll();
    foreach ($result['downloads'] as $row) {
        $dlByDate[$row['date']] = (int)$row['cnt'];
    }
} catch (Exception $e) {}

// Заполняем скачивания в allDates
foreach ($dlByDate as $date => $cnt) {
    if (isset($allDates[$date])) {
        $allDates[$date]['downloads'] = $cnt;
    }
}

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
        COUNT(DISTINCT COALESCE(NULLIF(device_model,''), ip)) as unique_devices
    FROM app_events WHERE created_at >= ?");
    $t->execute([$dateFrom]); $totals = $t->fetch();
    $result['total'] = array_merge($result['total'], [
        'opens'=>(int)($totals['opens']??0), 
        'offer_clicks'=>(int)($totals['offer_clicks']??0),
        'applies'=>(int)($totals['applies']??0), 
        'page_views'=>(int)($totals['page_views']??0),
        'article_views'=>(int)($totals['article_views']??0), 
        'calc_uses'=>(int)($totals['calc_uses']??0),
        'favorites'=>(int)($totals['favorites']??0), 
        'unique_devices'=>(int)($totals['unique_devices']??0)
    ]);

    // По экранам
    $s = $db->prepare("SELECT screen_name, COUNT(*) as cnt FROM app_events WHERE created_at >= ? AND screen_name IS NOT NULL AND screen_name != '' GROUP BY screen_name ORDER BY cnt DESC LIMIT 15");
    $s->execute([$dateFrom]); $result['screens'] = $s->fetchAll();

    // Популярные офферы
    $o = $db->prepare("SELECT offer_title, offer_id, SUM(event_type='offer_click') as clicks, SUM(event_type='offer_apply') as applies FROM app_events WHERE created_at >= ? AND offer_id IS NOT NULL GROUP BY offer_id, offer_title ORDER BY clicks DESC LIMIT 15");
    $o->execute([$dateFrom]); $result['offers'] = $o->fetchAll();

    // Устройства — показываем по IP если модель пустая
    $d = $db->prepare("SELECT 
        CASE WHEN device_model IS NOT NULL AND device_model != '' THEN device_model ELSE CONCAT('Устройство (', SUBSTRING(ip, 1, 8), '...)') END as device_model,
        COALESCE(platform, 'unknown') as platform, 
        COUNT(DISTINCT COALESCE(NULLIF(device_model,''), ip)) as users, 
        COUNT(*) as events 
    FROM app_events WHERE created_at >= ? 
    GROUP BY 
        CASE WHEN device_model IS NOT NULL AND device_model != '' THEN device_model ELSE CONCAT('Устройство (', SUBSTRING(ip, 1, 8), '...)') END,
        COALESCE(platform, 'unknown')
    ORDER BY users DESC LIMIT 15");
    $d->execute([$dateFrom]); $result['devices'] = $d->fetchAll();

    // По дням — для графика
    $dy = $db->prepare("SELECT DATE(created_at) as date, 
        SUM(event_type='app_open') as opens, 
        SUM(event_type='offer_click') as clicks, 
        SUM(event_type='offer_apply') as applies, 
        COUNT(DISTINCT COALESCE(NULLIF(device_model,''), ip)) as users 
    FROM app_events WHERE created_at >= ? GROUP BY DATE(created_at) ORDER BY date");
    $dy->execute([$dateFrom]); 
    $eventsByDate = $dy->fetchAll();
    
    // Мержим в allDates
    foreach ($eventsByDate as $row) {
        $d = $row['date'];
        if (isset($allDates[$d])) {
            $allDates[$d]['opens'] = (int)$row['opens'];
            $allDates[$d]['clicks'] = (int)$row['clicks'];
            $allDates[$d]['applies'] = (int)$row['applies'];
            $allDates[$d]['users'] = (int)$row['users'];
        }
    }

    // Последние действия
    $r = $db->prepare("SELECT event_type, screen_name, offer_title, device_model, platform, created_at FROM app_events WHERE created_at >= ? ORDER BY created_at DESC LIMIT 20");
    $r->execute([$dateFrom]); $result['recent'] = $r->fetchAll();

} catch (Exception $e) { $result['message'] = 'Таблица событий создастся при первом использовании приложения'; }

// Финальный daily — непрерывный массив дат
$result['daily'] = array_values($allDates);

echo json_encode($result, JSON_UNESCAPED_UNICODE);

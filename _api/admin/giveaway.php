<?php
/**
 * API розыгрышей — управление из админки
 */
requireAdmin();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

// Автосоздание таблиц
try { $db->query("SELECT 1 FROM giveaways LIMIT 1");
    // Добавляем новые столбцы если нет
    try { $db->query("SELECT page_subtitle FROM giveaways LIMIT 1"); } catch (Exception $e2) {
        $db->exec("ALTER TABLE giveaways ADD COLUMN page_subtitle varchar(500) DEFAULT NULL AFTER description, ADD COLUMN page_steps text DEFAULT NULL AFTER page_subtitle, ADD COLUMN page_rules text DEFAULT NULL AFTER page_steps");
    }
    try { $db->query("SELECT offer_id FROM giveaways LIMIT 1"); } catch (Exception $e3) {
        $db->exec("ALTER TABLE giveaways ADD COLUMN offer_id int(11) DEFAULT NULL AFTER status");
        $db->exec("ALTER TABLE giveaways ADD COLUMN page_subtitle varchar(500) DEFAULT NULL AFTER description, ADD COLUMN page_steps text DEFAULT NULL AFTER page_subtitle, ADD COLUMN page_rules text DEFAULT NULL AFTER page_steps");
    }
} catch (Exception $e) {
    $migration = file_get_contents(__DIR__ . '/../../database-giveaway-migration.sql');
    foreach (explode(';', $migration) as $q) { $q = trim($q); if ($q) $db->exec($q); }
}

function maskEmail(string $email): string {
    $parts = explode('@', $email);
    if (count($parts) !== 2) return '***@***';
    $name = $parts[0]; $domain = $parts[1];
    $visible = max(2, (int)(mb_strlen($name) * 0.4));
    return mb_substr($name, 0, $visible) . str_repeat('*', mb_strlen($name) - $visible) . '@' . $domain;
}

function maskIp(string $ip): string {
    $parts = explode('.', $ip);
    if (count($parts) === 4) return $parts[0] . '.' . $parts[1] . '.*.*';
    return '***';
}

function recalcGiveaway(PDO $db, int $giveawayId): array {
    $gw = $db->prepare("SELECT * FROM giveaways WHERE id = ?"); $gw->execute([$giveawayId]);
    $gw = $gw->fetch(); if (!$gw) return ['error' => 'Not found'];

    // Собираем одобренные конверсии за период конкурса
    $offerFilter = '';
    $params = [$gw['start_at'], $gw['end_at']];
    if (!empty($gw['offer_id'])) {
        $offerFilter = ' AND pc.offer_id = ?';
        $params[] = (int)$gw['offer_id'];
    }
    $stmt = $db->prepare("SELECT pc.id as conv_id, pc.offer_id, pc.payout, pc.ip, pc.status,
        o.title as offer_title, ua.user_id, u.name as user_name, u.email as user_email
        FROM postback_conversions pc
        LEFT JOIN offers o ON pc.offer_id = o.id
        LEFT JOIN click_stats cs ON pc.click_id = cs.id OR pc.aff_sub = cs.id
        LEFT JOIN user_applications ua ON ua.click_stat_id = cs.id
        LEFT JOIN users u ON ua.user_id = u.id
        WHERE pc.status = 'approved' AND pc.created_at >= ? AND pc.created_at <= ?{$offerFilter}
        ORDER BY pc.created_at ASC");
    $stmt->execute($params);
    $conversions = $stmt->fetchAll();

    $totalAmount = 0;
    $newEntries = 0;
    foreach ($conversions as $c) {
        $totalAmount += (float)$c['payout'];
        if (!$c['user_id'] || !$c['user_email']) continue;
        // Upsert участника
        try {
            $db->prepare("INSERT INTO giveaway_entries (giveaway_id, user_id, user_name, user_email, offer_id, offer_title, conversion_id, payout, ip)
                VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payout=VALUES(payout)")
               ->execute([$giveawayId, $c['user_id'], $c['user_name'] ?: 'Участник', $c['user_email'],
                          $c['offer_id'], $c['offer_title'] ?: '', $c['conv_id'], $c['payout'], $c['ip'] ?: '']);
            $newEntries++;
        } catch (Exception $e) {}
    }

    $prizeAmount = round($totalAmount * (float)$gw['prize_percent'] / 100, 2);
    $db->prepare("UPDATE giveaways SET total_conversions_amount = ?, prize_amount = ? WHERE id = ?")
       ->execute([$totalAmount, $prizeAmount, $giveawayId]);

    return ['total_amount' => $totalAmount, 'prize_amount' => $prizeAmount, 'entries' => $newEntries];
}

switch ($action) {

case 'list':
    $rows = $db->query("SELECT g.*, o.title as offer_title FROM giveaways g LEFT JOIN offers o ON g.offer_id = o.id ORDER BY g.created_at DESC")->fetchAll();
    foreach ($rows as &$r) {
        $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
        $cnt->execute([$r['id']]); $r['entries_count'] = (int)$cnt->fetch()['cnt'];
    } unset($r);
    echo json_encode($rows);
    break;

case 'create':
    if ($method !== 'POST') { echo json_encode(['error'=>'POST']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $offerId = !empty($data['offer_id']) ? (int)$data['offer_id'] : null;
    $db->prepare("INSERT INTO giveaways (title, description, page_subtitle, page_steps, page_rules, prize_percent, start_at, end_at, draw_at, status, offer_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$data['title']??'Розыгрыш', $data['description']??'', $data['page_subtitle']??'',
                  $data['page_steps']??'', $data['page_rules']??'',
                  $data['prize_percent']??10,
                  $data['start_at']??date('Y-m-d H:i:s'), $data['end_at']??date('Y-m-d H:i:s', strtotime('+30 days')),
                  $data['draw_at']??null, 'planned', $offerId]);
    echo json_encode(['success'=>true, 'id'=>$db->lastInsertId()]);
    break;

case 'update':
    if ($method !== 'POST') { echo json_encode(['error'=>'POST']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $offerId = isset($data['offer_id']) ? ($data['offer_id'] ? (int)$data['offer_id'] : null) : null;
    $db->prepare("UPDATE giveaways SET title=?, description=?, page_subtitle=?, page_steps=?, page_rules=?, prize_percent=?, start_at=?, end_at=?, draw_at=?, status=?, offer_id=? WHERE id=?")
       ->execute([$data['title'], $data['description']??'', $data['page_subtitle']??'', $data['page_steps']??'', $data['page_rules']??'',
                  $data['prize_percent']??10,
                  $data['start_at'], $data['end_at'], $data['draw_at']??null, $data['status']??'planned', $offerId, $data['id']]);
    echo json_encode(['success'=>true]);
    break;

case 'delete':
    if ($method !== 'POST') { echo json_encode(['error'=>'POST']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $db->prepare("DELETE FROM giveaway_entries WHERE giveaway_id = ?")->execute([$data['id']]);
    $db->prepare("DELETE FROM giveaways WHERE id = ?")->execute([$data['id']]);
    echo json_encode(['success'=>true]);
    break;

case 'entries':
    $gwId = (int)($_GET['id'] ?? 0);
    $entries = $db->prepare("SELECT * FROM giveaway_entries WHERE giveaway_id = ? ORDER BY created_at DESC");
    $entries->execute([$gwId]); $entries = $entries->fetchAll();
    foreach ($entries as &$en) {
        $en['user_email_masked'] = maskEmail($en['user_email']);
        $en['ip_masked'] = maskIp($en['ip'] ?? '');
    } unset($en);
    echo json_encode($entries);
    break;

case 'recalc':
    if ($method !== 'POST') { echo json_encode(['error'=>'POST']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $result = recalcGiveaway($db, (int)$data['id']);
    echo json_encode(array_merge(['success'=>true], $result));
    break;

case 'draw':
    // Запуск розыгрыша — выбор победителя
    if ($method !== 'POST') { echo json_encode(['error'=>'POST']); exit; }
    $data = json_decode(file_get_contents('php://input'), true);
    $gwId = (int)$data['id'];

    // Пересчитаем перед розыгрышем
    recalcGiveaway($db, $gwId);

    $entries = $db->prepare("SELECT * FROM giveaway_entries WHERE giveaway_id = ?");
    $entries->execute([$gwId]); $entries = $entries->fetchAll();
    if (!$entries) { echo json_encode(['error'=>'Нет участников']); exit; }

    // Случайный победитель
    $winner = $entries[array_rand($entries)];
    $db->prepare("UPDATE giveaways SET status = 'finished', winner_id = ? WHERE id = ?")
       ->execute([$winner['id'], $gwId]);

    $gw = $db->prepare("SELECT * FROM giveaways WHERE id = ?"); $gw->execute([$gwId]); $gw = $gw->fetch();

    echo json_encode([
        'success' => true,
        'winner' => [
            'name' => $winner['user_name'],
            'email_masked' => maskEmail($winner['user_email']),
            'offer' => $winner['offer_title'],
        ],
        'prize_amount' => $gw['prize_amount'],
        'total_entries' => count($entries)
    ]);
    break;

case 'active':
    // Получить активный розыгрыш (для плашки на сайте)
    $stmt = $db->query("SELECT * FROM giveaways WHERE status IN ('active','drawing') AND start_at <= NOW() AND end_at >= NOW() ORDER BY start_at DESC LIMIT 1");
    $active = $stmt->fetch();
    if ($active) {
        $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
        $cnt->execute([$active['id']]); $active['entries_count'] = (int)$cnt->fetch()['cnt'];
        // Пересчитаем призовой фонд
        recalcGiveaway($db, (int)$active['id']);
        $active = $db->prepare("SELECT * FROM giveaways WHERE id = ?"); $active->execute([$active['id'] ?? 0]);
        // re-fetch
        $stmt2 = $db->query("SELECT * FROM giveaways WHERE status IN ('active','drawing') AND start_at <= NOW() AND end_at >= NOW() ORDER BY start_at DESC LIMIT 1");
        $active = $stmt2->fetch();
        if ($active) {
            $cnt2 = $db->prepare("SELECT COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
            $cnt2->execute([$active['id']]); $active['entries_count'] = (int)$cnt2->fetch()['cnt'];
        }
    }
    echo json_encode($active ?: null);
    break;

case 'export-csv':
    // Экспорт участников в CSV
    $gwId = (int)($_GET['id'] ?? 0);
    $entries = $db->prepare("SELECT ge.user_name, ge.user_email, ge.offer_title, ge.payout, ge.ip, ge.created_at FROM giveaway_entries ge WHERE ge.giveaway_id = ? ORDER BY ge.created_at ASC");
    $entries->execute([$gwId]);
    $rows = $entries->fetchAll();
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="giveaway_' . $gwId . '_participants.csv"');
    echo "ï»¿"; // BOM for Excel
    echo "Имя;Email;Оффер;Сумма;IP;Дата\n";
    foreach ($rows as $r) {
        echo implode(';', [
            $r['user_name'],
            maskEmail($r['user_email']),
            $r['offer_title'] ?: '—',
            $r['payout'],
            maskIp($r['ip'] ?? ''),
            $r['created_at']
        ]) . "\n";
    }
    exit;

case 'stats':
    // Подробная статистика розыгрыша
    $gwId = (int)($_GET['id'] ?? 0);
    $gw = $db->prepare("SELECT g.*, o.title as offer_title FROM giveaways g LEFT JOIN offers o ON g.offer_id = o.id WHERE g.id = ?");
    $gw->execute([$gwId]); $gw = $gw->fetch();
    if (!$gw) { echo json_encode(['error' => 'Not found']); exit; }
    
    // Участники по офферам
    $byOffer = $db->prepare("SELECT offer_title, COUNT(*) as cnt, SUM(payout) as total_payout FROM giveaway_entries WHERE giveaway_id = ? GROUP BY offer_title ORDER BY cnt DESC");
    $byOffer->execute([$gwId]);
    
    // Участники по дням
    $byDay = $db->prepare("SELECT DATE(created_at) as dt, COUNT(*) as cnt FROM giveaway_entries WHERE giveaway_id = ? GROUP BY DATE(created_at) ORDER BY dt ASC");
    $byDay->execute([$gwId]);
    
    // Уникальные пользователи
    $uniqueUsers = $db->prepare("SELECT COUNT(DISTINCT user_id) as cnt FROM giveaway_entries WHERE giveaway_id = ?");
    $uniqueUsers->execute([$gwId]);
    
    // Дубликаты (один user_id с несколькими конверсиями)
    $duplicates = $db->prepare("SELECT user_name, user_email, COUNT(*) as entries_count FROM giveaway_entries WHERE giveaway_id = ? GROUP BY user_id HAVING entries_count > 1 ORDER BY entries_count DESC LIMIT 10");
    $duplicates->execute([$gwId]);
    $dupes = $duplicates->fetchAll();
    foreach ($dupes as &$d) { $d['user_email'] = maskEmail($d['user_email']); } unset($d);
    
    // Подозрительные IP (один IP — разные пользователи)
    $suspiciousIps = $db->prepare("SELECT ip, COUNT(DISTINCT user_id) as user_count, GROUP_CONCAT(DISTINCT user_name SEPARATOR ', ') as names FROM giveaway_entries WHERE giveaway_id = ? AND ip != '' GROUP BY ip HAVING user_count > 1 ORDER BY user_count DESC LIMIT 10");
    $suspiciousIps->execute([$gwId]);
    $suspicious = $suspiciousIps->fetchAll();
    foreach ($suspicious as &$s) { $s['ip'] = maskIp($s['ip']); } unset($s);
    
    echo json_encode([
        'giveaway' => $gw,
        'by_offer' => $byOffer->fetchAll(),
        'by_day' => $byDay->fetchAll(),
        'unique_users' => (int)$uniqueUsers->fetch()['cnt'],
        'duplicates' => $dupes,
        'suspicious_ips' => $suspicious,
    ]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

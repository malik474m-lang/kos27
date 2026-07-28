<?php
// Postback от leads.su
// URL для leads.su: https://kosmozaim.ru/api/postback?click_id={click_id}&status={status}&payout={payout}&ip={ip}&offer_id={offer_id}&transaction_id={transaction_id}&aff_sub={aff_sub}&aff_sub2={aff_sub2}&aff_sub3={aff_sub3}&goal_id={goal_id}

$secret = getenv('POSTBACK_SECRET') ?: '';
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$clickId = $_GET['click_id'] ?? $_GET['clickid'] ?? null;
$status = $_GET['status'] ?? 'pending';
$payout = (float)($_GET['payout'] ?? 0);
$ip = $_GET['ip'] ?? null;
$externalOfferId = $_GET['offer_id'] ?? null;
$transactionId = $_GET['transaction_id'] ?? null;
$affSub = $_GET['aff_sub'] ?? $_GET['sub1'] ?? null;
$affSub2 = $_GET['aff_sub2'] ?? $_GET['sub2'] ?? null;
$affSub3 = $_GET['aff_sub3'] ?? $_GET['sub3'] ?? null;
$goalId = $_GET['goal_id'] ?? null;
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';

// Нормализуем статус
$statusMap = [
    'approved' => 'approved',
    'approve' => 'approved',
    'confirmed' => 'approved',
    'paid' => 'approved',
    'rejected' => 'rejected',
    'reject' => 'rejected',
    'declined' => 'rejected',
    'canceled' => 'rejected',
    'cancelled' => 'rejected',
    'pending' => 'pending',
    'hold' => 'hold',
    'processing' => 'hold',
];
$normalizedStatus = $statusMap[strtolower($status)] ?? strtolower($status);

// Пробуем найти наш offer_id по aff_sub (click_stats.id)
$internalOfferId = null;
if ($affSub) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT offer_id FROM click_stats WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$affSub]);
        $row = $stmt->fetch();
        if ($row) $internalOfferId = (int)$row['offer_id'];
    } catch (Exception $e) {}
}

// Проверяем дубликат по transaction_id
if ($transactionId) {
    try {
        $db = getDB();
        $existing = $db->prepare("SELECT id FROM postback_conversions WHERE transaction_id = ? LIMIT 1");
        $existing->execute([$transactionId]);
        if ($existing->fetch()) {
            // Обновляем статус
            $db->prepare("UPDATE postback_conversions SET status = ?, payout = ?, ip = COALESCE(?, ip) WHERE transaction_id = ?")
               ->execute([$normalizedStatus, $payout, $ip, $transactionId]);
            echo json_encode(['ok' => true, 'action' => 'updated']);
            exit;
        }
    } catch (Exception $e) {}
}

// Сохраняем
try {
    $db = getDB();
    $db->prepare("INSERT INTO postback_conversions (click_id, transaction_id, offer_id, external_offer_id, status, payout, ip, aff_sub, aff_sub2, aff_sub3, goal_id, raw_query) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$clickId, $transactionId, $internalOfferId, $externalOfferId, $normalizedStatus, $payout, $ip, $affSub, $affSub2, $affSub3, $goalId, $rawQuery]);
    echo json_encode(['ok' => true, 'action' => 'created']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}

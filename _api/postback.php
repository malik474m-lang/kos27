<?php
// Postback от leads.su
// Поддерживаемые параметры:
// - click_id ИЛИ conversion_id
// - status
// - payout
// - ip
// - offer_id
// - transaction_id
// - aff_sub / aff_sub1 / sub1
// - aff_sub2 / sub2
// - aff_sub3 / sub3
// - goal_id
//
// Рекомендуемый URL для leads.su:
// https://kosmozaim.ru/api/postback?click_id={conversion_id}&status={status}&payout={payout}&ip={ip}&offer_id={offer_id}&transaction_id={transaction_id}&aff_sub={aff_sub1}&goal_id={goal_id}

$secret = getenv('POSTBACK_SECRET') ?: '';
if ($secret && ($_GET['secret'] ?? '') !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$clickId = $_GET['click_id']
    ?? $_GET['clickid']
    ?? $_GET['conversion_id']
    ?? $_GET['lead_id']
    ?? null;

$status = $_GET['status'] ?? 'pending';
$payout = (float)($_GET['payout'] ?? 0);
$ip = $_GET['ip'] ?? null;
$externalOfferId = $_GET['offer_id'] ?? $_GET['program_id'] ?? null;
$transactionId = $_GET['transaction_id'] ?? $_GET['trans_id'] ?? null;
$affSub = $_GET['aff_sub'] ?? $_GET['aff_sub1'] ?? $_GET['sub1'] ?? null;
$affSub2 = $_GET['aff_sub2'] ?? $_GET['sub2'] ?? null;
$affSub3 = $_GET['aff_sub3'] ?? $_GET['sub3'] ?? null;
$goalId = $_GET['goal_id'] ?? $_GET['goal'] ?? null;
$rawQuery = $_SERVER['QUERY_STRING'] ?? '';

// Нормализуем статус
$statusMap = [
    'approved' => 'approved',
    'approve' => 'approved',
    'confirmed' => 'approved',
    'paid' => 'approved',
    'sale' => 'approved',
    'lead_approve' => 'approved',
    'rejected' => 'rejected',
    'reject' => 'rejected',
    'declined' => 'rejected',
    'canceled' => 'rejected',
    'cancelled' => 'rejected',
    'lead_cancel' => 'rejected',
    'pending' => 'pending',
    'lead' => 'pending',
    'hold' => 'hold',
    'processing' => 'hold',
];
$normalizedStatus = $statusMap[strtolower((string)$status)] ?? strtolower((string)$status);

// Пробуем найти наш offer_id по aff_sub (click_stats.id)
$internalOfferId = null;
$db = null;
try {
    $db = getDB();
    if ($affSub) {
        $stmt = $db->prepare("SELECT offer_id FROM click_stats WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$affSub]);
        $row = $stmt->fetch();
        if ($row) $internalOfferId = (int)$row['offer_id'];
    }
} catch (Exception $e) {}

if (!$db) {
    try { $db = getDB(); } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'DB connection error']);
        exit;
    }
}

// Проверяем дубликат: сначала transaction_id, потом click_id + status
try {
    if ($transactionId) {
        $existing = $db->prepare("SELECT id FROM postback_conversions WHERE transaction_id = ? LIMIT 1");
        $existing->execute([$transactionId]);
        if ($existing->fetch()) {
            $db->prepare("UPDATE postback_conversions SET click_id = COALESCE(?, click_id), offer_id = COALESCE(?, offer_id), external_offer_id = COALESCE(?, external_offer_id), status = ?, payout = ?, ip = COALESCE(?, ip), aff_sub = COALESCE(?, aff_sub), aff_sub2 = COALESCE(?, aff_sub2), aff_sub3 = COALESCE(?, aff_sub3), goal_id = COALESCE(?, goal_id), raw_query = ? WHERE transaction_id = ?")
               ->execute([$clickId, $internalOfferId, $externalOfferId, $normalizedStatus, $payout, $ip, $affSub, $affSub2, $affSub3, $goalId, $rawQuery, $transactionId]);
            echo json_encode(['ok' => true, 'action' => 'updated_by_transaction', 'status' => $normalizedStatus]);
            exit;
        }
    }

    if ($clickId) {
        $existing = $db->prepare("SELECT id FROM postback_conversions WHERE click_id = ? AND status = ? LIMIT 1");
        $existing->execute([$clickId, $normalizedStatus]);
        if ($existing->fetch()) {
            echo json_encode(['ok' => true, 'action' => 'duplicate_ignored', 'status' => $normalizedStatus]);
            exit;
        }
    }
} catch (Exception $e) {}

// Сохраняем
try {
    $db->prepare("INSERT INTO postback_conversions (click_id, transaction_id, offer_id, external_offer_id, status, payout, ip, aff_sub, aff_sub2, aff_sub3, goal_id, raw_query) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$clickId, $transactionId, $internalOfferId, $externalOfferId, $normalizedStatus, $payout, $ip, $affSub, $affSub2, $affSub3, $goalId, $rawQuery]);

    echo json_encode([
        'ok' => true,
        'action' => 'created',
        'status' => $normalizedStatus,
        'offer_id' => $internalOfferId,
        'click_id' => $clickId,
        'transaction_id' => $transactionId,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error', 'message' => $e->getMessage()]);
}

<?php
require_once __DIR__ . '/../../includes/user-auth.php';
require_once __DIR__ . '/../../includes/kosmobonus.php';
header('Content-Type: application/json; charset=UTF-8');

$user = getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    echo json_encode([
        'balance' => kosmoBonusBalance((int)$user['id']),
        'requests' => kosmoBonusWithdrawRequestsByUser((int)$user['id'], 30),
    ]);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $amount = (int)($data['amount'] ?? 0);
    $bankName = trim((string)($data['bank_name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $cardholderName = trim((string)($data['cardholder_name'] ?? ''));

    $result = kosmoBonusCreateWithdrawalRequest((int)$user['id'], $amount, $bankName, $phone, $cardholderName);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Не удалось создать заявку']);
        exit;
    }

    echo json_encode(['success' => true, 'request_id' => $result['request_id'], 'new_balance' => $result['new_balance']]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

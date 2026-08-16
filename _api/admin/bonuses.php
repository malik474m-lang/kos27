<?php
require_once __DIR__ . '/../../includes/kosmobonus.php';

$action = $_GET['action'] ?? 'history';
$db = getDB();
ensureKosmoBonusTables();

if ($action === 'history' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    echo json_encode(kosmoBonusAdminHistory(120));
    exit;
}

if ($action === 'accrue' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int)($data['user_id'] ?? 0);
    $amount = (int)($data['amount'] ?? 0);
    $description = trim((string)($data['description'] ?? ''));

    if ($userId <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id и amount обязательны']);
        exit;
    }

    $result = kosmoBonusManualAccrual($userId, $amount, $description);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Ошибка начисления']);
        exit;
    }

    echo json_encode(['success' => true, 'amount' => $result['amount'], 'new_balance' => $result['new_balance']]);
    exit;
}


if ($action === 'requests' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    echo json_encode(kosmoBonusAdminWithdrawalRequests(120));
    exit;
}

if ($action === 'process-request' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $requestId = (int)($data['request_id'] ?? 0);
    $processAction = trim((string)($data['process_action'] ?? ''));
    $adminComment = trim((string)($data['admin_comment'] ?? ''));

    if ($requestId <= 0 || $processAction === '') {
        http_response_code(400);
        echo json_encode(['error' => 'request_id и process_action обязательны']);
        exit;
    }

    $result = kosmoBonusProcessWithdrawalRequest($requestId, $processAction, $adminComment);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Ошибка обработки заявки']);
        exit;
    }

    echo json_encode(['success' => true, 'status' => $result['status'], 'new_balance' => $result['new_balance'] ?? null]);
    exit;
}

if ($action === 'withdraw' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $userId = (int)($data['user_id'] ?? 0);
    $amount = (int)($data['amount'] ?? 0);
    $description = trim((string)($data['description'] ?? ''));

    if ($userId <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id и amount обязательны']);
        exit;
    }

    $result = kosmoBonusWithdraw($userId, $amount, $description);
    if (!$result['ok']) {
        http_response_code(400);
        echo json_encode(['error' => $result['error'] ?? 'Ошибка списания']);
        exit;
    }

    echo json_encode(['success' => true, 'amount' => $result['amount'], 'new_balance' => $result['new_balance']]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);

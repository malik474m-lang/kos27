<?php
/**
 * API управления лицензией
 */

require_once __DIR__ . '/../../includes/license.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — информация о лицензии
if ($method === 'GET') {
    $info = getLicenseInfo();
    header('Content-Type: application/json');
    echo json_encode($info, JSON_UNESCAPED_UNICODE);
    exit;
}

// POST — действия с лицензией
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'activate';
    
    header('Content-Type: application/json');
    
    if ($action === 'activate') {
        $key = trim($input['license_key'] ?? '');
        echo json_encode(activateLicense($key), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'remove') {
        removeLicense();
        echo json_encode(['success' => true, 'message' => 'Лицензия удалена']);
        exit;
    }
    
    if ($action === 'check') {
        // Принудительная проверка — сбрасывает кэш
        echo json_encode(forceLicenseCheck(), JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

<?php
/**
 * API управления лицензией
 */

require_once __DIR__ . '/../../includes/license.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// GET — получить информацию о лицензии
if ($method === 'GET' || $action === 'info') {
    $info = getLicenseInfo();
    header('Content-Type: application/json');
    echo json_encode($info, JSON_UNESCAPED_UNICODE);
    exit;
}

// POST — активировать лицензию
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'activate';
    
    if ($action === 'activate') {
        $key = trim($input['license_key'] ?? '');
        $result = activateLicense($key);
        
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'remove') {
        removeLicense();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Лицензия удалена']);
        exit;
    }
    
    if ($action === 'check') {
        $status = checkLicenseStatus();
        header('Content-Type: application/json');
        echo json_encode($status, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

header('Content-Type: application/json');
http_response_code(400);
echo json_encode(['error' => 'Invalid request']);

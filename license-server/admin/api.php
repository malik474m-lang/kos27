<?php
/**
 * API админки сервера лицензий
 */
header('Content-Type: application/json; charset=UTF-8');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// === ЛОГИН (доступен без авторизации) ===
if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    
    $rate = checkRateLimit('admin_login', 5, 300);
    if (!$rate['allowed']) {
        http_response_code(429);
        echo json_encode(['error' => 'Слишком много попыток']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Неверные данные']);
        exit;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['lic_admin_id'] = $admin['id'];
    $_SESSION['lic_admin_user'] = $admin['username'];
    
    echo json_encode(['success' => true]);
    exit;
}

// === ЛОГАУТ ===
if ($action === 'logout' && $method === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Проверка авторизации для остальных действий
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_GET['token'] ?? '';
if ($token !== ADMIN_API_TOKEN) {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
        session_start();
    }
    if (empty($_SESSION['lic_admin_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// === Список лицензий ===
if ($action === 'list') {
    $licenses = $db->query("SELECT l.*, 
        (SELECT COUNT(*) FROM license_log ll WHERE ll.license_id = l.id AND ll.action = 'verify' AND ll.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as checks_24h
        FROM licenses l ORDER BY l.id DESC")->fetchAll();
    echo json_encode(['licenses' => $licenses]);
    exit;
}

// === Создать лицензию ===
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $key = generateLicenseKey();
    $domain = normalizeDomain($data['domain'] ?? '');
    $plan = in_array($data['plan'] ?? '', ['trial','basic','pro','enterprise']) ? $data['plan'] : 'basic';
    $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
    $features = $data['features'] ?? null;
    if ($features && is_array($features)) $features = json_encode($features);
    
    $db->prepare("INSERT INTO licenses (license_key, domain, product, plan, status, owner_name, owner_email, expires_at, features, notes, max_activations) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $key, $domain, $data['product'] ?? 'kosmozaim', $plan, 'active',
           $data['owner_name'] ?? null, $data['owner_email'] ?? null,
           $expiresAt, $features, $data['notes'] ?? null,
           (int)($data['max_activations'] ?? 1),
       ]);
    
    echo json_encode(['success' => true, 'license_key' => $key, 'id' => $db->lastInsertId()]);
    exit;
}

// === Обновить лицензию ===
if ($action === 'update' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id required']); exit; }
    
    $sets = [];
    $params = [];
    foreach (['domain','plan','status','owner_name','owner_email','expires_at','notes','max_activations'] as $field) {
        if (array_key_exists($field, $data)) {
            if ($field === 'domain') $data[$field] = normalizeDomain($data[$field]);
            $sets[] = "`$field` = ?";
            $params[] = $data[$field];
        }
    }
    if (!empty($data['features'])) {
        $sets[] = "`features` = ?";
        $params[] = is_array($data['features']) ? json_encode($data['features']) : $data['features'];
    }
    if ($sets) {
        $params[] = $id;
        $db->prepare("UPDATE licenses SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
    }
    echo json_encode(['success' => true]);
    exit;
}

// === Удалить лицензию ===
if ($action === 'delete' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM licenses WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM license_log WHERE license_id = ?")->execute([$id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// === Логи ===
if ($action === 'logs') {
    $licId = (int)($_GET['license_id'] ?? 0);
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    $where = $licId ? "WHERE license_id = $licId" : '';
    $logs = $db->query("SELECT * FROM license_log $where ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    echo json_encode(['logs' => $logs]);
    exit;
}

// === Статистика ===
if ($action === 'stats') {
    $stats = [
        'total' => (int)$db->query("SELECT COUNT(*) FROM licenses")->fetchColumn(),
        'active' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'active'")->fetchColumn(),
        'expired' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'expired'")->fetchColumn(),
        'suspended' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = 'suspended'")->fetchColumn(),
        'checks_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action IN ('verify','heartbeat') AND created_at > CURDATE()")->fetchColumn(),
        'activations_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = 'activate' AND created_at > CURDATE()")->fetchColumn(),
        'denied_today' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = 'denied' AND created_at > CURDATE()")->fetchColumn(),
    ];
    echo json_encode($stats);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

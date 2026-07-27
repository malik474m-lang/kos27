<?php
$db = getDB();
$action = $_GET['action'] ?? 'overview';

if ($action === 'overview') {
    // Общая информация
    $loginLog = $db->query("SELECT * FROM admin_login_log ORDER BY created_at DESC LIMIT 30")->fetchAll();
    $whitelist = $db->query("SELECT * FROM admin_ip_whitelist ORDER BY created_at DESC")->fetchAll();
    $failedToday = $db->query("SELECT COUNT(*) as cnt FROM admin_login_log WHERE success = 0 AND created_at >= CURDATE()")->fetch()['cnt'];
    $successToday = $db->query("SELECT COUNT(*) as cnt FROM admin_login_log WHERE success = 1 AND created_at >= CURDATE()")->fetch()['cnt'];
    $blockedIps = $db->query("SELECT ip, COUNT(*) as fails FROM admin_login_log WHERE success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) GROUP BY ip HAVING fails >= 10")->fetchAll();
    $currentIp = getClientIp();

    echo json_encode([
        'loginLog' => $loginLog,
        'whitelist' => $whitelist,
        'failedToday' => (int)$failedToday,
        'successToday' => (int)$successToday,
        'blockedIps' => $blockedIps,
        'currentIp' => $currentIp,
    ]);
}

if ($action === 'add-ip' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ip = trim($data['ip'] ?? '');
    $note = trim($data['note'] ?? '');
    if (!$ip) { http_response_code(400); echo json_encode(['error' => 'IP обязателен']); exit; }
    $exists = $db->prepare("SELECT id FROM admin_ip_whitelist WHERE ip = ?"); $exists->execute([$ip]);
    if ($exists->fetch()) { echo json_encode(['success' => true, 'message' => 'Уже в списке']); exit; }
    $db->prepare("INSERT INTO admin_ip_whitelist (ip, note) VALUES (?, ?)")->execute([$ip, $note]);
    echo json_encode(['success' => true]);
}

if ($action === 'remove-ip' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    if ($id) $db->prepare("DELETE FROM admin_ip_whitelist WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
}

if ($action === 'clear-log' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->exec("DELETE FROM admin_login_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['success' => true]);
}

if ($action === 'unblock-ip' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ip = trim($data['ip'] ?? '');
    if ($ip) $db->prepare("DELETE FROM admin_login_log WHERE ip = ? AND success = 0")->execute([$ip]);
    echo json_encode(['success' => true]);
}

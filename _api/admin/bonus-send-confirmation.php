<?php
/**
 * Отправка письма-подтверждения перевода бонусов пользователю
 * С вложением скрина/pdf транзакции
 */
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/kosmobonus.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? ''));

if ($requestId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'request_id обязателен']);
    exit;
}

$db = getDB();
ensureKosmoBonusTables();

$stmt = $db->prepare("SELECT r.*, u.email, u.name FROM bonus_withdraw_requests r LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$requestId]);
$req = $stmt->fetch();

if (!$req) {
    http_response_code(404);
    echo json_encode(['error' => 'Заявка не найдена']);
    exit;
}

if (empty($req['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email пользователя не найден']);
    exit;
}

// Обработка вложения
$attachPath = null;
$attachName = null;
if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['attachment'];
    $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Допустимые форматы: PNG, JPG, PDF']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'Максимальный размер файла: 5 МБ']);
        exit;
    }
    $attachPath = $file['tmp_name'];
    $attachName = $file['name'] ?: ('payment_confirmation.' . pathinfo($file['name'], PATHINFO_EXTENSION));
}

$userName = trim($req['name'] ?: explode('@', $req['email'])[0]);
$amount = (int)$req['amount'];
$bankName = trim($req['bank_name'] ?? '');
$cardholderName = trim($req['cardholder_name'] ?? '');
$statusLabel = $req['status'] === 'paid' ? 'Выплачено' : ($req['status'] === 'pending' ? 'На проверке' : $req['status']);

$subject = 'Подтверждение выплаты КосмоБонусов — ' . SITE_NAME;

$body = '<!DOCTYPE html><html><body style="font-family:-apple-system,sans-serif;color:#333;max-width:600px;margin:0 auto;padding:20px">'
    . '<h2 style="color:#d97706">🎁 Подтверждение выплаты КосмоБонусов</h2>'
    . '<p>Здравствуйте, <strong>' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</strong>!</p>'
    . '<p>Ваша заявка на вывод бонусов обработана.</p>'
    . '<table style="width:100%;border-collapse:collapse;margin:16px 0">'
    . '<tr><td style="padding:8px;border:1px solid #eee;font-weight:600">Сумма</td><td style="padding:8px;border:1px solid #eee">' . $amount . ' ₽</td></tr>'
    . '<tr><td style="padding:8px;border:1px solid #eee;font-weight:600">Банк</td><td style="padding:8px;border:1px solid #eee">' . htmlspecialchars($bankName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:8px;border:1px solid #eee;font-weight:600">Получатель</td><td style="padding:8px;border:1px solid #eee">' . htmlspecialchars($cardholderName, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '<tr><td style="padding:8px;border:1px solid #eee;font-weight:600">Статус</td><td style="padding:8px;border:1px solid #eee;color:#059669;font-weight:600">' . htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') . '</td></tr>'
    . '</table>';

if ($comment) {
    $body .= '<p><strong>Комментарий:</strong> ' . nl2br(htmlspecialchars($comment, ENT_QUOTES, 'UTF-8')) . '</p>';
}

if ($attachPath) {
    $body .= '<p style="color:#6b7280;font-size:13px">📎 К письму приложен документ о переводе.</p>';
}

$body .= '<p style="color:#999;font-size:12px;margin-top:24px">Спасибо, что пользуетесь программой КосмоБонус!<br>' . SITE_NAME . '</p>'
    . '</body></html>';

if ($attachPath && $attachName) {
    $result = sendMailWithAttachment($req['email'], $subject, $body, $attachPath, $attachName, true);
} else {
    $result = sendMail($req['email'], $subject, $body, true);
}

if ($result['ok']) {
    echo json_encode(['success' => true, 'message' => 'Письмо отправлено на ' . $req['email']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка отправки: ' . ($result['error'] ?? 'unknown')]);
}

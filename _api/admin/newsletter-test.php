<?php
/**
 * Тестовая отправка рассылки на один email
 * POST /api/admin/newsletters/:id/test
 * Body: {"email": "test@example.com"}
 */
require_once __DIR__ . '/../../includes/newsletter-helpers.php';
require_once __DIR__ . '/../../includes/audit-log.php';

header('Content-Type: application/json; charset=UTF-8');

$db = getDB();
$data = json_decode(file_get_contents('php://input'), true);
$testEmail = trim($data['email'] ?? '');

if (!$testEmail || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Укажите корректный email']);
    exit;
}

// Получаем рассылку
$nl = $db->prepare("SELECT * FROM newsletters WHERE id = ? LIMIT 1");
$nl->execute([$itemId]);
$newsletter = $nl->fetch();

if (!$newsletter) {
    http_response_code(404);
    echo json_encode(['error' => 'Рассылка не найдена']);
    exit;
}

// Собираем письмо
$offersBlock = buildOffersBlock($db);
$unsubLink = SITE_URL . '/unsubscribe?token=TEST';
$fullHtml = buildEmailHtml($newsletter['body_html'], $offersBlock, $unsubLink, (int)$newsletter['id'], 0, true);

$subject = '🧪 [ТЕСТ] ' . $newsletter['subject'];

// Отправляем
$result = sendOneEmail($testEmail, $subject, $fullHtml);

// Логируем
logSend($db, (int)$newsletter['id'], null, $testEmail, $result['ok'] ? 'sent' : 'failed', $result['error'], true);

// Аудит
auditLog('send', 'newsletter', (int)$newsletter['id'], 'Тест → ' . $testEmail);

if ($result['ok']) {
    echo json_encode([
        'success' => true,
        'message' => "Тестовое письмо отправлено на {$testEmail}"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка отправки: ' . ($result['error'] ?? 'неизвестная ошибка')
    ]);
}

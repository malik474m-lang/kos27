<?php
/**
 * Отправка рассылки всем подписчикам (с логированием)
 */
require_once __DIR__ . '/../../includes/audit-log.php';
require_once __DIR__ . '/../../includes/newsletter-helpers.php';

$db = getDB();
$nl = $db->prepare("SELECT * FROM newsletters WHERE id = ? LIMIT 1");
$nl->execute([$itemId]);
$newsletter = $nl->fetch();

if (!$newsletter) {
    http_response_code(404);
    echo json_encode(['error' => 'Рассылка не найдена']);
    exit;
}

$subs = $db->query("SELECT id, email, unsubscribe_token FROM subscribers WHERE is_active = 1")->fetchAll();
if (!$subs) {
    echo json_encode(['error' => 'Нет активных подписчиков']);
    exit;
}

$offersBlock = buildOffersBlock($db);
$nlId = (int)$newsletter['id'];
$subject = $newsletter['subject'];

$db->prepare("UPDATE newsletters SET status = 'sending' WHERE id = ?")->execute([$itemId]);

$sent = 0;
$failed = 0;
$errors = [];

foreach ($subs as $sub) {
    $unsubLink = SITE_URL . '/unsubscribe?token=' . $sub['unsubscribe_token'];
    $subId = (int)$sub['id'];
    
    $fullHtml = buildEmailHtml($newsletter['body_html'], $offersBlock, $unsubLink, $nlId, $subId, false);
    $result = sendOneEmail($sub['email'], $subject, $fullHtml, $unsubLink);
    
    // Логируем
    logSend($db, $nlId, $subId, $sub['email'], $result['ok'] ? 'sent' : 'failed', $result['error'], false);

    if ($result['ok']) {
        $sent++;
    } else {
        $failed++;
        $errors[] = $sub['email'];
    }

    usleep(200000); // 0.2 сек
}

$status = ($sent === 0) ? 'failed' : 'sent';
$db->prepare("UPDATE newsletters SET status = ?, sent_count = ?, failed_count = ?, sent_at = NOW() WHERE id = ?")
   ->execute([$status, $sent, $failed, $itemId]);

// Аудит
auditLog('send', 'newsletter', $itemId, $newsletter['subject'] ?? 'Рассылка', ['sent' => $sent, 'failed' => $failed, 'total' => count($subs)]);

$result = ['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => count($subs)];
if ($errors) $result['failedEmails'] = array_slice($errors, 0, 10);
echo json_encode($result);

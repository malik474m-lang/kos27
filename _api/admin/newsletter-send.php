<?php
$db = getDB();
$nl = $db->prepare("SELECT * FROM newsletters WHERE id = ? LIMIT 1");
$nl->execute([$itemId]);
$newsletter = $nl->fetch();

if (!$newsletter) {
    http_response_code(404);
    echo json_encode(['error' => 'Рассылка не найдена']);
    exit;
}

// Получаем активных подписчиков
$subs = $db->query("SELECT id, email, unsubscribe_token FROM subscribers WHERE is_active = 1")->fetchAll();

if (!$subs) {
    echo json_encode(['error' => 'Нет активных подписчиков']);
    exit;
}

$db->prepare("UPDATE newsletters SET status = 'sending' WHERE id = ?")->execute([$itemId]);

$sent = 0;
$failed = 0;
$from = 'info@kosmozaim.ru';
$fromName = SITE_NAME;
$subject = $newsletter['subject'];

foreach ($subs as $sub) {
    $unsubLink = SITE_URL . '/unsubscribe?token=' . $sub['unsubscribe_token'];

    $body = $newsletter['body_html'];
    $body .= '<br><hr style="border:none;border-top:1px solid #eee;margin:24px 0">';
    $body .= '<p style="font-size:12px;color:#999;text-align:center">';
    $body .= 'Вы получили это письмо, потому что подписались на рассылку ' . SITE_NAME . '.<br>';
    $body .= '<a href="' . $unsubLink . '" style="color:#999">Отписаться от рассылки</a></p>';

    $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#333;max-width:600px;margin:0 auto;padding:20px">' . $body . '</body></html>';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "List-Unsubscribe: <{$unsubLink}>\r\n";

    $ok = @mail($sub['email'], '=?UTF-8?B?' . base64_encode($subject) . '?=', $fullHtml, $headers);

    if ($ok) $sent++;
    else $failed++;

    usleep(100000); // 0.1 сек между письмами
}

$status = $failed === count($subs) ? 'failed' : 'sent';
$db->prepare("UPDATE newsletters SET status = ?, sent_count = ?, failed_count = ?, sent_at = NOW() WHERE id = ?")
   ->execute([$status, $sent, $failed, $itemId]);

echo json_encode(['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => count($subs)]);

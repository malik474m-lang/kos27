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

$subs = $db->query("SELECT id, email, unsubscribe_token FROM subscribers WHERE is_active = 1")->fetchAll();
if (!$subs) {
    echo json_encode(['error' => 'Нет активных подписчиков']);
    exit;
}

// Офферы для блока в письме
$offers = $db->query("SELECT id, title, slug, rate, amount_max, free_term_days, logo_url, affiliate_url FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 5")->fetchAll();

// Собираем HTML-блок офферов
$offersBlock = '';
if ($offers) {
    $offersBlock .= '<table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0">';
    foreach ($offers as $o) {
        $logo = trim($o['logo_url'] ?? '');
        if ($logo && !str_starts_with($logo, 'http')) {
            if (str_starts_with($logo, '/public/')) $logo = substr($logo, 7);
            $logo = SITE_URL . $logo;
        }
        $free = $o['free_term_days'] > 0 ? '<span style="color:#059669;font-size:12px;font-weight:600"> • 0% на ' . $o['free_term_days'] . ' дней</span>' : '';
        $clickUrl = SITE_URL . '/click/' . $o['id'];
        $offerUrl = SITE_URL . '/offer/' . $o['slug'];

        $offersBlock .= '<tr><td style="padding:10px 0;border-bottom:1px solid #f3f4f6">';
        $offersBlock .= '<table width="100%" cellpadding="0" cellspacing="0"><tr>';
        // Логотип
        $offersBlock .= '<td width="50" style="vertical-align:middle;padding-right:12px">';
        if ($logo) {
            $offersBlock .= '<img src="' . e($logo) . '" alt="' . e($o['title']) . '" width="44" height="44" style="border-radius:8px;background:#f9fafb;object-fit:contain">';
        } else {
            $offersBlock .= '<div style="width:44px;height:44px;background:#f3f4f6;border-radius:8px;text-align:center;line-height:44px;font-size:20px">🏦</div>';
        }
        $offersBlock .= '</td>';
        // Название и условия
        $offersBlock .= '<td style="vertical-align:middle">';
        $offersBlock .= '<a href="' . e($offerUrl) . '" style="color:#111827;font-weight:600;font-size:14px;text-decoration:none">' . e($o['title']) . '</a><br>';
        $offersBlock .= '<span style="color:#6b7280;font-size:12px">от ' . $o['rate'] . '% • до ' . number_format($o['amount_max'], 0, '', ' ') . ' ₽' . $free . '</span>';
        $offersBlock .= '</td>';
        // Кнопка
        $offersBlock .= '<td width="100" style="vertical-align:middle;text-align:right">';
        $offersBlock .= '<a href="' . e($clickUrl) . '" style="display:inline-block;background:#059669;color:#ffffff;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Оформить</a>';
        $offersBlock .= '</td>';
        $offersBlock .= '</tr></table></td></tr>';
    }
    $offersBlock .= '</table>';
}

$db->prepare("UPDATE newsletters SET status = 'sending' WHERE id = ?")->execute([$itemId]);

$sent = 0;
$failed = 0;
$from = 'info@kosmozaim.ru';
$fromName = SITE_NAME;
$subject = $newsletter['subject'];
$errors = [];

foreach ($subs as $sub) {
    $unsubLink = SITE_URL . '/unsubscribe?token=' . $sub['unsubscribe_token'];

    // Собираем письмо
    $body = $newsletter['body_html'];
    // Вставляем блок офферов
    if (str_contains($body, '{{offers}}')) {
        $body = str_replace('{{offers}}', $offersBlock, $body);
    } else {
        $body .= $offersBlock;
    }

    // Трекинг кликов — заменяем все ссылки на трекинговые
    $nlId = (int)$newsletter['id'];
    $subId = (int)$sub['id'];
    $body = preg_replace_callback(
        '/<a\s([^>]*?)href=["\'](https?:\/\/[^"\'>]+)["\']([^>]*?)>/i',
        function($m) use ($nlId, $subId) {
            $url = $m[2];
            // Не трекаем ссылку отписки
            if (str_contains($url, 'unsubscribe')) return $m[0];
            $trackUrl = SITE_URL . '/api/nl-click?n=' . $nlId . '&s=' . $subId . '&url=' . urlencode($url);
            return '<a ' . $m[1] . 'href="' . $trackUrl . '"' . $m[3] . '>';
        },
        $body
    );

    $body .= '<br><hr style="border:none;border-top:1px solid #eee;margin:24px 0">';
    $body .= '<p style="font-size:12px;color:#999;text-align:center">';
    $body .= 'Вы получили это письмо, потому что подписались на рассылку ' . e(SITE_NAME) . '.<br>';
    $body .= '<a href="' . $unsubLink . '" style="color:#999">Отписаться от рассылки</a></p>';

    // Трекинг-пиксель открытия (в конце body)
    $body .= '<img src="' . SITE_URL . '/api/nl-open?n=' . $nlId . '&s=' . $subId . '" width="1" height="1" style="display:none" alt="">';

    $fullHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#333;margin:0;padding:0;background:#f9fafb">'
        . '<div style="max-width:600px;margin:0 auto;padding:24px;background:#ffffff">'
        . $body
        . '</div></body></html>';

    // Отправка через mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: Kosmozaim Newsletter\r\n";
    $headers .= "List-Unsubscribe: <{$unsubLink}>\r\n";
    $headers .= "List-Unsubscribe-Post: List-Unsubscribe=One-Click\r\n";

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    // Дополнительные параметры для sendmail — указываем envelope sender
    $ok = @mail($sub['email'], $encodedSubject, $fullHtml, $headers, '-f' . $from);

    if ($ok) {
        $sent++;
    } else {
        $failed++;
        $errors[] = $sub['email'];
    }

    usleep(200000); // 0.2 сек между письмами
}

$status = ($sent === 0) ? 'failed' : 'sent';
$db->prepare("UPDATE newsletters SET status = ?, sent_count = ?, failed_count = ?, sent_at = NOW() WHERE id = ?")
   ->execute([$status, $sent, $failed, $itemId]);

$result = ['success' => true, 'sent' => $sent, 'failed' => $failed, 'total' => count($subs)];
if ($errors) $result['failedEmails'] = array_slice($errors, 0, 10);
echo json_encode($result);

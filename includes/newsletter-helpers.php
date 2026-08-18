<?php
require_once __DIR__ . '/mailer.php';
/**
 * Хелперы для рассылок: сборка письма, отправка, логирование
 */

/**
 * Гарантировать что таблица лога существует
 */
function ensureSendLogTable(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    try {
        $db->query("SELECT 1 FROM newsletter_send_log LIMIT 1");
    } catch (Exception $e) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `newsletter_send_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `newsletter_id` int(11) NOT NULL,
              `subscriber_id` int(11) DEFAULT NULL,
              `email` varchar(255) NOT NULL,
              `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
              `error_message` varchar(500) DEFAULT NULL,
              `is_test` tinyint(1) NOT NULL DEFAULT 0,
              `sent_at` timestamp NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_newsletter` (`newsletter_id`),
              KEY `idx_email` (`email`),
              KEY `idx_sent_at` (`sent_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }
    $checked = true;
}

/**
 * Записать лог отправки
 */
function logSend(PDO $db, int $nlId, ?int $subId, string $email, string $status, ?string $error = null, bool $isTest = false): void {
    try {
        ensureSendLogTable($db);
        $db->prepare("INSERT INTO newsletter_send_log (newsletter_id, subscriber_id, email, status, error_message, is_test) VALUES (?,?,?,?,?,?)")
           ->execute([$nlId, $subId, $email, $status, $error ? mb_substr($error, 0, 500) : null, $isTest ? 1 : 0]);
    } catch (Exception $e) {
        error_log("Send log error: " . $e->getMessage());
    }
}

/**
 * Собрать блок офферов для письма
 */
function buildOffersBlock(PDO $db): string {
    $offers = $db->query("SELECT id, title, slug, rate, amount_max, free_term_days, logo_url FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 5")->fetchAll();
    if (!$offers) return '';

    $block = '<table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0">';
    foreach ($offers as $o) {
        $logo = trim($o['logo_url'] ?? '');
        if ($logo && !str_starts_with((string)($logo), 'http')) {
            if (str_starts_with((string)($logo), '/public/')) $logo = substr($logo, 7);
            $logo = SITE_URL . $logo;
        }
        $free = $o['free_term_days'] > 0 ? '<span style="color:#059669;font-size:12px;font-weight:600"> • 0% на ' . $o['free_term_days'] . ' дней</span>' : '';
        $clickUrl = SITE_URL . '/click/' . $o['id'];
        $offerUrl = SITE_URL . '/offer/' . $o['slug'];

        $block .= '<tr><td style="padding:10px 0;border-bottom:1px solid #f3f4f6">';
        $block .= '<table width="100%" cellpadding="0" cellspacing="0"><tr>';
        $block .= '<td width="50" style="vertical-align:middle;padding-right:12px">';
        if ($logo) {
            $block .= '<img src="' . e($logo) . '" alt="' . e($o['title']) . '" width="44" height="44" style="border-radius:8px;background:#f9fafb;object-fit:contain">';
        } else {
            $block .= '<div style="width:44px;height:44px;background:#f3f4f6;border-radius:8px;text-align:center;line-height:44px;font-size:20px">🏦</div>';
        }
        $block .= '</td>';
        $block .= '<td style="vertical-align:middle">';
        $block .= '<a href="' . e($offerUrl) . '" style="color:#111827;font-weight:600;font-size:14px;text-decoration:none">' . e($o['title']) . '</a><br>';
        $block .= '<span style="color:#6b7280;font-size:12px">от ' . $o['rate'] . '% • до ' . number_format($o['amount_max'], 0, '', ' ') . ' ₽' . $free . '</span>';
        $block .= '</td>';
        $block .= '<td width="100" style="vertical-align:middle;text-align:right">';
        $block .= '<a href="' . e($clickUrl) . '" style="display:inline-block;background:#059669;color:#ffffff;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none">Оформить</a>';
        $block .= '</td>';
        $block .= '</tr></table></td></tr>';
    }
    $block .= '</table>';
    return $block;
}

/**
 * Собрать полный HTML письма
 */
function buildEmailHtml(string $bodyHtml, string $offersBlock, string $unsubLink, int $nlId, int $subId, bool $isTest = false): string {
    $body = $bodyHtml;
    
    // Блок офферов
    if (str_contains((string)($body), '{{offers}}')) {
        $body = str_replace('{{offers}}', $offersBlock, $body);
    } else {
        $body .= $offersBlock;
    }

    // Бренд-баннер
    $brandImageUrl = SITE_URL . '/images/kosmo-rassil.jpg';
    $body = '<div style="margin:-24px -24px 24px -24px;text-align:center;background:#f8fafc">'
        . '<img src="' . $brandImageUrl . '" alt="' . e(SITE_NAME) . '" style="display:block;width:100%;max-width:600px;height:auto;border:0;margin:0 auto">'
        . '</div>' . $body;

    // Трекинг кликов (только для реальных отправок)
    if (!$isTest) {
        $body = preg_replace_callback(
            '/<a\s([^>]*?)href=["\']( https?:\/\/[^"\'>]+)["\']([^>]*?)>/i',
            function($m) use ($nlId, $subId) {
                $url = $m[2];
                if (str_contains((string)($url), 'unsubscribe')) return $m[0];
                $trackUrl = SITE_URL . '/api/nl-click?n=' . $nlId . '&s=' . $subId . '&url=' . urlencode($url);
                return '<a ' . $m[1] . 'href="' . $trackUrl . '"' . $m[3] . '>';
            },
            $body
        );
    }

    // Тестовый баннер
    if ($isTest) {
        $body = '<div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:8px;padding:12px;margin-bottom:16px;text-align:center">'
            . '<span style="font-weight:bold;color:#92400e">⚠️ ТЕСТОВОЕ ПИСЬМО — только для проверки</span></div>' . $body;
    }

    // Подвал
    $body .= '<br><hr style="border:none;border-top:1px solid #eee;margin:24px 0">';
    $body .= '<p style="font-size:12px;color:#999;text-align:center">';
    $body .= 'Вы получили это письмо, потому что подписались на рассылку ' . e(SITE_NAME) . '.<br>';
    $body .= '<a href="' . $unsubLink . '" style="color:#999">Отписаться от рассылки</a></p>';

    // Трекинг-пиксель (только реальные)
    if (!$isTest) {
        $body .= '<img src="' . SITE_URL . '/api/nl-open?n=' . $nlId . '&s=' . $subId . '" width="1" height="1" style="display:none" alt="">';
    }

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#333;margin:0;padding:0;background:#f9fafb">'
        . '<div style="max-width:600px;margin:0 auto;padding:24px;background:#ffffff">'
        . $body
        . '</div></body></html>';
}

/**
 * Отправить одно письмо
 * @return array ['ok' => bool, 'error' => string|null]
 */
function sendOneEmail(string $to, string $subject, string $fullHtml, string $unsubLink = ''): array {
    $from = 'info@kosmozaim.ru';
    $fromName = SITE_NAME;

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: Kosmozaim Newsletter\r\n";
    if ($unsubLink) {
        $headers .= "List-Unsubscribe: <{$unsubLink}>\r\n";
        $headers .= "List-Unsubscribe-Post: List-Unsubscribe=One-Click\r\n";
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $result = sendMail($to, $subject, $fullHtml, true);
    return ['ok' => $result['ok'], 'error' => $result['error'] ?? null, 'method' => $result['method'] ?? ''];
}

<?php
/**
 * Единый модуль отправки почты с SMTP и fallback на mail()
 */

function getMailConfig(): array {
    static $config = null;
    if ($config !== null) return $config;

    $settings = getSiteSettings();
    $config = [
        'smtp_enabled' => !empty($settings['smtp_enabled']),
        'smtp_host' => $settings['smtp_host'] ?? '',
        'smtp_port' => (int)($settings['smtp_port'] ?? 465),
        'smtp_user' => $settings['smtp_user'] ?? '',
        'smtp_pass' => $settings['smtp_pass'] ?? '',
        'smtp_secure' => $settings['smtp_secure'] ?? 'ssl', // ssl, tls, or ''
        'mail_from' => $settings['mail_from'] ?? ($settings['smtp_user'] ?: 'info@kosmozaim.ru'),
        'mail_from_name' => $settings['mail_from_name'] ?? ($settings['site_name'] ?? 'Космозайм'),
        'contact_email' => $settings['contact_email'] ?? '',
    ];
    return $config;
}

/**
 * Отправка письма через SMTP (socket) или mail()
 */
function sendMail(string $to, string $subject, string $body, bool $isHtml = true, ?string $replyTo = null): array {
    $cfg = getMailConfig();

    if ($cfg['smtp_enabled'] && $cfg['smtp_host'] && $cfg['smtp_user']) {
        return sendSmtp($to, $subject, $body, $isHtml, $replyTo, $cfg);
    }

    return sendNativeMail($to, $subject, $body, $isHtml, $replyTo, $cfg);
}

/**
 * Отправка через встроенный mail()
 */
function sendNativeMail(string $to, string $subject, string $body, bool $isHtml, ?string $replyTo, array $cfg): array {
    $from = $cfg['mail_from'];
    $fromName = $cfg['mail_from_name'];

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= $isHtml ? "Content-Type: text/html; charset=UTF-8\r\n" : "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
    if ($replyTo) $headers .= "Reply-To: {$replyTo}\r\n";
    $headers .= "X-Mailer: KosmoEngine\r\n";

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $ok = @mail($to, $encodedSubject, $body, $headers, '-f' . $from);
    if ($ok) return ['ok' => true, 'method' => 'mail()'];

    $err = error_get_last();
    return ['ok' => false, 'method' => 'mail()', 'error' => $err['message'] ?? 'mail() failed'];
}

/**
 * Отправка через SMTP (pure socket, без внешних библиотек)
 */
function sendSmtp(string $to, string $subject, string $body, bool $isHtml, ?string $replyTo, array $cfg): array {
    $host = $cfg['smtp_host'];
    $port = $cfg['smtp_port'];
    $user = $cfg['smtp_user'];
    $pass = $cfg['smtp_pass'];
    $secure = $cfg['smtp_secure'];
    $from = $cfg['mail_from'];
    $fromName = $cfg['mail_from_name'];

    $prefix = '';
    if ($secure === 'ssl') $prefix = 'ssl://';

    $errno = 0; $errstr = '';
    $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
    if (!$fp) {
        return ['ok' => false, 'method' => 'smtp', 'error' => "Connect failed: {$errstr} ({$errno})"];
    }

    stream_set_timeout($fp, 15);

    $resp = function() use ($fp) {
        $data = '';
        while ($line = fgets($fp, 512)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };

    $send = function(string $cmd) use ($fp, $resp) {
        fwrite($fp, $cmd . "\r\n");
        return $resp();
    };

    try {
        $greeting = $resp();
        if (!str_starts_with((string)($greeting), '220')) throw new Exception("SMTP greeting failed: {$greeting}");

        $ehlo = $send('EHLO kosmozaim.ru');

        if ($secure === 'tls') {
            $starttls = $send('STARTTLS');
            if (!str_starts_with((string)($starttls), '220')) throw new Exception("STARTTLS failed: {$starttls}");
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $send('EHLO kosmozaim.ru');
        }

        $auth = $send('AUTH LOGIN');
        if (!str_starts_with((string)($auth), '334')) throw new Exception("AUTH failed: {$auth}");

        $r1 = $send(base64_encode($user));
        if (!str_starts_with((string)($r1), '334')) throw new Exception("User rejected: {$r1}");

        $r2 = $send(base64_encode($pass));
        if (!str_starts_with((string)($r2), '235')) throw new Exception("Password rejected: {$r2}");

        $r3 = $send("MAIL FROM:<{$from}>");
        if (!str_starts_with((string)($r3), '250')) throw new Exception("MAIL FROM rejected: {$r3}");

        $r4 = $send("RCPT TO:<{$to}>");
        if (!str_starts_with((string)($r4), '250')) throw new Exception("RCPT TO rejected: {$r4}");

        $r5 = $send('DATA');
        if (!str_starts_with((string)($r5), '354')) throw new Exception("DATA rejected: {$r5}");

        $boundary = 'kosmo_' . md5(uniqid());
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $contentType = $isHtml ? 'text/html' : 'text/plain';

        $message = "From: {$encodedFromName} <{$from}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$encodedSubject}\r\n";
        if ($replyTo) $message .= "Reply-To: {$replyTo}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "X-Mailer: KosmoEngine SMTP\r\n";
        $message .= "\r\n";
        $message .= chunk_split(base64_encode($body));
        $message .= "\r\n.";

        $r6 = $send($message);
        if (!str_starts_with((string)($r6), '250')) throw new Exception("Message rejected: {$r6}");

        $send('QUIT');
        fclose($fp);

        return ['ok' => true, 'method' => 'smtp'];
    } catch (Exception $e) {
        @fclose($fp);
        // Fallback to mail()
        $fallback = sendNativeMail($to, $subject, $body, $isHtml, $replyTo, $cfg);
        $fallback['smtp_error'] = $e->getMessage();
        $fallback['method'] = 'mail() (smtp fallback)';
        return $fallback;
    }
}

/**
 * Тест отправки почты
 */
function testMailDelivery(string $to): array {
    $subject = 'Тест почты — ' . (defined('SITE_NAME') ? SITE_NAME : 'KosmoEngine');
    $body = '<!DOCTYPE html><html><body style="font-family:-apple-system,sans-serif;max-width:500px;margin:0 auto;padding:20px">'
        . '<h2 style="color:#1a56db">✅ Тестовое письмо</h2>'
        . '<p>Если вы видите это письмо — почта работает корректно.</p>'
        . '<p style="color:#666;font-size:13px">Отправлено: ' . date('d.m.Y H:i:s') . '</p>'
        . '<p style="color:#999;font-size:12px">KosmoEngine Mail Test</p>'
        . '</body></html>';

    return sendMail($to, $subject, $body, true);
}


/**
 * Отправка письма с вложением файла
 */
function sendMailWithAttachment(string $to, string $subject, string $body, string $filePath, string $fileName, bool $isHtml = true): array {
    $cfg = getMailConfig();
    $from = $cfg['mail_from'] ?: 'info@kosmozaim.ru';
    $fromName = $cfg['mail_from_name'] ?: (defined('SITE_NAME') ? SITE_NAME : 'Космозайм');

    if (!file_exists($filePath)) {
        return ['ok' => false, 'error' => 'Файл не найден: ' . $filePath];
    }

    $boundary = 'kosmo_attach_' . md5(uniqid());
    $fileData = file_get_contents($filePath);
    $fileBase64 = chunk_split(base64_encode($fileData));

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
    $contentType = $isHtml ? 'text/html' : 'text/plain';

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

    $headers = "From: {$encodedFromName} <{$from}>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: KosmoEngine\r\n";

    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($body)) . "\r\n";

    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= $fileBase64 . "\r\n";
    $message .= "--{$boundary}--\r\n";

    $ok = @mail($to, $encodedSubject, $message, $headers, '-f' . $from);

    return $ok
        ? ['ok' => true, 'method' => 'mail() with attachment']
        : ['ok' => false, 'error' => 'mail() failed', 'method' => 'mail() with attachment'];
}

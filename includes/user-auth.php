<?php
function startUserSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 86400 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

function getUser(): ?array {
    startUserSession();
    return $_SESSION['user'] ?? null;
}

function getUserId(): ?int {
    $u = getUser();
    return $u ? (int)$u['id'] : null;
}

function isLoggedIn(): bool {
    return getUser() !== null;
}

function setUser(array $user): void {
    startUserSession();
    $_SESSION['user'] = ['id' => (int)$user['id'], 'email' => $user['email'], 'name' => $user['name'] ?? ''];
}

function logoutUser(): void {
    startUserSession();
    unset($_SESSION['user']);
}

function sendVerifyCode(string $email, string $code): bool {
    $subject = '=?UTF-8?B?' . base64_encode('Код подтверждения — ' . SITE_NAME) . '?=';
    $body = '<!DOCTYPE html><html><body style="font-family:-apple-system,sans-serif;color:#333;max-width:500px;margin:0 auto;padding:20px">'
        . '<h2 style="color:#1a56db">Код подтверждения</h2>'
        . '<p>Ваш код для подтверждения email на сайте ' . SITE_NAME . ':</p>'
        . '<div style="background:#f0f4ff;border:2px solid #1a56db;border-radius:12px;padding:20px;text-align:center;margin:20px 0">'
        . '<span style="font-size:32px;font-weight:700;letter-spacing:8px;color:#1a56db">' . $code . '</span></div>'
        . '<p style="color:#666;font-size:13px">Код действует 15 минут.</p>'
        . '<p style="color:#999;font-size:12px;margin-top:20px">⚠️ Если письмо попало в спам, переместите его во входящие.</p>'
        . '</body></html>';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode(SITE_NAME) . "?= <info@kosmozaim.ru>\r\n";
    return @mail($email, $subject, $body, $headers, '-finfo@kosmozaim.ru');
}

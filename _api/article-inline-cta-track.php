<?php
require_once __DIR__ . '/../includes/article-inline-cta.php';

$articleSlug = trim((string)($_GET['article_slug'] ?? ''));
$variant = trim((string)($_GET['variant'] ?? ''));
$offerId = (int)($_GET['offer_id'] ?? 0);
$eventType = trim((string)($_GET['event'] ?? 'impression'));

if ($articleSlug === '' || $offerId <= 0 || !in_array($variant, ['a', 'b'], true)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid params']);
    exit;
}

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = trim(explode(',', $ip)[0]);
$sessionKey = trim((string)($_COOKIE['article_inline_cta_sid'] ?? ''));
if ($sessionKey === '') {
    $sessionKey = md5($ip . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . date('Y-m-d'));
    setcookie('article_inline_cta_sid', $sessionKey, time() + 86400 * 30, '/');
    $_COOKIE['article_inline_cta_sid'] = $sessionKey;
}

if ($eventType !== 'impression') {
    echo json_encode(['ok' => false, 'error' => 'Unsupported event']);
    exit;
}

$ok = trackArticleInlineCtaImpression($articleSlug, $offerId, $variant, $ip, $sessionKey);
echo json_encode(['ok' => $ok]);

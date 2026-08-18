<?php
/**
 * Скачивание логотипа оффера по внешнему URL
 * POST: { url: string, title?: string }
 */
require_once __DIR__ . '/../../includes/leads-su-api.php';
header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$url = trim((string)($data['url'] ?? ''));
$title = trim((string)($data['title'] ?? ''));

if ($url === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Укажите URL логотипа']);
    exit;
}

if (!preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Нужна внешняя ссылка http/https']);
    exit;
}

$local = leadsSuDownloadLogo($url, $title !== '' ? $title : 'offer-logo');
if ($local === '' || preg_match('#^https?://#i', $local)) {
    http_response_code(422);
    echo json_encode(['error' => 'Не удалось скачать логотип по этой ссылке']);
    exit;
}

echo json_encode([
    'success' => true,
    'logoUrl' => $local,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

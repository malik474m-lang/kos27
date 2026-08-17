<?php
/**
 * AI-чат для посетителей сайта
 * POST /api/chat
 * Body: { message: string, history: [{role, content}] }
 */
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

// Проверяем что чат включён
$settingsFile = __DIR__ . '/../data/site-settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
if (empty($settings['chat_enabled'])) {
    echo json_encode(['error' => 'Чат отключён']);
    exit;
}

// Rate limiting по IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ip = trim(explode(',', $ip)[0]);
$rateLimitFile = __DIR__ . '/../data/chat-ratelimit.json';
$limits = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];
$now = time();
// Очистка старых записей
foreach ($limits as $k => $v) { if ($v['t'] < $now - 3600) unset($limits[$k]); }
$ipKey = md5($ip);
$ipData = $limits[$ipKey] ?? ['t' => $now, 'c' => 0];
if ($now - $ipData['t'] > 3600) { $ipData = ['t' => $now, 'c' => 0]; }
$maxPerHour = (int)($settings['chat_rate_limit'] ?? 30);
if ($ipData['c'] >= $maxPerHour) {
    echo json_encode(['error' => 'Слишком много запросов. Попробуйте позже.']);
    exit;
}
$ipData['c']++;
$limits[$ipKey] = $ipData;
@file_put_contents($rateLimitFile, json_encode($limits));

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$userMessage = trim((string)($data['message'] ?? ''));
$history = is_array($data['history'] ?? null) ? $data['history'] : [];

if ($userMessage === '' || mb_strlen($userMessage) > 2000) {
    echo json_encode(['error' => 'Введите сообщение (до 2000 символов)']);
    exit;
}

// API ключ
$apiKey = $settings['odirouter_api_key'] ?? '';
if (!$apiKey) $apiKey = $settings['odirouter_image_api_key'] ?? '';
$model = $settings['chat_model'] ?? 'free-gemini-2.5-flash';

if (!$apiKey) {
    echo json_encode(['error' => 'AI не настроен']);
    exit;
}

// Системный промпт
$siteName = $settings['site_name'] ?? 'Космозайм';
$systemPrompt = $settings['chat_system_prompt'] ?? "Ты — умный помощник на финансовом сайте «{$siteName}». 
Помогаешь посетителям разобраться в займах, кредитах, кредитных и дебетовых картах.

Правила:
- Отвечай кратко и по делу (2-4 предложения)
- Используй только русский язык
- Рекомендуй сравнивать предложения на сайте {$siteName}
- Предлагай воспользоваться калькулятором (/calculator) и фильтрами
- Не давай конкретных финансовых советов — только общую информацию
- Напоминай проверять лицензию организации в реестре ЦБ РФ
- Не обсуждай темы не связанные с финансами — вежливо перенаправляй
- Не выдумывай конкретные ставки, если не знаешь точных данных";

// Формируем сообщения (последние 10 из истории)
$messages = [['role' => 'system', 'content' => $systemPrompt]];
$historySlice = array_slice($history, -10);
foreach ($historySlice as $msg) {
    if (in_array($msg['role'] ?? '', ['user', 'assistant'])) {
        $messages[] = [
            'role' => $msg['role'],
            'content' => mb_substr((string)($msg['content'] ?? ''), 0, 1000),
        ];
    }
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

// Вызов OdiRouter
$ch = curl_init('https://api.odirouter.ai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 500,
    ], JSON_UNESCAPED_UNICODE),
]);

$response = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'Ошибка соединения']);
    exit;
}

if ($code >= 500) {
    echo json_encode(['error' => 'Сервис временно недоступен, попробуйте позже']);
    exit;
}

if ($code < 200 || $code >= 300) {
    echo json_encode(['error' => 'Ошибка AI сервиса']);
    exit;
}

$respData = json_decode($response, true);
$reply = $respData['choices'][0]['message']['content'] ?? '';

if (!$reply) {
    echo json_encode(['error' => 'Не удалось получить ответ']);
    exit;
}

echo json_encode([
    'success' => true,
    'reply' => trim($reply),
], JSON_UNESCAPED_UNICODE);

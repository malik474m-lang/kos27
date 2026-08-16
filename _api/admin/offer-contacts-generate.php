<?php
/**
 * API для генерации справочной информации оффера через ИИ
 */

$data = json_decode(file_get_contents('php://input'), true);
$offerTitle = $data['title'] ?? '';
$offerCategory = $data['category'] ?? 'microloans';

if (!$offerTitle) {
    echo json_encode(['error' => 'Название оффера обязательно']);
    exit;
}

if (!YANDEX_GPT_API_KEY || !YANDEX_FOLDER_ID) {
    echo json_encode(['error' => 'Не настроен Yandex GPT (API ключ или Folder ID)']);
    exit;
}

$categoryLabels = [
    'microloans' => 'МФО (микрофинансовая организация)',
    'credits' => 'банк',
    'credit_cards' => 'банк',
    'debit_cards' => 'банк',
];
$categoryLabel = $categoryLabels[$offerCategory] ?? 'финансовая организация';

$prompt = "Найди реальную информацию о компании «{$offerTitle}» — это {$categoryLabel} в России.

Верни ТОЛЬКО JSON (без текста и markdown) с полями:
{
  \"phone\": \"телефон горячей линии или null\",
  \"address\": \"юридический адрес или null\",
  \"trademark\": \"полное юридическое название (ООО, АО) или null\",
  \"license\": \"номер лицензии ЦБ РФ или null\"
}";

$response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt-lite/latest',
            'completionOptions' => ['stream' => false, 'temperature' => 0.1, 'maxTokens' => 300],
            'messages' => [
                ['role' => 'system', 'text' => 'Отвечай ТОЛЬКО валидным JSON. Без markdown, без пояснений, без ```json```. Только JSON объект.'],
                ['role' => 'user', 'text' => $prompt],
            ],
        ]),
        'timeout' => 30,
    ],
]));

if (!$response) {
    $err = error_get_last();
    echo json_encode(['error' => 'Ошибка запроса к Yandex GPT', 'detail' => $err['message'] ?? '']);
    exit;
}

$result = json_decode($response, true);
$text = $result['result']['alternatives'][0]['message']['text'] ?? '';

if (!$text) {
    echo json_encode(['error' => 'Пустой ответ от ИИ', 'raw' => $result]);
    exit;
}

// Очищаем ответ от markdown и мусора
$text = trim($text);
$text = preg_replace('/^```(json)?\s*/i', '', $text);
$text = preg_replace('/\s*```\s*$/', '', $text);
$text = trim($text);

// Пробуем извлечь JSON из текста если он обёрнут
if (!str_starts_with($text, '{')) {
    if (preg_match('/\{[^}]+\}/s', $text, $m)) {
        $text = $m[0];
    }
}

$contacts = json_decode($text, true);

if (!$contacts || !is_array($contacts)) {
    echo json_encode(['error' => 'Не удалось распознать ответ ИИ', 'raw' => $text]);
    exit;
}

// Убираем строку "null"
foreach (['phone', 'address', 'trademark', 'license'] as $field) {
    if (isset($contacts[$field]) && (strtolower((string)$contacts[$field]) === 'null' || $contacts[$field] === '')) {
        $contacts[$field] = null;
    }
}

echo json_encode([
    'success' => true,
    'contacts' => [
        'phone' => $contacts['phone'] ?? null,
        'address' => $contacts['address'] ?? null,
        'trademark' => $contacts['trademark'] ?? null,
        'license' => $contacts['license'] ?? null,
    ],
]);

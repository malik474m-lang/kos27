<?php
/**
 * Генерация справочной информации оффера через Yandex GPT
 * Важно: промпт максимально строгий, чтобы модель не придумывала данные.
 */

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$offerTitle = trim((string)($data['title'] ?? ''));
$offerCategory = trim((string)($data['category'] ?? 'microloans'));

if ($offerTitle === '') {
    echo json_encode(['error' => 'Название оффера обязательно']);
    exit;
}

if (!defined('YANDEX_GPT_API_KEY') || !YANDEX_GPT_API_KEY || !defined('YANDEX_FOLDER_ID') || !YANDEX_FOLDER_ID) {
    echo json_encode(['error' => 'Не настроен Yandex GPT']);
    exit;
}

$categoryLabels = [
    'microloans' => 'микрофинансовая организация',
    'credits' => 'банк',
    'credit_cards' => 'банк',
    'debit_cards' => 'банк',
];
$categoryLabel = $categoryLabels[$offerCategory] ?? 'финансовая организация';

$prompt = <<<PROMPT
Дай Справочную информацию о {$offerTitle}, Телефон, Лицензия ЦБ, Торговая марка, Адрес.

Контекст: это {$categoryLabel} в России.

Нужен только JSON-объект без markdown, без комментариев, без пояснений, без текста до и после JSON.
Строго в формате:
{
  "phone": null,
  "license": null,
  "trademark": null,
  "address": null
}

Правила:
1. Не выдумывай данные.
2. Если не уверен хотя бы на 95%, ставь null.
3. Не придумывай номер лицензии, телефон, торговую марку или адрес.
4. phone — только телефон поддержки или горячей линии.
5. license — только номер лицензии ЦБ РФ без дополнительных пояснений.
6. trademark — только точное юридическое название компании, если оно известно.
7. address — только юридический адрес, если он известен точно.
8. Если название оффера похоже на бренд, но точную компанию определить нельзя, все поля должны быть null.
9. Ответ только JSON.
PROMPT;

$response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt-lite/latest',
            'completionOptions' => [
                'stream' => false,
                'temperature' => 0,
                'maxTokens' => 250,
            ],
            'messages' => [
                ['role' => 'system', 'text' => 'Ты возвращаешь только валидный JSON-объект. Если точных данных нет — ставишь null. Ничего не придумываешь.'],
                ['role' => 'user', 'text' => $prompt],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'timeout' => 30,
    ],
]));

if (!$response) {
    $err = error_get_last();
    echo json_encode(['error' => 'Ошибка запроса к Yandex GPT', 'detail' => $err['message'] ?? '']);
    exit;
}

$respData = json_decode($response, true);
$text = trim((string)($respData['result']['alternatives'][0]['message']['text'] ?? ''));

if ($text === '') {
    echo json_encode(['error' => 'Пустой ответ от ИИ']);
    exit;
}

$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
$text = preg_replace('/\s*```$/', '', $text);
$text = trim($text);

if (!str_starts_with($text, '{') && preg_match('/\{.*\}/su', $text, $m)) {
    $text = $m[0];
}

$contacts = json_decode($text, true);
if (!is_array($contacts)) {
    echo json_encode(['error' => 'Не удалось распознать ответ от ИИ', 'raw' => $text]);
    exit;
}

$normalized = [];
foreach (['phone', 'address', 'trademark', 'license'] as $field) {
    $value = $contacts[$field] ?? null;
    if (is_string($value)) {
        $value = trim($value);
        if ($value === '' || mb_strtolower($value) === 'null' || mb_strtolower($value) === 'неизвестно') {
            $value = null;
        }
    }
    $normalized[$field] = $value;
}

echo json_encode([
    'success' => true,
    'contacts' => $normalized,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

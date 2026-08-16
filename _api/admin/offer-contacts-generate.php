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

$categoryLabels = [
    'microloans' => 'микрофинансовая организация (МФО)',
    'credits' => 'банк',
    'credit_cards' => 'банк',
    'debit_cards' => 'банк',
];
$categoryLabel = $categoryLabels[$offerCategory] ?? 'финансовая организация';

$prompt = "Ты — помощник для финансового сайта. Найди реальную справочную информацию о компании «{$offerTitle}» ({$categoryLabel} в России).

Верни JSON с полями:
- phone: телефон горячей линии (формат: 8-800-XXX-XX-XX или +7...)
- address: юридический адрес головного офиса
- trademark: полное название юридического лица (ООО, АО и т.д.)
- license: номер лицензии ЦБ РФ (для МФО формат: №XXXXXXX, для банков: №XXXX)

Если точных данных нет — напиши null для этого поля. Не выдумывай!

Ответ строго в формате JSON без пояснений:";

$settings = getSiteSettings();
$apiKey = $settings['yandex_gpt_api_key'] ?? '';
$folderId = $settings['yandex_folder_id'] ?? '';

if (!$apiKey || !$folderId) {
    echo json_encode(['error' => 'Не настроен Yandex GPT API']);
    exit;
}

$response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key {$apiKey}\r\n",
        'content' => json_encode([
            'modelUri' => "gpt://{$folderId}/yandexgpt-lite",
            'completionOptions' => ['stream' => false, 'temperature' => 0.3, 'maxTokens' => 500],
            'messages' => [
                ['role' => 'system', 'text' => 'Ты возвращаешь только валидный JSON без markdown и пояснений.'],
                ['role' => 'user', 'text' => $prompt],
            ],
        ]),
        'timeout' => 30,
    ],
]));

if (!$response) {
    echo json_encode(['error' => 'Ошибка запроса к Yandex GPT']);
    exit;
}

$result = json_decode($response, true);
$text = $result['result']['alternatives'][0]['message']['text'] ?? '';

// Извлекаем JSON из ответа
$text = preg_replace('/^```json\s*/i', '', $text);
$text = preg_replace('/```\s*$/', '', $text);
$text = trim($text);

$contacts = json_decode($text, true);

if (!$contacts || !is_array($contacts)) {
    echo json_encode(['error' => 'Не удалось распознать ответ ИИ', 'raw' => $text]);
    exit;
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

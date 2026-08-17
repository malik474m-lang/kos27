<?php
require_once __DIR__ . "/../../includes/ai-compat.php";
/**
 * Генерация справочной информации оффера через AI.
 */

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$offerTitle = trim((string)($data['title'] ?? ''));
$offerCategory = trim((string)($data['category'] ?? 'microloans'));

if ($offerTitle === '') {
    echo json_encode(['error' => 'Название оффера обязательно']);
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
Если ты знаешь бренд или компанию по общеизвестной справочной информации, верни известные поля.
Если какое-то поле неизвестно — верни null.

Ответ нужен в JSON:
{
  "phone": null,
  "license": null,
  "trademark": null,
  "address": null
}

Правила:
1. phone — телефон поддержки или горячей линии.
2. license — номер лицензии ЦБ РФ без пояснений.
3. trademark — торговая марка или точное юрлицо, если известно.
4. address — юридический адрес, если известен.
5. Можно заполнять только часть полей.
6. Не добавляй markdown и комментарии.
7. Ответ только JSON.
PROMPT;

$systemPrompt = 'Ты возвращаешь краткую справочную информацию о российских финансовых брендах. Старайся вернуть JSON. Если поле неизвестно — null. Не используй markdown.';

$text = kosmozaimAIComplete($systemPrompt, $prompt);

if (!$text) {
    echo json_encode(['error' => 'Не удалось получить ответ от AI. Проверьте настройки провайдеров.']);
    exit;
}

$text = preg_replace('/^```(?:json)?\s*/iu', '', $text);
$text = preg_replace('/\s*```$/u', '', $text);
$text = trim($text);

function offerInfoNormalize($value): ?string {
    if ($value === null) return null;
    if (is_array($value) || is_object($value)) return null;
    $value = trim((string)$value);
    if ($value === '') return null;
    $bad = ['null', 'неизвестно', 'не указано', 'нет данных', 'n/a'];
    if (in_array(mb_strtolower($value), $bad, true)) return null;
    return $value;
}

function offerInfoParseFromText(string $text): array {
    $result = [
        'phone' => null,
        'license' => null,
        'trademark' => null,
        'address' => null,
    ];

    if (preg_match('/(?:телефон|phone)\s*[:\-]\s*([^\n\r]+)/iu', $text, $m)) {
        $result['phone'] = trim($m[1]);
    }
    if (preg_match('/(?:лицензия(?:\s*цб)?|license)\s*[:\-]\s*([^\n\r]+)/iu', $text, $m)) {
        $result['license'] = trim($m[1]);
    }
    if (preg_match('/(?:торговая марка|trademark|бренд|юр(?:идическое)?\s*лицо)\s*[:\-]\s*([^\n\r]+)/iu', $text, $m)) {
        $result['trademark'] = trim($m[1]);
    }
    if (preg_match('/(?:адрес|address)\s*[:\-]\s*([^\n\r]+)/iu', $text, $m)) {
        $result['address'] = trim($m[1]);
    }

    if (!$result['phone'] && preg_match('/(?:8\s*800|\+7)[\d\s\-\(\)]{7,}/u', $text, $m)) {
        $result['phone'] = trim($m[0]);
    }

    return $result;
}

$contacts = null;
if (preg_match('/\{.*\}/su', $text, $m)) {
    $contacts = json_decode($m[0], true);
}
if (!is_array($contacts)) {
    $contacts = offerInfoParseFromText($text);
}

$normalized = [
    'phone' => offerInfoNormalize($contacts['phone'] ?? null),
    'license' => offerInfoNormalize($contacts['license'] ?? null),
    'trademark' => offerInfoNormalize($contacts['trademark'] ?? null),
    'address' => offerInfoNormalize($contacts['address'] ?? null),
];

$filled = 0;
foreach ($normalized as $value) {
    if ($value !== null) $filled++;
}

echo json_encode([
    'success' => true,
    'contacts' => $normalized,
    'filled' => $filled,
    'raw' => $text,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

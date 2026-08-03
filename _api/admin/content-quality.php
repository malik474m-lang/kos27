<?php
requireAdmin();
require_once __DIR__ . '/../../includes/content-quality.php';

header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$action = trim((string)($data['action'] ?? 'analyze'));
$entity = trim((string)($data['entity'] ?? 'generic'));
$title = trim((string)($data['title'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$content = (string)($data['content'] ?? '');
$field = trim((string)($data['field'] ?? 'content'));

if ($action === 'analyze') {
    echo json_encode(['success' => true, 'analysis' => cq_analyze($content, $entity, $title, $description)]);
    exit;
}

if ($action === 'improve') {
    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Пустой текст']);
        exit;
    }

    $improved = null;
    $provider = 'template';

    if (defined('YANDEX_GPT_API_KEY') && YANDEX_GPT_API_KEY && defined('YANDEX_FOLDER_ID') && YANDEX_FOLDER_ID) {
        $prompt = "Улучши текст для финансового сайта. "
            . "Сущность: {$entity}. Поле: {$field}. Заголовок: {$title}. Описание: {$description}. "
            . "Сделай текст более полезным, менее шаблонным, убери markdown-мусор, штампы, повторы, слишком рекламные формулировки. "
            . "Сохрани фактический смысл. Если в тексте есть HTML, верни аккуратный HTML без обёрток <html><body>. Если HTML нет — верни просто улучшенный текст. Без пояснений.\n\nТекст:\n" . $content;

        $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
                'content' => json_encode([
                    'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
                    'completionOptions' => ['stream' => false, 'temperature' => 0.35, 'maxTokens' => 4000],
                    'messages' => [
                        ['role' => 'system', 'text' => 'Ты редактор контента финансового сайта. Отвечаешь только улучшенным текстом без пояснений.'],
                        ['role' => 'user', 'text' => $prompt],
                    ],
                ]),
                'timeout' => 45,
                'ignore_errors' => true,
            ],
        ]));

        if ($response) {
            $json = json_decode($response, true);
            $text = trim((string)($json['result']['alternatives'][0]['message']['text'] ?? ''));
            if ($text !== '') {
                $improved = $text;
                $provider = 'YandexGPT';
            }
        }
    }

    if ($improved === null) {
        $improved = cq_improve_fallback($content, $entity, $title, $description);
    }

    echo json_encode([
        'success' => true,
        'provider' => $provider,
        'improved' => $improved,
        'analysis_before' => cq_analyze($content, $entity, $title, $description),
        'analysis_after' => cq_analyze($improved, $entity, $title, $description),
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);

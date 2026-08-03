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

    $analysisBefore = cq_analyze($content, $entity, $title, $description);
    $recommendations = $analysisBefore['recommendations'] ?? [];
    $issuesList = array_map(fn($i) => $i['msg'] ?? '', $analysisBefore['issues'] ?? []);
    $improved = null;
    $provider = 'template';

    if (defined('YANDEX_GPT_API_KEY') && YANDEX_GPT_API_KEY && defined('YANDEX_FOLDER_ID') && YANDEX_FOLDER_ID) {
        $prompt = "Улучши текст для финансового сайта с учётом конкретных замечаний. "
            . "Сущность: {$entity}. Поле: {$field}. Заголовок: {$title}. Описание: {$description}. "
            . "Минимум слов для этого типа контента: " . cq_min_words($entity) . ". "
            . "Нужно обязательно устранить замечания: " . implode('; ', array_filter($issuesList)) . ". "
            . "Нужно обязательно выполнить рекомендации: " . implode('; ', array_filter($recommendations)) . ". "
            . "Сделай текст более полезным, менее шаблонным, убери markdown-мусор, повторы и слишком рекламные фразы. "
            . "Если в тексте нет упоминания темы, естественно добавь формулировку заголовка в текст. "
            . "Верни только улучшенный текст без пояснений. Если в исходнике есть HTML — верни аккуратный HTML без <html><body>. Если HTML нет — верни просто текст.\n\nИсходный текст:\n" . $content;

        $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
                'content' => json_encode([
                    'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
                    'completionOptions' => ['stream' => false, 'temperature' => 0.3, 'maxTokens' => 5000],
                    'messages' => [
                        ['role' => 'system', 'text' => 'Ты редактор контента финансового сайта. Строго устраняешь перечисленные проблемы и возвращаешь только улучшенный текст без пояснений.'],
                        ['role' => 'user', 'text' => $prompt],
                    ],
                ]),
                'timeout' => 45,
                'ignore_errors' => true,
            ],
        ]));

        if ($response) {
            $json = json_decode($response, true);
            $respText = trim((string)($json['result']['alternatives'][0]['message']['text'] ?? ''));
            if ($respText !== '') {
                $improved = cq_strip_markdown($respText);
                $provider = 'YandexGPT';
            }
        }
    }

    if ($improved === null) {
        $improved = cq_improve_fallback($content, $entity, $title, $description);
    }

    $improved = cq_strip_markdown($improved);
    $improved = cq_enforce_recommendations($improved, $analysisBefore, $entity, $title, $description);
    $analysisAfter = cq_analyze($improved, $entity, $title, $description);

    // Если ИИ не помог — применяем ещё один детерминированный проход
    if (($analysisAfter['score'] ?? 0) < ($analysisBefore['score'] ?? 0) || ($analysisAfter['score'] ?? 0) < 80) {
        $fallbackImproved = cq_enforce_recommendations(cq_improve_fallback($content, $entity, $title, $description), $analysisBefore, $entity, $title, $description);
        $fallbackAnalysis = cq_analyze($fallbackImproved, $entity, $title, $description);
        if (($fallbackAnalysis['score'] ?? 0) > ($analysisAfter['score'] ?? 0)) {
            $improved = $fallbackImproved;
            $analysisAfter = $fallbackAnalysis;
            if ($provider !== 'YandexGPT') {
                $provider = 'template';
            } else {
                $provider = 'YandexGPT + fallback';
            }
        }
    }

    echo json_encode([
        'success' => true,
        'provider' => $provider,
        'improved' => $improved,
        'analysis_before' => $analysisBefore,
        'analysis_after' => $analysisAfter,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);

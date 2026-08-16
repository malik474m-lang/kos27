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
$targetScore = max(1, min(100, (int)($data['targetScore'] ?? 80)));
$maxPasses = max(1, min(5, (int)($data['maxPasses'] ?? 3)));
$context = is_array($data['context'] ?? null) ? $data['context'] : [];

function cq_ai_improve_once(string $content, string $entity, string $field, string $title, string $description, array $analysisBefore, int $targetScore, array $context = []): array {
    $recommendations = $analysisBefore['recommendations'] ?? [];
    $issuesList = array_map(fn($i) => $i['msg'] ?? '', $analysisBefore['issues'] ?? []);
    $improved = null;
    $provider = 'template';

    if (defined('YANDEX_GPT_API_KEY') && YANDEX_GPT_API_KEY && defined('YANDEX_FOLDER_ID') && YANDEX_FOLDER_ID) {
        if ($entity === 'offer' && $field === 'description') {
            $contextBits = [];
            if (!empty($context['category'])) $contextBits[] = 'категория: ' . $context['category'];
            if (!empty($context['amountMin']) || !empty($context['amountMax'])) $contextBits[] = 'сумма: ' . ($context['amountMin'] ?? '') . ' - ' . ($context['amountMax'] ?? '');
            if (!empty($context['termMinDays']) || !empty($context['termMaxDays'])) $contextBits[] = 'срок: ' . ($context['termMinDays'] ?? '') . ' - ' . ($context['termMaxDays'] ?? '') . ' дней';
            if (!empty($context['rate'])) $contextBits[] = 'ставка: ' . $context['rate'] . '% ' . (($context['rateUnit'] ?? 'day') === 'year' ? 'в год' : 'в день');
            if (!empty($context['freeTermDays'])) $contextBits[] = 'льготный период: ' . $context['freeTermDays'] . ' дней';
            $contextLine = implode('; ', $contextBits);

            $prompt = "Перепиши описание конкретного финансового оффера для карточки на сайте. "
                . "Название оффера: {$title}. "
                . ($contextLine ? "Параметры: {$contextLine}. " : '')
                . "Нужно получить уникальное, не шаблонное описание длиной 45-90 слов. "
                . "Не начинай со слов 'мы собрали', 'на этой странице', 'актуальные условия', 'подходит для'. "
                . "Не пиши общие фразы, одинаковые для всех офферов. Опирайся на конкретные параметры и тему оффера. "
                . "Сделай 2-4 предложения, нейтральный тон, без markdown, без списков, без HTML. "
                . "Обязательно устрани замечания: " . implode('; ', array_filter($issuesList)) . ". "
                . "Учитывай рекомендации: " . implode('; ', array_filter($recommendations)) . ". "
                . "Если в исходнике есть полезные детали — сохрани их, но переформулируй. "
                . "Верни только итоговый текст.\n\nИсходный текст:\n" . $content;
        } else {
            $prompt = "Улучши текст для финансового сайта с учётом конкретных замечаний. "
                . "Сущность: {$entity}. Поле: {$field}. Заголовок: {$title}. Описание: {$description}. "
                . "Целевой score качества: не ниже {$targetScore}. "
                . "Минимум слов для этого типа контента: " . cq_min_words($entity) . ". "
                . "Нужно обязательно устранить замечания: " . implode('; ', array_filter($issuesList)) . ". "
                . "Нужно обязательно выполнить рекомендации: " . implode('; ', array_filter($recommendations)) . ". "
                . "Сделай текст более полезным, менее шаблонным, убери markdown-мусор, повторы и слишком рекламные фразы. "
                . "Если в тексте нет упоминания темы, естественно добавь формулировку заголовка в текст. "
                . "Верни только улучшенный текст без пояснений. Если в исходнике есть HTML — верни аккуратный HTML без <html><body>. Если HTML нет — верни просто текст.\n\nИсходный текст:\n" . $content;
        }

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
        $improved = cq_improve_fallback($content, $entity, $title, $description, $context);
    }

    $improved = cq_strip_markdown($improved);
    $improved = cq_enforce_recommendations($improved, $analysisBefore, $entity, $title, $description);
    $analysisAfter = cq_analyze($improved, $entity, $title, $description);

    if (($analysisAfter['score'] ?? 0) < ($analysisBefore['score'] ?? 0) || ($analysisAfter['score'] ?? 0) < 80) {
        $fallbackImproved = cq_enforce_recommendations(cq_improve_fallback($content, $entity, $title, $description, $context), $analysisBefore, $entity, $title, $description);
        $fallbackAnalysis = cq_analyze($fallbackImproved, $entity, $title, $description);
        if (($fallbackAnalysis['score'] ?? 0) > ($analysisAfter['score'] ?? 0)) {
            $improved = $fallbackImproved;
            $analysisAfter = $fallbackAnalysis;
            $provider = ($provider === 'YandexGPT') ? 'YandexGPT + fallback' : 'template';
        }
    }

    return [
        'provider' => $provider,
        'improved' => $improved,
        'analysis_after' => $analysisAfter,
    ];
}

if ($action === 'analyze') {
    echo json_encode(['success' => true, 'analysis' => cq_analyze($content, $entity, $title, $description)]);
    exit;
}

if ($action === 'cleanup_only') {
    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Пустой текст']);
        exit;
    }
    $analysisBefore = cq_analyze($content, $entity, $title, $description);
    $cleaned = cq_strip_markdown($content);
    // для SEO-текста убираем HTML в plain text, если это city/tag SEO, но здесь оставим структуру
    $cleaned = preg_replace('/^```\s*\w*\s*\n?/mi', '', $cleaned);
    $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
    $cleaned = trim($cleaned);
    $analysisAfter = cq_analyze($cleaned, $entity, $title, $description);
    echo json_encode([
        'success' => true,
        'provider' => 'cleanup_only',
        'improved' => $cleaned,
        'analysis_before' => $analysisBefore,
        'analysis_after' => $analysisAfter,
        'target_score' => null,
        'passes' => [['pass' => 1, 'provider' => 'cleanup_only', 'score' => (int)($analysisAfter['score'] ?? 0)]],
        'reached_target' => null,
    ]);
    exit;
}

if ($action === 'improve' || $action === 'improve_until') {
    if ($content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Пустой текст']);
        exit;
    }

    $analysisBefore = cq_analyze($content, $entity, $title, $description);
    $currentText = $content;
    $currentAnalysis = $analysisBefore;
    $usedProviders = [];
    $passes = [];

    $passesToRun = $action === 'improve_until' ? $maxPasses : 1;

    for ($i = 1; $i <= $passesToRun; $i++) {
        $result = cq_ai_improve_once($currentText, $entity, $field, $title, $description, $currentAnalysis, $targetScore, $context);
        $currentText = $result['improved'];
        $currentAnalysis = $result['analysis_after'];
        $usedProviders[] = $result['provider'];
        $passes[] = [
            'pass' => $i,
            'provider' => $result['provider'],
            'score' => (int)($currentAnalysis['score'] ?? 0),
        ];
        if (($currentAnalysis['score'] ?? 0) >= $targetScore) {
            break;
        }
    }

    echo json_encode([
        'success' => true,
        'provider' => implode(' → ', array_values(array_unique($usedProviders))),
        'improved' => $currentText,
        'analysis_before' => $analysisBefore,
        'analysis_after' => $currentAnalysis,
        'target_score' => $targetScore,
        'passes' => $passes,
        'reached_target' => (($currentAnalysis['score'] ?? 0) >= $targetScore),
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);

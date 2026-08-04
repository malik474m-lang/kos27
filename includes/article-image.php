<?php
/**
 * Генерация обложки статьи через YandexART
 */

function generateArticleCoverImage(string $title): string {
    $title = trim($title);
    if ($title === '' || !defined('YANDEX_GPT_API_KEY') || !YANDEX_GPT_API_KEY || !defined('YANDEX_FOLDER_ID') || !YANDEX_FOLDER_ID) {
        return '';
    }

    // Шаг 1: Генерация детального промпта через YandexGPT
    $detailedPrompt = generateDetailedImagePrompt($title);
    
    if (empty($detailedPrompt)) {
        // Fallback на простой промпт
        $detailedPrompt = "нарисуй 16:9 $title";
    }

    // Шаг 2: Генерация картинки через YandexART
    $artCtx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'art://' . YANDEX_FOLDER_ID . '/yandex-art/latest',
            'generationOptions' => [
                'seed' => mt_rand(1, 999999),
                'aspectRatio' => ['widthRatio' => '16', 'heightRatio' => '9']
            ],
            'messages' => [
                ['weight' => '1', 'text' => $detailedPrompt],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'timeout' => 45,
        'ignore_errors' => true,
    ]]);

    $artResponse = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync', false, $artCtx);
    if (!$artResponse) return '';

    $artData = json_decode($artResponse, true);
    $opId = $artData['id'] ?? null;
    if (!$opId) return '';

    // Ожидаем генерацию картинки
    for ($i = 0; $i < 18; $i++) {
        sleep(4);
        $checkCtx = stream_context_create(['http' => [
            'header' => "Authorization: Api-Key " . YANDEX_GPT_API_KEY,
            'timeout' => 15,
            'ignore_errors' => true,
        ]]);
        $checkResponse = @file_get_contents("https://operation.api.cloud.yandex.net:443/operations/$opId", false, $checkCtx);
        if (!$checkResponse) continue;
        $checkData = json_decode($checkResponse, true);

        if (($checkData['done'] ?? false) && !empty($checkData['response']['image'])) {
            $imageData = base64_decode($checkData['response']['image']);
            if (!$imageData) return '';
            $fileName = 'article-' . time() . '-' . mt_rand(1000, 9999) . '.jpeg';
            $dirPath = __DIR__ . '/../images/articles';
            if (!is_dir($dirPath)) @mkdir($dirPath, 0755, true);
            if (@file_put_contents("$dirPath/$fileName", $imageData) === false) return '';
            return "/images/articles/$fileName";
        }

        if (($checkData['done'] ?? false) && !empty($checkData['error'])) {
            return '';
        }
    }

    return '';
}

/**
 * Генерация детального промпта для картинки через YandexGPT
 */
function generateDetailedImagePrompt(string $title): string {
    if (!defined('YANDEX_GPT_API_KEY') || !YANDEX_GPT_API_KEY || !defined('YANDEX_FOLDER_ID') || !YANDEX_FOLDER_ID) {
        return '';
    }

    $systemPrompt = "You are an expert at creating detailed image generation prompts. Create a detailed, descriptive prompt in English for generating a professional illustration. The prompt should describe the visual elements, style, colors, composition, and atmosphere. Do not include any explanations or text about what you're doing - just output the prompt itself.";
    
    $userPrompt = "Create a detailed image generation prompt for this topic (translate to English if needed): '$title'. The image should be 16:9 aspect ratio, professional quality, suitable for a financial article cover. Describe the visual elements, style, colors, and composition in detail.";

    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
            'completionOptions' => [
                'stream' => false,
                'temperature' => 0.7,
                'maxTokens' => 800,
            ],
            'messages' => [
                ['role' => 'system', 'text' => $systemPrompt],
                ['role' => 'user', 'text' => $userPrompt],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'timeout' => 30,
        'ignore_errors' => true,
    ]]);

    $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, $ctx);
    if (!$response) return '';

    $data = json_decode($response, true);
    $text = trim((string)($data['result']['alternatives'][0]['message']['text'] ?? ''));
    
    if (empty($text)) return '';
    
    // Убираем лишние кавычки и форматирование
    $text = trim($text, '"\'');
    $text = preg_replace('/^["\']+|["\']+$/', '', $text);
    
    return $text;
}

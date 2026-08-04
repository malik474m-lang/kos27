<?php
/**
 * Генерация обложки статьи через YandexART
 */

function generateArticleCoverImage(string $title): string {
    $title = trim($title);
    if ($title === '' || !defined('YANDEX_GPT_API_KEY') || !YANDEX_GPT_API_KEY || !defined('YANDEX_FOLDER_ID') || !YANDEX_FOLDER_ID) {
        return '';
    }

    // Используем шаблон из настроек. По умолчанию — строго как требуется:
    // "нарисуй 16:9 {title}"
    $settings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    $template = trim((string)($settings['article_image_prompt_template'] ?? 'нарисуй 16:9 {title}'));
    if ($template === '') $template = 'нарисуй 16:9 {title}';
    $artPrompt = str_replace('{title}', $title, $template);

    // Фиксируем seed по заголовку, чтобы для одной и той же темы результат был стабильнее
    $seed = abs(crc32($artPrompt)) % 999999 + 1;

    $artCtx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'art://' . YANDEX_FOLDER_ID . '/yandex-art/latest',
            'generationOptions' => [
                'seed' => $seed,
                'aspectRatio' => ['widthRatio' => '16', 'heightRatio' => '9']
            ],
            'messages' => [
                ['weight' => '1', 'text' => $artPrompt],
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

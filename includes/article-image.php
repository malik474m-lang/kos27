<?php
/**
 * Генерация обложки статьи через выбранный провайдер:
 * - YandexART
 * - Stability AI
 * - GigaChat / Kandinsky
 */

function getArticleImageSettings(): array {
    $settings = function_exists('getSiteSettings') ? getSiteSettings() : [];
    return [
        'provider' => trim((string)($settings['article_image_provider'] ?? 'yandex')) ?: 'yandex',
        'prompt_template' => trim((string)($settings['article_image_prompt_template'] ?? 'нарисуй 16:9 {title}')) ?: 'нарисуй 16:9 {title}',
        'stability_api_key' => trim((string)($settings['stability_api_key'] ?? '')),
        'gigachat_auth_key' => trim((string)($settings['gigachat_auth_key'] ?? '')),
        'gigachat_scope' => trim((string)($settings['gigachat_scope'] ?? 'GIGACHAT_API_PERS')) ?: 'GIGACHAT_API_PERS',
    ];
}

function saveArticleImageBinary(string $binary, string $ext = 'png'): string {
    if (!$binary) return '';
    $ext = in_array($ext, ['png','jpg','jpeg','webp'], true) ? $ext : 'png';
    $fileName = 'article-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $dirPath = __DIR__ . '/../images/articles';
    if (!is_dir($dirPath)) @mkdir($dirPath, 0755, true);
    if (@file_put_contents("$dirPath/$fileName", $binary) === false) return '';
    return "/images/articles/$fileName";
}

function buildArticleImagePrompt(string $title): string {
    $settings = getArticleImageSettings();
    return str_replace('{title}', trim($title), $settings['prompt_template']);
}

function generateArticleCoverImage(string $title): string {
    $result = generateArticleCoverImageResult($title);
    return $result['path'] ?? '';
}

function generateArticleCoverImageYandex(string $prompt): string {
    if (!defined('YANDEX_GPT_API_KEY') || !YANDEX_GPT_API_KEY || !defined('YANDEX_FOLDER_ID') || !YANDEX_FOLDER_ID) {
        return '';
    }
    $seed = abs(crc32($prompt)) % 999999 + 1;
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'art://' . YANDEX_FOLDER_ID . '/yandex-art/latest',
            'generationOptions' => ['seed' => $seed, 'aspectRatio' => ['widthRatio' => '16', 'heightRatio' => '9']],
            'messages' => [['weight' => '1', 'text' => $prompt]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'timeout' => 45,
        'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync', false, $ctx);
    if (!$resp) return '';
    $data = json_decode($resp, true);
    $opId = $data['id'] ?? null;
    if (!$opId) return '';
    for ($i = 0; $i < 18; $i++) {
        sleep(4);
        $checkCtx = stream_context_create(['http' => ['header' => "Authorization: Api-Key " . YANDEX_GPT_API_KEY, 'timeout' => 15, 'ignore_errors' => true]]);
        $checkResp = @file_get_contents("https://operation.api.cloud.yandex.net:443/operations/$opId", false, $checkCtx);
        if (!$checkResp) continue;
        $checkData = json_decode($checkResp, true);
        if (($checkData['done'] ?? false) && !empty($checkData['response']['image'])) {
            $binary = base64_decode($checkData['response']['image']);
            return saveArticleImageBinary($binary, 'jpeg');
        }
        if (($checkData['done'] ?? false) && !empty($checkData['error'])) return '';
    }
    return '';
}

function generateArticleCoverImageStability(string $prompt, array $settings): array {
    $apiKey = $settings['stability_api_key'] ?? '';
    if ($apiKey === '') return ['path' => '', 'error' => 'Stability API key not set'];
    $seed = abs(crc32($prompt)) % 4294967294 + 1;

    // Попытка 1: актуальный endpoint Stable Image Core
    $ch = curl_init('https://api.stability.ai/v2beta/stable-image/generate/core');
    $postFields = [
        'prompt' => $prompt,
        'aspect_ratio' => '16:9',
        'output_format' => 'png',
        'seed' => (string)$seed,
    ];
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Accept: image/png',
        ],
        CURLOPT_POSTFIELDS => $postFields,
    ]);
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($code >= 200 && $code < 300 && $response) {
        return ['path' => saveArticleImageBinary($response, 'png'), 'error' => null];
    }

    // Попытка 2: legacy SDXL endpoint
    $ch = curl_init('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'text_prompts' => [['text' => $prompt, 'weight' => 1]],
            'cfg_scale' => 7,
            'height' => 768,
            'width' => 1344,
            'steps' => 30,
            'samples' => 1,
            'seed' => $seed,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $response2 = curl_exec($ch);
    $code2 = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr2 = curl_error($ch);
    curl_close($ch);
    if ($code2 >= 200 && $code2 < 300 && $response2) {
        $data = json_decode($response2, true);
        $b64 = $data['artifacts'][0]['base64'] ?? '';
        if ($b64) {
            return ['path' => saveArticleImageBinary(base64_decode($b64), 'png'), 'error' => null];
        }
    }

    $err = 'core=' . $code . ($curlErr ? ' ' . $curlErr : '') . '; legacy=' . $code2 . ($curlErr2 ? ' ' . $curlErr2 : '');
    return ['path' => '', 'error' => $err];
}

function generateGigaChatAccessToken(array $settings): string {
    $authKey = $settings['gigachat_auth_key'] ?? '';
    if ($authKey === '') return '';
    $scope = $settings['gigachat_scope'] ?? 'GIGACHAT_API_PERS';
    $rqid = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid('', true));
    $rqid = substr($rqid,0,8) . '-' . substr($rqid,8,4) . '-' . substr($rqid,12,4) . '-' . substr($rqid,16,4) . '-' . substr($rqid,20,12);
    $ch = curl_init('https://ngw.devices.sberbank.ru:9443/api/v2/oauth');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'RqUID: ' . $rqid,
            'Authorization: Basic ' . $authKey,
        ],
        CURLOPT_POSTFIELDS => http_build_query(['scope' => $scope]),
    ]);
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300 || !$response) return ['path' => '', 'error' => 'chat ' . $code];
    $data = json_decode($response, true);
    return (string)($data['access_token'] ?? '');
}

function generateArticleCoverImageGigaChat(string $prompt, array $settings): array {
    $token = generateGigaChatAccessToken($settings);
    if ($token === '') return ['path' => '', 'error' => 'GigaChat token not received'];

    $clientId = 'kosmozaim-image';
    $ch = curl_init('https://gigachat.devices.sberbank.ru/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'X-Client-ID: ' . $clientId,
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'GigaChat',
            'function_call' => 'auto',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты — Василий Кандинский'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code < 200 || $code >= 300 || !$response) return '';
    $data = json_decode($response, true);
    $content = (string)($data['choices'][0]['message']['content'] ?? '');
    if ($content === '') return ['path' => '', 'error' => 'empty content'];
    if (!preg_match('/<img[^>]+src=\"([^\"]+)\"/i', $content, $m)) return ['path' => '', 'error' => 'no image file id in response'];
    $fileId = $m[1] ?? '';
    if ($fileId === '') return ['path' => '', 'error' => 'empty file id'];

    $imgCh = curl_init('https://api.giga.chat/v1/files/' . rawurlencode($fileId) . '/content');
    curl_setopt_array($imgCh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Accept: application/jpg',
            'Authorization: Bearer ' . $token,
            'X-Client-ID: ' . $clientId,
        ],
    ]);
    $binary = curl_exec($imgCh);
    $imgCode = (int)curl_getinfo($imgCh, CURLINFO_HTTP_CODE);
    curl_close($imgCh);
    if ($imgCode >= 200 && $imgCode < 300 && $binary) {
        return ['path' => saveArticleImageBinary($binary, 'jpg'), 'error' => null];
    }
    return ['path' => '', 'error' => 'file ' . $imgCode];
}


function articleImageProviderLabel(string $provider): string {
    return match ($provider) {
        'stability' => 'Stability AI',
        'gigachat' => 'GigaChat / Kandinsky',
        default => 'YandexART',
    };
}

function generateArticleCoverImageResult(string $title): array {
    $title = trim($title);
    if ($title === '') return ['path' => '', 'provider' => '', 'requested_provider' => ''];

    $settings = getArticleImageSettings();
    $requested = $settings['provider'];
    $prompt = buildArticleImagePrompt($title);

    $error = null;
    if ($requested === 'stability') {
        $res = generateArticleCoverImageStability($prompt, $settings);
        if (!empty($res['path'])) return ['path' => $res['path'], 'provider' => 'stability', 'requested_provider' => $requested, 'fallback' => false, 'error' => null];
        $error = $res['error'] ?? 'unknown stability error';
    }

    if ($requested === 'gigachat') {
        $res = generateArticleCoverImageGigaChat($prompt, $settings);
        if (!empty($res['path'])) return ['path' => $res['path'], 'provider' => 'gigachat', 'requested_provider' => $requested, 'fallback' => false, 'error' => null];
        $error = $res['error'] ?? 'unknown gigachat error';
    }

    $img = generateArticleCoverImageYandex($prompt);
    if ($img) return ['path' => $img, 'provider' => 'yandex', 'requested_provider' => $requested, 'fallback' => ($requested !== 'yandex'), 'error' => $error];

    return ['path' => '', 'provider' => '', 'requested_provider' => $requested, 'fallback' => false, 'error' => $error ?: 'all providers failed'];
}

<?php
require_once __DIR__ . '/ai-providers.php';
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
        CURLOPT_TIMEOUT => 35,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Accept: image/*',
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
    $responsePreview = is_string($response) ? mb_substr(trim($response), 0, 300) : '';

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
            'steps' => 20,
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

    $legacyPreview = is_string($response2) ? mb_substr(trim($response2), 0, 300) : '';
    $err = 'core=' . $code . ($curlErr ? ' ' . $curlErr : '') . ($responsePreview ? ' body=' . $responsePreview : '') . '; legacy=' . $code2 . ($curlErr2 ? ' ' . $curlErr2 : '') . ($legacyPreview ? ' body=' . $legacyPreview : '');
    return ['path' => '', 'error' => $err];
}

function generateGigaChatAccessTokenDetails(array $settings): array {
    $authKey = $settings['gigachat_auth_key'] ?? '';
    if ($authKey === '') return ['token' => '', 'error' => 'GigaChat auth key not set'];
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
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($code < 200 || $code >= 300 || !$response) {
        return ['token' => '', 'error' => 'oauth ' . $code . ($curlErr ? ' ' . $curlErr : '') . ($response ? ' body=' . mb_substr(trim($response),0,300) : '')];
    }
    $data = json_decode($response, true);
    $token = (string)($data['access_token'] ?? '');
    if ($token === '') return ['token' => '', 'error' => 'oauth token missing'];
    return ['token' => $token, 'error' => null];
}

function generateGigaChatAccessToken(array $settings): string {
    $res = generateGigaChatAccessTokenDetails($settings);
    return $res['token'] ?? '';
}

function generateArticleCoverImageGigaChat(string $prompt, array $settings): array {
    $tokenInfo = generateGigaChatAccessTokenDetails($settings);
    $token = $tokenInfo['token'] ?? '';
    if ($token === '') return ['path' => '', 'error' => ($tokenInfo['error'] ?? 'GigaChat token not received')];

    $clientId = 'kosmozaim-image';
    $chatBody = json_encode([
        'model' => 'GigaChat',
        'function_call' => 'auto',
        'messages' => [
            ['role' => 'system', 'content' => 'Ты — Василий Кандинский'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $chatUrls = [
        'https://api.giga.chat/v1/chat/completions',
        'https://gigachat.devices.sberbank.ru/api/v1/chat/completions',
    ];

    $content = '';
    $chatErrors = [];
    foreach ($chatUrls as $chatUrl) {
        $ch = curl_init($chatUrl);
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
            CURLOPT_POSTFIELDS => $chatBody,
        ]);
        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code >= 200 && $code < 300 && $response) {
            $data = json_decode($response, true);
            $content = (string)($data['choices'][0]['message']['content'] ?? '');
            if ($content !== '') break;
        }
        $chatErrors[] = $chatUrl . ' => ' . $code . ($err ? ' ' . $err : '') . ($response ? ' body=' . mb_substr(trim($response), 0, 250) : '');
    }
    if ($content === '') {
        return ['path' => '', 'error' => 'chat failed: ' . implode(' | ', $chatErrors)];
    }

    if (!preg_match('/<img[^>]+src="([^"]+)"/i', $content, $m)) {
        return ['path' => '', 'error' => 'no image file id in response: ' . mb_substr($content, 0, 250)];
    }
    $fileId = trim((string)($m[1] ?? ''));
    if ($fileId === '') return ['path' => '', 'error' => 'empty file id'];

    $downloadAttempts = [
        ['url' => 'https://api.giga.chat/v1/files/' . rawurlencode($fileId) . '/content', 'withClient' => true],
        ['url' => 'https://api.giga.chat/v1/files/' . rawurlencode($fileId) . '/content', 'withClient' => false],
        ['url' => 'https://gigachat.devices.sberbank.ru/api/v1/files/' . rawurlencode($fileId) . '/content', 'withClient' => true],
        ['url' => 'https://gigachat.devices.sberbank.ru/api/v1/files/' . rawurlencode($fileId) . '/content', 'withClient' => false],
    ];
    $downloadErrors = [];
    foreach ($downloadAttempts as $attempt) {
        $headers = [
            'Accept: application/jpg',
            'Authorization: Bearer ' . $token,
        ];
        if ($attempt['withClient']) {
            $headers[] = 'X-Client-ID: ' . $clientId;
        }
        $imgCh = curl_init($attempt['url']);
        curl_setopt_array($imgCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $binary = curl_exec($imgCh);
        $imgCode = (int)curl_getinfo($imgCh, CURLINFO_HTTP_CODE);
        $imgErr = curl_error($imgCh);
        curl_close($imgCh);
        if ($imgCode >= 200 && $imgCode < 300 && $binary) {
            return ['path' => saveArticleImageBinary($binary, 'jpg'), 'error' => null];
        }
        $downloadErrors[] = $attempt['url'] . ' client=' . ($attempt['withClient'] ? '1' : '0') . ' => ' . $imgCode . ($imgErr ? ' ' . $imgErr : '') . ($binary ? ' body=' . mb_substr(trim($binary), 0, 180) : '');
    }

    return ['path' => '', 'error' => 'file not downloaded; file_id=' . $fileId . '; ' . implode(' | ', $downloadErrors)];
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
    if ($title === '') return ['path' => '', 'provider' => '', 'requested_provider' => '', 'fallback' => false, 'error' => 'empty title'];

    $settings = getArticleImageSettings();
    $requested = $settings['provider'];
    $prompt = buildArticleImagePrompt($title);

    $providerMap = [
        'yandex' => 'yandex_art',
        'yandex_art' => 'yandex_art',
        'stability' => 'stability',
        'gigachat' => 'gigachat',
        'odirouter' => 'odirouter',
    ];
    $forcedProvider = $providerMap[$requested] ?? null;

    $result = $forcedProvider ? aiGenerateImage($prompt, $forcedProvider) : aiGenerateImage($prompt);
    if (!$result['success'] && $forcedProvider) {
        $fallbackResult = aiGenerateImage($prompt);
        if (!empty($fallbackResult['success'])) {
            logArticleImageEvent($requested, (string)($fallbackResult['provider'] ?? ''), $prompt, true, $result['error'] ?? null);
            return [
                'path' => $fallbackResult['path'] ?? '',
                'provider' => $fallbackResult['provider'] ?? '',
                'requested_provider' => $requested,
                'fallback' => true,
                'error' => $result['error'] ?? null,
            ];
        }
    }

    if (!empty($result['success']) && !empty($result['path'])) {
        logArticleImageEvent($requested, (string)($result['provider'] ?? ''), $prompt, true, null);
        return [
            'path' => $result['path'] ?? '',
            'provider' => $result['provider'] ?? '',
            'requested_provider' => $requested,
            'fallback' => ($forcedProvider && ($result['provider'] ?? '') !== $forcedProvider),
            'error' => null,
        ];
    }

    logArticleImageEvent($requested, '', $prompt, false, $result['error'] ?? 'all providers failed');
    return ['path' => '', 'provider' => '', 'requested_provider' => $requested, 'fallback' => false, 'error' => $result['error'] ?? 'all providers failed'];
}

function testArticleImageProvider(string $provider, string $title = 'тест'): array {
    $title = trim($title) ?: 'тест';
    $providerMap = [
        'yandex' => 'yandex_art',
        'yandex_art' => 'yandex_art',
        'stability' => 'stability',
        'gigachat' => 'gigachat',
        'odirouter' => 'odirouter',
    ];
    $forcedProvider = $providerMap[$provider] ?? null;
    if (!$forcedProvider) {
        return ['success' => false, 'provider' => $provider, 'error' => 'unknown provider'];
    }
    $result = aiGenerateImage(buildArticleImagePrompt($title), $forcedProvider);
    if (!empty($result['success']) && !empty($result['path'])) {
        @unlink(__DIR__ . '/..' . $result['path']);
        return ['success' => true, 'provider' => (string)($result['provider'] ?? $forcedProvider), 'error' => null];
    }
    return ['success' => false, 'provider' => $forcedProvider, 'error' => $result['error'] ?? 'generation failed'];
}


function logArticleImageEvent(string $providerRequested, string $providerActual, string $prompt, bool $success, ?string $error = null): void {
    $logFile = __DIR__ . '/../data/article-image-log.json';
    $entries = [];
    if (file_exists($logFile)) {
        $entries = json_decode(file_get_contents($logFile), true) ?: [];
    }
    $entries[] = [
        'time' => date('Y-m-d H:i:s'),
        'requested_provider' => $providerRequested,
        'actual_provider' => $providerActual,
        'success' => $success,
        'prompt' => mb_substr($prompt, 0, 300),
        'error' => $error ? mb_substr($error, 0, 800) : null,
    ];
    if (count($entries) > 100) $entries = array_slice($entries, -100);
    @file_put_contents($logFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getArticleImageLog(int $limit = 10): array {
    $logFile = __DIR__ . '/../data/article-image-log.json';
    if (!file_exists($logFile)) return [];
    $entries = json_decode(file_get_contents($logFile), true) ?: [];
    return array_slice(array_reverse($entries), 0, $limit);
}

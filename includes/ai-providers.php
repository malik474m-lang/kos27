<?php
/**
 * Unified AI Providers Module
 * Поддерживаемые провайдеры:
 * - OdiRouter (OpenAI-compatible API для текста + мультимодальный для изображений)
 * - YandexGPT (текст)
 * - YandexART (изображения)
 * - GigaChat (текст + Kandinsky изображения)
 * - Stability AI (изображения)
 */

require_once __DIR__ . '/odirouter-keys.php';

// === Конфигурация провайдеров ===

function getAIProvidersConfig(): array {
    static $config = null;
    if ($config !== null) return $config;
    
    $settingsFile = __DIR__ . '/../data/site-settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    
    // Дефолтные значения
    $defaults = [
        // OdiRouter
        'odirouter_enabled' => false,
        'odirouter_api_key' => '',
        'odirouter_image_api_key' => '',
        'odirouter_text_model' => 'free-gemini-2.5-flash',
        'odirouter_image_model' => 'free-nano-banana-2',
        
        // YandexGPT
        'yandex_gpt_enabled' => true,
        'yandex_gpt_api_key' => getenv('YANDEX_GPT_API_KEY') ?: '',
        'yandex_folder_id' => getenv('YANDEX_FOLDER_ID') ?: '',
        'yandex_gpt_model' => 'yandexgpt-lite',
        
        // YandexART
        'yandex_art_enabled' => true,
        
        // GigaChat
        'gigachat_enabled' => false,
        'gigachat_auth_key' => '',
        'gigachat_scope' => 'GIGACHAT_API_PERS',
        
        // Stability AI
        'stability_enabled' => false,
        'stability_api_key' => '',
        
        // Приоритеты
        'text_provider_priority' => ['odirouter', 'yandex_gpt', 'gigachat'],
        'image_provider_priority' => ['odirouter', 'yandex_art', 'stability', 'gigachat'],
    ];
    
    $config = array_merge($defaults, $settings);
    return $config;
}

function saveAIProvidersConfig(array $newConfig): bool {
    $settingsFile = __DIR__ . '/../data/site-settings.json';
    $current = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    if (!is_array($current)) $current = [];
    
    $merged = array_merge($current, $newConfig);
    return file_put_contents($settingsFile, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

// === Получение активного провайдера по приоритету ===

function getActiveTextProvider(): ?string {
    $config = getAIProvidersConfig();
    $priority = $config['text_provider_priority'] ?? ['yandex_gpt'];
    
    foreach ($priority as $provider) {
        if (isTextProviderAvailable($provider, $config)) {
            return $provider;
        }
    }
    return null;
}

function getActiveImageProvider(): ?string {
    $config = getAIProvidersConfig();
    $priority = $config['image_provider_priority'] ?? ['yandex_art'];
    
    foreach ($priority as $provider) {
        if (isImageProviderAvailable($provider, $config)) {
            return $provider;
        }
    }
    return null;
}

function isTextProviderAvailable(string $provider, ?array $config = null): bool {
    $config = $config ?? getAIProvidersConfig();
    
    switch ($provider) {
        case 'odirouter':
            if (empty($config['odirouter_enabled'])) return false;
            if (!empty($config['odirouter_api_key'])) return true;
            require_once __DIR__ . '/odirouter-keys.php';
            return !empty(odiGetAvailableKeys('text'));
        case 'yandex_gpt':
            return !empty($config['yandex_gpt_enabled']) && !empty($config['yandex_gpt_api_key']) && !empty($config['yandex_folder_id']);
        case 'gigachat':
            return !empty($config['gigachat_enabled']) && !empty($config['gigachat_auth_key']);
        default:
            return false;
    }
}

function isImageProviderAvailable(string $provider, ?array $config = null): bool {
    $config = $config ?? getAIProvidersConfig();
    
    switch ($provider) {
        case 'odirouter':
            if (empty($config['odirouter_enabled'])) return false;
            if (!empty($config['odirouter_api_key']) || !empty($config['odirouter_image_api_key'])) return true;
            require_once __DIR__ . '/odirouter-keys.php';
            return !empty(odiGetAvailableKeys('image'));
        case 'yandex_art':
            return !empty($config['yandex_art_enabled']) && !empty($config['yandex_gpt_api_key']) && !empty($config['yandex_folder_id']);
        case 'stability':
            return !empty($config['stability_enabled']) && !empty($config['stability_api_key']);
        case 'gigachat':
            return !empty($config['gigachat_enabled']) && !empty($config['gigachat_auth_key']);
        default:
            return false;
    }
}

// === OdiRouter API ===

function odiRouterGenerateText(string $prompt, string $systemPrompt = '', ?string $model = null): array {
    $config = getAIProvidersConfig();
    $model = $model ?? ($config['odirouter_text_model'] ?? 'free-gemini-2.5-flash');

    $keys = odiGetAvailableKeys('text');
    if (!$keys) {
        return ['success' => false, 'error' => 'OdiRouter: все ключи исчерпали дневной лимит (50/день). Добавьте ещё ключи в настройках.'];
    }

    $fallbackModels = ['free-gemini-2.5-flash', 'free-gemini-3.5-flash'];
    $disabledSlowModels = ['free-gpt-5.6-luna'];
    $deprioritizedModels = ['free-qwen3.7-plus', 'free-gpt-5.4-mini'];
    if (in_array($model, $disabledSlowModels, true)) {
        // полностью пропускаем слишком медленные модели в синхронных admin-вызовах
    } elseif (in_array($model, $deprioritizedModels, true)) {
        $fallbackModels[] = $model;
    } else {
        array_unshift($fallbackModels, $model);
    }
    $fallbackModels = array_values(array_unique($fallbackModels));

    $messages = [];
    if ($systemPrompt) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];

    $errors = [];
    $blockedAccounts = [];
    $blockedModels = [];
    $accountsTried = 0;
    $maxAccountsPerCall = 2;
    foreach ($keys as $activeKey) {
        $keyAccount = $activeKey['account'] ?? '';
        if ($keyAccount && isset($blockedAccounts[$keyAccount])) continue;
        if ($accountsTried >= $maxAccountsPerCall) break;
        $accountsTried++;
        $apiKey = $activeKey['key'];
        odiTrackUsage($activeKey['id']); // считаем использование аккаунта ДО запроса
        foreach ($fallbackModels as $tryModel) {
            if (isset($blockedModels[$tryModel])) continue;
            $payload = [
                'model' => $tryModel,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2800,
            ];

            $ch = curl_init('https://api.odirouter.ai/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                ],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $response = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                if (stripos($err, 'timed out') !== false) {
                    $blockedModels[$tryModel] = true; // модель тормозит — не повторяем на других аккаунтах в этом вызове
                }
                $errors[] = ($activeKey['name'] ?? 'key') . ' / ' . $tryModel . ': cURL ' . $err;
                continue; // пробуем следующую модель на том же ключе
            }

            if (in_array($code, [401, 402, 403, 408, 429, 500, 502, 503, 504], true)) {
                if (in_array($code, [402,403], true)) {
                    odiMarkKeyExhausted($activeKey['id']);
                }
                if ($code === 429 && $keyAccount) {
                    $blockedAccounts[$keyAccount] = true;
                }
                if (in_array($code, [503,504], true)) {
                    $blockedModels[$tryModel] = true; // модель временно недоступна — не повторяем на других аккаунтах
                }
                $errors[] = ($activeKey['name'] ?? 'key') . ' / ' . $tryModel . ': HTTP ' . $code;
                continue 2; // следующий ключ
            }

            if ($code < 200 || $code >= 300) {
                $errors[] = ($activeKey['name'] ?? 'key') . ' / ' . $tryModel . ': HTTP ' . $code . ' ' . mb_substr(strip_tags((string)$response), 0, 200);
                continue; // пробуем следующую модель на том же ключе
            }

            $data = json_decode((string)$response, true);
            $text = $data['choices'][0]['message']['content'] ?? '';
            if (!$text) {
                $errors[] = ($activeKey['name'] ?? 'key') . ' / ' . $tryModel . ': empty response';
                continue 2; // следующий ключ
            }

            return [
                'success' => true,
                'text' => $text,
                'provider' => 'odirouter',
                'model' => $tryModel,
                'usage' => $data['usage'] ?? null,
                'key_name' => $activeKey['name'] ?? '',
                'key_remaining' => ($activeKey['remaining'] ?? 0) - 1,
            ];
        }
    }

    return ['success' => false, 'error' => 'OdiRouter all keys failed. ' . implode('; ', array_slice($errors, 0, 8))];
}

function odiRouterGenerateImage(string $prompt, ?string $model = null): array {
    $config = getAIProvidersConfig();
    $model = $model ?? ($config['odirouter_image_model'] ?? 'free-nano-banana-2');

    $keys = odiGetAvailableKeys('image');
    if (!$keys) {
        return ['success' => false, 'error' => 'OdiRouter: все ключи исчерпали дневной лимит. Добавьте ещё ключи.'];
    }

    $errors = [];
    $blockedAccounts = []; // аккаунты получившие 429 — пропускаем все их ключи
    foreach ($keys as $activeKey) {
        $keyAccount = $activeKey['account'] ?? '';
        // Пропускаем ключи аккаунта, который уже получил 429
        if ($keyAccount && isset($blockedAccounts[$keyAccount])) {
            continue;
        }
        $apiKey = $activeKey['key'];
        odiTrackUsage($activeKey['id']); // считаем использование аккаунта ДО запроса

        $payload = [
            'prompt' => $prompt,
            'aspect_ratio' => '16:9',
            'resolution' => '1K',
        ];

        $ch = curl_init("https://api.odirouter.ai/model/v1/queue/{$model}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $response = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $errors[] = ($activeKey['name'] ?? 'key') . ': cURL ' . $err;
            continue;
        }

        if (in_array($code, [401, 402, 403, 408, 429, 500, 502, 503, 504], true)) {
            if ($code === 402) {
                odiMarkKeyExhausted($activeKey['id']);
            }
            if ($code === 429 && $keyAccount) {
                $blockedAccounts[$keyAccount] = true; // блокируем все ключи этого аккаунта
            }
            $errors[] = ($activeKey['name'] ?? 'key') . ': HTTP ' . $code;
            continue;
        }

        if ($code < 200 || $code >= 300) {
            $errors[] = ($activeKey['name'] ?? 'key') . ': HTTP ' . $code . ' ' . mb_substr((string)$response, 0, 200);
            continue;
        }

        $data = json_decode((string)$response, true);
        $requestId = $data['request_id'] ?? '';
        $statusUrl = $data['status_url'] ?? '';
        $responseUrl = $data['response_url'] ?? '';
        if (!$requestId || !$statusUrl || !$responseUrl) {
            $errors[] = ($activeKey['name'] ?? 'key') . ': invalid queue response';
            continue;
        }

        $taskFailed = false;
        for ($i = 0; $i < 15; $i++) {
            sleep(4);
            $ch = curl_init($statusUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            ]);
            $statusResp = curl_exec($ch);
            $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $statusErr = curl_error($ch);
            curl_close($ch);

            if ($statusErr) { $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': status cURL ' . $statusErr; break; }
            if (in_array($statusCode, [401,402,403,408,429,500,502,503,504], true)) {
                if (in_array($statusCode, [402,403], true)) odiMarkKeyExhausted($activeKey['id']);
                if ($statusCode === 429 && $keyAccount) $blockedAccounts[$keyAccount] = true;
                $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': status HTTP ' . $statusCode; break;
            }
            if ($statusCode < 200 || $statusCode >= 300) continue;

            $statusData = json_decode((string)$statusResp, true);
            $status = $statusData['status'] ?? '';
            if ($status === 'COMPLETED') {
                if (!empty($statusData['error'])) {
                    $taskFailed = true;
                    $errors[] = ($activeKey['name'] ?? 'key') . ': ' . (($statusData['error']['message'] ?? $statusData['error']['error_type'] ?? 'task failed'));
                    break;
                }

                $ch = curl_init($responseUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
                ]);
                $resultResp = curl_exec($ch);
                $resultCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $resultErr = curl_error($ch);
                curl_close($ch);

                if ($resultErr) { $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': response cURL ' . $resultErr; break; }
                if (in_array($resultCode, [401,402,403,408,429,500,502,503,504], true)) {
                    if (in_array($resultCode, [402,403], true)) odiMarkKeyExhausted($activeKey['id']);
                    if ($resultCode === 429 && $keyAccount) $blockedAccounts[$keyAccount] = true;
                    $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': response HTTP ' . $resultCode; break;
                }
                if ($resultCode < 200 || $resultCode >= 300) { $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': response HTTP ' . $resultCode; break; }

                $resultData = json_decode((string)$resultResp, true);
                $imageUrl = null;
                if (isset($resultData['output']) && is_array($resultData['output'])) {
                    foreach ($resultData['output'] as $outputItem) {
                        if (isset($outputItem['content']) && is_array($outputItem['content'])) {
                            foreach ($outputItem['content'] as $contentItem) {
                                if (($contentItem['type'] ?? '') === 'image' && !empty($contentItem['url'])) {
                                    $imageUrl = $contentItem['url'];
                                    break 2;
                                }
                            }
                        }
                        if (!empty($outputItem['url'])) { $imageUrl = $outputItem['url']; break; }
                    }
                    if (!$imageUrl) {
                        $imageUrl = $resultData['output']['image_url'] ?? $resultData['output']['url'] ?? null;
                    }
                }
                if (!$imageUrl) {
                    $imageUrl = $resultData['images'][0]['url'] ?? $resultData['result']['url'] ?? $resultData['url'] ?? $resultData['image_url'] ?? null;
                }
                $imageBase64 = $resultData['output'][0]['content'][0]['base64']
                    ?? $resultData['output']['image']
                    ?? $resultData['output']['base64']
                    ?? $resultData['images'][0]['base64']
                    ?? $resultData['base64']
                    ?? $resultData['image']
                    ?? null;

                if ($imageUrl) {
                    $ctx = stream_context_create(['http' => ['timeout' => 30], 'ssl' => ['verify_peer' => false]]);
                    $imageData = @file_get_contents($imageUrl, false, $ctx);
                    if ($imageData && strlen($imageData) > 1000) {
                        $path = saveAIImage($imageData);
                        if ($path) {
                                return ['success' => true, 'path' => $path, 'provider' => 'odirouter', 'model' => $model, 'key_name' => $activeKey['name'] ?? ''];
                        }
                    }
                    $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': failed to download image'; break;
                }
                if ($imageBase64) {
                    $imageData = base64_decode($imageBase64);
                    if ($imageData && strlen($imageData) > 1000) {
                        $path = saveAIImage($imageData);
                        if ($path) {
                                return ['success' => true, 'path' => $path, 'provider' => 'odirouter', 'model' => $model, 'key_name' => $activeKey['name'] ?? ''];
                        }
                    }
                    $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': failed to decode base64 image'; break;
                }

                $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': no image in response'; break;
            }
            if (!in_array($status, ['IN_QUEUE', 'IN_PROGRESS', 'PENDING', ''], true)) {
                $taskFailed = true; $errors[] = ($activeKey['name'] ?? 'key') . ': task status ' . $status; break;
            }
        }

        if (!$taskFailed) {
            $errors[] = ($activeKey['name'] ?? 'key') . ': timeout waiting for image generation';
            continue;
        }
    }

    return ['success' => false, 'error' => 'OdiRouter image failed for all keys. ' . implode('; ', array_slice($errors, 0, 8))];
}

function saveAIImage(string $binary): string {
    if (!$binary) return '';
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($binary);
    $ext = match($mime) {
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        default => 'png'
    };
    
    $fileName = 'article-' . time() . '-' . mt_rand(1000, 9999) . '.' . $ext;
    $dirPath = __DIR__ . '/../images/articles';
    if (!is_dir($dirPath)) @mkdir($dirPath, 0755, true);
    
    if (@file_put_contents("$dirPath/$fileName", $binary) === false) return '';
    return "/images/articles/$fileName";
}

// === Unified Generate Text ===

function sanitizeAiGeneratedText(string $text): string {
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace('/^\xEF\xBB\xBF/u', '', $text) ?? $text;
    $text = preg_replace('/\x{FFFD}+/u', '', $text) ?? $text;
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
    $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
    return trim($text);
}

function aiGenerateText(string $prompt, string $systemPrompt = '', ?string $forceProvider = null): array {
    $config = getAIProvidersConfig();
    
    if ($forceProvider) {
        $result = aiGenerateTextWithProvider($prompt, $systemPrompt, $forceProvider, $config);
        if (!empty($result['success']) && isset($result['text'])) {
            $result['text'] = sanitizeAiGeneratedText((string)$result['text']);
        }
        return $result;
    }
    
    $priority = $config['text_provider_priority'] ?? ['yandex_gpt'];
    $lastError = '';
    $tried = [];
    
    foreach ($priority as $provider) {
        if (!isTextProviderAvailable($provider, $config)) continue;
        
        $result = aiGenerateTextWithProvider($prompt, $systemPrompt, $provider, $config);
        if ($result['success']) {
            if (isset($result['text'])) {
                $result['text'] = sanitizeAiGeneratedText((string)$result['text']);
            }
            return $result;
        }
        
        $lastError = $result['error'] ?? 'unknown';
        $tried[] = $provider . ': ' . $lastError;
        error_log("AI Text Provider '{$provider}' failed: " . $lastError);
    }
    
    if ($tried) {
        return ['success' => false, 'error' => 'All providers failed: ' . implode('; ', $tried)];
    }
    return ['success' => false, 'error' => 'No AI text providers enabled/configured'];
}


function aiGenerateTextWithProvider(string $prompt, string $systemPrompt, string $provider, array $config): array {
    switch ($provider) {
        case 'odirouter':
            return odiRouterGenerateText($prompt, $systemPrompt);
        case 'yandex_gpt':
            return yandexGptGenerateText($prompt, $systemPrompt, $config);
        case 'gigachat':
            return gigaChatGenerateText($prompt, $systemPrompt, $config);
        default:
            return ['success' => false, 'error' => 'Unknown provider: ' . $provider];
    }
}

// === YandexGPT ===

function yandexGptGenerateText(string $prompt, string $systemPrompt, array $config): array {
    $apiKey = $config['yandex_gpt_api_key'] ?? '';
    $folderId = $config['yandex_folder_id'] ?? '';
    $model = $config['yandex_gpt_model'] ?? 'yandexgpt-lite';
    
    if (!$apiKey || !$folderId) {
        return ['success' => false, 'error' => 'YandexGPT credentials not set'];
    }
    
    $messages = [];
    if ($systemPrompt) {
        $messages[] = ['role' => 'system', 'text' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'text' => $prompt];
    
    $payload = [
        'modelUri' => "gpt://{$folderId}/{$model}",
        'completionOptions' => [
            'stream' => false,
            'temperature' => 0.6,
            'maxTokens' => 8000,
        ],
        'messages' => $messages,
    ];
    
    $ch = curl_init('https://llm.api.cloud.yandex.net/foundationModels/v1/completion');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Api-Key ' . $apiKey,
            'x-folder-id: ' . $folderId,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        return ['success' => false, 'error' => 'cURL error: ' . $err];
    }
    
    if ($code < 200 || $code >= 300) {
        return ['success' => false, 'error' => 'HTTP ' . $code . ': ' . mb_substr($response, 0, 500)];
    }
    
    $data = json_decode($response, true);
    $text = $data['result']['alternatives'][0]['message']['text'] ?? '';
    
    if (!$text) {
        return ['success' => false, 'error' => 'Empty response from YandexGPT'];
    }
    
    return [
        'success' => true,
        'text' => $text,
        'provider' => 'yandex_gpt',
        'model' => $model,
    ];
}

// === GigaChat ===

function gigaChatGetToken(array $config): array {
    $authKey = $config['gigachat_auth_key'] ?? '';
    if (!$authKey) {
        return ['token' => '', 'error' => 'GigaChat auth key not set'];
    }
    
    $scope = $config['gigachat_scope'] ?? 'GIGACHAT_API_PERS';
    $rqid = bin2hex(random_bytes(16));
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
    
    if ($code < 200 || $code >= 300) {
        return ['token' => '', 'error' => 'OAuth failed: HTTP ' . $code];
    }
    
    $data = json_decode($response, true);
    $token = $data['access_token'] ?? '';
    
    return $token ? ['token' => $token, 'error' => null] : ['token' => '', 'error' => 'No token in response'];
}

function gigaChatGenerateText(string $prompt, string $systemPrompt, array $config): array {
    $tokenInfo = gigaChatGetToken($config);
    if (!$tokenInfo['token']) {
        return ['success' => false, 'error' => $tokenInfo['error']];
    }
    
    $messages = [];
    if ($systemPrompt) {
        $messages[] = ['role' => 'system', 'content' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];
    
    $payload = [
        'model' => 'GigaChat',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 4096,
    ];
    
    $ch = curl_init('https://gigachat.devices.sberbank.ru/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $tokenInfo['token'],
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code < 200 || $code >= 300) {
        return ['success' => false, 'error' => 'HTTP ' . $code . ': ' . mb_substr($response, 0, 500)];
    }
    
    $data = json_decode($response, true);
    $text = $data['choices'][0]['message']['content'] ?? '';
    
    if (!$text) {
        return ['success' => false, 'error' => 'Empty response from GigaChat'];
    }
    
    return [
        'success' => true,
        'text' => $text,
        'provider' => 'gigachat',
        'model' => 'GigaChat',
    ];
}

// === Unified Generate Image ===

function aiGenerateImage(string $prompt, ?string $forceProvider = null): array {
    $config = getAIProvidersConfig();
    
    if ($forceProvider) {
        return aiGenerateImageWithProvider($prompt, $forceProvider, $config);
    }
    
    $priority = $config['image_provider_priority'] ?? ['yandex_art'];
    
    foreach ($priority as $provider) {
        if (!isImageProviderAvailable($provider, $config)) continue;
        
        $result = aiGenerateImageWithProvider($prompt, $provider, $config);
        if ($result['success']) {
            return $result;
        }
        
        error_log("AI Image Provider '{$provider}' failed: " . ($result['error'] ?? 'unknown'));
    }
    
    return ['success' => false, 'error' => 'No available AI image providers'];
}

function aiGenerateImageWithProvider(string $prompt, string $provider, array $config): array {
    switch ($provider) {
        case 'odirouter':
            return odiRouterGenerateImage($prompt);
        case 'yandex_art':
            return yandexArtGenerateImage($prompt, $config);
        case 'stability':
            return stabilityGenerateImage($prompt, $config);
        case 'gigachat':
            return gigaChatGenerateImage($prompt, $config);
        default:
            return ['success' => false, 'error' => 'Unknown provider: ' . $provider];
    }
}

// === YandexART ===

function yandexArtGenerateImage(string $prompt, array $config): array {
    $apiKey = $config['yandex_gpt_api_key'] ?? '';
    $folderId = $config['yandex_folder_id'] ?? '';
    
    if (!$apiKey || !$folderId) {
        return ['success' => false, 'error' => 'YandexART credentials not set'];
    }
    
    $seed = abs(crc32($prompt)) % 999999 + 1;
    
    $payload = [
        'modelUri' => "art://{$folderId}/yandex-art/latest",
        'generationOptions' => [
            'seed' => $seed,
            'aspectRatio' => ['widthRatio' => '16', 'heightRatio' => '9'],
        ],
        'messages' => [['weight' => '1', 'text' => $prompt]],
    ];
    
    $ch = curl_init('https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Api-Key ' . $apiKey,
            'x-folder-id: ' . $folderId,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $opId = $data['id'] ?? null;
    
    if (!$opId) {
        return ['success' => false, 'error' => 'No operation ID: ' . mb_substr($response, 0, 300)];
    }
    
    for ($i = 0; $i < 18; $i++) {
        sleep(4);
        
        $ch = curl_init("https://operation.api.cloud.yandex.net:443/operations/{$opId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => ['Authorization: Api-Key ' . $apiKey],
        ]);
        
        $checkResp = curl_exec($ch);
        curl_close($ch);
        
        $checkData = json_decode($checkResp, true);
        
        if (!empty($checkData['done']) && !empty($checkData['response']['image'])) {
            $binary = base64_decode($checkData['response']['image']);
            $path = saveAIImage($binary);
            if ($path) {
                return ['success' => true, 'path' => $path, 'provider' => 'yandex_art'];
            }
        }
        
        if (!empty($checkData['done']) && !empty($checkData['error'])) {
            return ['success' => false, 'error' => $checkData['error']['message'] ?? 'YandexART error'];
        }
    }
    
    return ['success' => false, 'error' => 'YandexART timeout'];
}

// === Stability AI ===

function stabilityGenerateImage(string $prompt, array $config): array {
    $apiKey = $config['stability_api_key'] ?? '';
    
    if (!$apiKey) {
        return ['success' => false, 'error' => 'Stability API key not set'];
    }
    
    $ch = curl_init('https://api.stability.ai/v2beta/stable-image/generate/core');
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
        CURLOPT_POSTFIELDS => [
            'prompt' => $prompt,
            'aspect_ratio' => '16:9',
            'output_format' => 'png',
        ],
    ]);
    
    $response = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code >= 200 && $code < 300 && $response) {
        $path = saveAIImage($response);
        if ($path) {
            return ['success' => true, 'path' => $path, 'provider' => 'stability'];
        }
    }
    
    return ['success' => false, 'error' => 'Stability AI error: HTTP ' . $code];
}

// === GigaChat Image (Kandinsky) ===

function gigaChatGenerateImage(string $prompt, array $config): array {
    $tokenInfo = gigaChatGetToken($config);
    if (!$tokenInfo['token']) {
        return ['success' => false, 'error' => $tokenInfo['error']];
    }
    
    $payload = [
        'model' => 'GigaChat',
        'function_call' => 'auto',
        'messages' => [
            ['role' => 'system', 'content' => 'Ты — Василий Кандинский'],
            ['role' => 'user', 'content' => $prompt],
        ],
    ];
    
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
            'Authorization: Bearer ' . $tokenInfo['token'],
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    
    if (preg_match('/<img[^>]+src="([^"]+)"/', $content, $m)) {
        $fileId = $m[1];
        
        $ch = curl_init("https://gigachat.devices.sberbank.ru/api/v1/files/{$fileId}/content");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER => [
                'Accept: application/jpg',
                'Authorization: Bearer ' . $tokenInfo['token'],
            ],
        ]);
        
        $imageData = curl_exec($ch);
        curl_close($ch);
        
        if ($imageData) {
            $path = saveAIImage($imageData);
            if ($path) {
                return ['success' => true, 'path' => $path, 'provider' => 'gigachat'];
            }
        }
    }
    
    return ['success' => false, 'error' => 'GigaChat did not return an image'];
}

// === Helper: Get all providers status ===

function getAIProvidersStatus(): array {
    $config = getAIProvidersConfig();
    
    return [
        'text' => [
            'odirouter' => [
                'name' => 'OdiRouter',
                'enabled' => !empty($config['odirouter_enabled']),
                'configured' => !empty($config['odirouter_api_key']),
                'available' => isTextProviderAvailable('odirouter', $config),
                'model' => $config['odirouter_text_model'] ?? 'free-gemini-2.5-flash',
            ],
            'yandex_gpt' => [
                'name' => 'YandexGPT',
                'enabled' => !empty($config['yandex_gpt_enabled']),
                'configured' => !empty($config['yandex_gpt_api_key']) && !empty($config['yandex_folder_id']),
                'available' => isTextProviderAvailable('yandex_gpt', $config),
                'model' => $config['yandex_gpt_model'] ?? 'yandexgpt-lite',
            ],
            'gigachat' => [
                'name' => 'GigaChat',
                'enabled' => !empty($config['gigachat_enabled']),
                'configured' => !empty($config['gigachat_auth_key']),
                'available' => isTextProviderAvailable('gigachat', $config),
                'model' => 'GigaChat',
            ],
        ],
        'image' => [
            'odirouter' => [
                'name' => 'OdiRouter',
                'enabled' => !empty($config['odirouter_enabled']),
                'configured' => !empty($config['odirouter_api_key']),
                'available' => isImageProviderAvailable('odirouter', $config),
                'model' => $config['odirouter_image_model'] ?? 'free-nano-banana-2',
            ],
            'yandex_art' => [
                'name' => 'YandexART',
                'enabled' => !empty($config['yandex_art_enabled']),
                'configured' => !empty($config['yandex_gpt_api_key']) && !empty($config['yandex_folder_id']),
                'available' => isImageProviderAvailable('yandex_art', $config),
                'model' => 'yandex-art/latest',
            ],
            'stability' => [
                'name' => 'Stability AI',
                'enabled' => !empty($config['stability_enabled']),
                'configured' => !empty($config['stability_api_key']),
                'available' => isImageProviderAvailable('stability', $config),
                'model' => 'stable-image-core',
            ],
            'gigachat' => [
                'name' => 'GigaChat (Kandinsky)',
                'enabled' => !empty($config['gigachat_enabled']),
                'configured' => !empty($config['gigachat_auth_key']),
                'available' => isImageProviderAvailable('gigachat', $config),
                'model' => 'Kandinsky',
            ],
        ],
        'priority' => [
            'text' => $config['text_provider_priority'] ?? ['yandex_gpt'],
            'image' => $config['image_provider_priority'] ?? ['yandex_art'],
        ],
        'active' => [
            'text' => getActiveTextProvider(),
            'image' => getActiveImageProvider(),
        ],
    ];
}

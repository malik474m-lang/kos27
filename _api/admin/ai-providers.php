<?php
/**
 * API управления AI провайдерами
 * GET — получить статус и настройки
 * POST — сохранить настройки
 * POST action=test — протестировать провайдер
 */

require_once __DIR__ . '/../../includes/ai-providers.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET — получить все настройки и статусы провайдеров
if ($method === 'GET') {
    $status = getAIProvidersStatus();
    $config = getAIProvidersConfig();
    
    // Маскируем ключи
    $masked = [];
    $sensitiveFields = ['odirouter_api_key', 'yandex_gpt_api_key', 'stability_api_key', 'gigachat_auth_key'];
    
    foreach ($config as $key => $value) {
        if (in_array($key, $sensitiveFields) && !empty($value)) {
            $masked[$key . '_masked'] = substr($value, 0, 8) . '...' . substr($value, -4);
            $masked[$key] = ''; // Не отдаём реальный ключ
        } else {
            $masked[$key] = $value;
        }
    }
    
    echo json_encode([
        'status' => $status,
        'config' => $masked,
        'availableTextModels' => [
            'odirouter' => ['gpt-5.5', 'gpt-5.4-mini', 'gemini-3.1-pro-preview', 'gemini-2.5-flash', 'claude-haiku-4.5', 'qwen3.5-plus'],
            'yandex_gpt' => ['yandexgpt-lite', 'yandexgpt', 'yandexgpt-32k'],
            'gigachat' => ['GigaChat', 'GigaChat-Pro', 'GigaChat-Max'],
        ],
        'availableImageModels' => [
            'odirouter' => ['nano_banana_2', 'flux-1.1-pro', 'ideogram-v2'],
            'yandex_art' => ['yandex-art/latest'],
            'stability' => ['stable-image-core', 'stable-diffusion-xl'],
            'gigachat' => ['Kandinsky'],
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// POST
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Тест провайдера
    if (($data['action'] ?? '') === 'test') {
        $provider = $data['provider'] ?? '';
        $type = $data['type'] ?? 'text'; // text или image
        
        if ($type === 'text') {
            $result = aiGenerateTextWithProvider(
                'Напиши одно предложение о погоде.',
                'Ты помощник. Отвечай кратко.',
                $provider,
                getAIProvidersConfig()
            );
        } else {
            $result = aiGenerateImageWithProvider(
                'красивый закат над морем, фотореалистично',
                $provider,
                getAIProvidersConfig()
            );
        }
        
        echo json_encode([
            'success' => $result['success'],
            'provider' => $provider,
            'type' => $type,
            'result' => $result,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Сохранение настроек
    $allowedFields = [
        'odirouter_enabled', 'odirouter_api_key', 'odirouter_text_model', 'odirouter_image_model',
        'yandex_gpt_enabled', 'yandex_gpt_api_key', 'yandex_folder_id', 'yandex_gpt_model',
        'yandex_art_enabled',
        'gigachat_enabled', 'gigachat_auth_key', 'gigachat_scope',
        'stability_enabled', 'stability_api_key',
        'text_provider_priority', 'image_provider_priority',
    ];
    
    $toSave = [];
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $value = $data[$field];
            
            // Пропускаем замаскированные ключи
            if (str_contains($field, '_api_key') || str_contains($field, '_auth_key')) {
                if (empty($value) || str_contains((string)$value, '...')) {
                    continue;
                }
            }
            
            // Boolean поля
            if (str_ends_with($field, '_enabled')) {
                $toSave[$field] = !empty($value);
            }
            // Массивы приоритетов
            elseif (str_ends_with($field, '_priority')) {
                $toSave[$field] = is_array($value) ? $value : [];
            }
            // Строки
            else {
                $toSave[$field] = is_string($value) ? trim($value) : $value;
            }
        }
    }
    
    if (saveAIProvidersConfig($toSave)) {
        // Очищаем кэш
        require_once __DIR__ . '/../../includes/api-cache.php';
        if (function_exists('apiCacheClear')) apiCacheClear();
        
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save settings']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

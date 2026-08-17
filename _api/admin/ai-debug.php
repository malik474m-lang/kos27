<?php
/**
 * Диагностика AI провайдеров — показывает полный статус
 */
require_once __DIR__ . '/../../includes/ai-providers.php';

$config = getAIProvidersConfig();
$status = getAIProvidersStatus();

// Маскируем ключи но показываем длину
$keys = [];
foreach (['odirouter_api_key', 'odirouter_image_api_key', 'yandex_gpt_api_key', 'stability_api_key', 'gigachat_auth_key'] as $k) {
    $v = $config[$k] ?? '';
    $keys[$k] = $v ? (substr($v, 0, 6) . '...' . substr($v, -4) . ' (' . strlen($v) . ' chars)') : 'EMPTY';
}

echo json_encode([
    'keys' => $keys,
    'enabled' => [
        'odirouter' => !empty($config['odirouter_enabled']),
        'yandex_gpt' => !empty($config['yandex_gpt_enabled']),
        'yandex_art' => !empty($config['yandex_art_enabled']),
        'gigachat' => !empty($config['gigachat_enabled']),
        'stability' => !empty($config['stability_enabled']),
    ],
    'text_priority' => $config['text_provider_priority'] ?? [],
    'image_priority' => $config['image_provider_priority'] ?? [],
    'active_text' => $status['active']['text'],
    'active_image' => $status['active']['image'],
    'providers' => $status,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

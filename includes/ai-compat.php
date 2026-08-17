<?php
/**
 * AI совместимость — обёртка для старого кода, который вызывал YandexGPT напрямую.
 * Вместо прямого вызова llm.api.cloud.yandex.net — используем unified AI providers.
 */

require_once __DIR__ . '/ai-providers.php';

/**
 * Замена прямого вызова YandexGPT.
 * Старый код: file_get_contents('https://llm.api.cloud.yandex.net/...')
 * Новый код: kosmozaimAIComplete($systemPrompt, $userPrompt)
 */
function kosmozaimAIComplete(string $systemPrompt, string $userPrompt, float $temperature = 0.6, int $maxTokens = 4000): ?string {
    $result = aiGenerateText($userPrompt, $systemPrompt);
    if ($result['success'] && !empty($result['text'])) {
        return $result['text'];
    }
    return null;
}

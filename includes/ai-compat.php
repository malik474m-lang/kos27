<?php
/**
 * AI совместимость — обёртка для старого кода.
 * Загружается лениво (require_once), не выводит ничего.
 */
if (!function_exists('kosmozaimAIComplete')) {
    require_once __DIR__ . '/ai-providers.php';
    
    function kosmozaimAIComplete(string $systemPrompt, string $userPrompt, float $temperature = 0.6, int $maxTokens = 4000): ?string {
        $result = aiGenerateText($userPrompt, $systemPrompt);
        if ($result['success'] && !empty($result['text'])) {
            return $result['text'];
        }
        return null;
    }
}

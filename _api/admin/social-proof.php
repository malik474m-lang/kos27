<?php
require_once __DIR__ . '/../../includes/page-cache.php';
/**
 * API для настроек виджета социального доказательства
 */

$method = $_SERVER['REQUEST_METHOD'];
$settingsFile = __DIR__ . '/../../data/site-settings.json';

// Получить настройки
if ($method === 'GET') {
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
    }
    
    echo json_encode([
        'enabled' => (bool)($settings['social_proof_enabled'] ?? true),
        'interval' => (int)($settings['social_proof_interval'] ?? 8000),
        'duration' => (int)($settings['social_proof_duration'] ?? 5000),
        'min_amount' => (int)($settings['social_proof_min_amount'] ?? 5000),
        'max_amount' => (int)($settings['social_proof_max_amount'] ?? 30000),
        'position' => $settings['social_proof_position'] ?? 'bottom-left',
    ]);
    exit;
}

// Сохранить настройки
if ($method === 'POST' || $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
    }
    
    // Обновляем только social_proof поля
    if (isset($data['enabled'])) {
        $settings['social_proof_enabled'] = (bool)$data['enabled'];
    }
    if (isset($data['interval'])) {
        $settings['social_proof_interval'] = max(3000, min(30000, (int)$data['interval']));
    }
    if (isset($data['duration'])) {
        $settings['social_proof_duration'] = max(2000, min(10000, (int)$data['duration']));
    }
    if (isset($data['min_amount'])) {
        $settings['social_proof_min_amount'] = max(1000, (int)$data['min_amount']);
    }
    if (isset($data['max_amount'])) {
        $settings['social_proof_max_amount'] = max(5000, (int)$data['max_amount']);
    }
    if (isset($data['position'])) {
        $settings['social_proof_position'] = in_array($data['position'], ['bottom-left', 'bottom-right']) 
            ? $data['position'] 
            : 'bottom-left';
    }
    
    $saved = file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($saved === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить настройки виджета']);
        exit;
    }

    $pageCleared = function_exists('pageCacheClear') ? pageCacheClear() : 0;
    $apiCleared = function_exists('apiCacheClear') ? apiCacheClear() : 0;

    echo json_encode([
        'success' => true,
        'message' => 'Настройки виджета сохранены',
        'saved' => [
            'enabled' => (bool)($settings['social_proof_enabled'] ?? true),
            'interval' => (int)($settings['social_proof_interval'] ?? 8000),
            'duration' => (int)($settings['social_proof_duration'] ?? 5000),
            'min_amount' => (int)($settings['social_proof_min_amount'] ?? 5000),
            'max_amount' => (int)($settings['social_proof_max_amount'] ?? 30000),
            'position' => $settings['social_proof_position'] ?? 'bottom-left',
        ],
        'page_cache_cleared' => $pageCleared,
        'api_cache_cleared' => $apiCleared,
    ]);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);

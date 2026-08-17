<?php
/**
 * OdiRouter Key Rotation — автоматическое переключение API ключей
 * 
 * Хранит пул ключей и счётчик использования.
 * При достижении лимита (50/день) переключается на следующий.
 * Счётчики сбрасываются в 00:00 UTC.
 */

define('ODIROUTER_DAILY_LIMIT', 50);
define('ODIROUTER_KEYS_FILE', __DIR__ . '/../data/odirouter-keys.json');
define('ODIROUTER_USAGE_FILE', __DIR__ . '/../data/odirouter-usage.json');

/**
 * Загрузить пул ключей
 */
function odiLoadKeys(): array {
    if (!file_exists(ODIROUTER_KEYS_FILE)) return [];
    $data = json_decode(file_get_contents(ODIROUTER_KEYS_FILE), true);
    return is_array($data) ? $data : [];
}

/**
 * Сохранить пул ключей
 */
function odiSaveKeys(array $keys): bool {
    $dir = dirname(ODIROUTER_KEYS_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return file_put_contents(ODIROUTER_KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Загрузить счётчики использования
 */
function odiLoadUsage(): array {
    if (!file_exists(ODIROUTER_USAGE_FILE)) return ['date' => '', 'keys' => []];
    $data = json_decode(file_get_contents(ODIROUTER_USAGE_FILE), true);
    if (!is_array($data)) return ['date' => '', 'keys' => []];
    
    // Сброс счётчиков при новом дне (UTC)
    $today = gmdate('Y-m-d');
    if (($data['date'] ?? '') !== $today) {
        return ['date' => $today, 'keys' => []];
    }
    return $data;
}

/**
 * Сохранить счётчики
 */
function odiSaveUsage(array $usage): void {
    @file_put_contents(ODIROUTER_USAGE_FILE, json_encode($usage));
}

/**
 * Записать использование ключа (+1)
 */
function odiTrackUsage(string $keyId): void {
    $usage = odiLoadUsage();
    $usage['keys'][$keyId] = ($usage['keys'][$keyId] ?? 0) + 1;
    odiSaveUsage($usage);
}

/**
 * Получить рабочий ключ (с оставшимся лимитом)
 * Возвращает ['key' => string, 'id' => string, 'name' => string, 'remaining' => int] или null
 */
function odiGetActiveKey(string $type = 'text'): ?array {
    // Сначала пул ключей
    $keys = odiLoadKeys();
    $usage = odiLoadUsage();
    
    // Также добавляем ключи из основных настроек (для совместимости)
    $settingsFile = __DIR__ . '/../data/site-settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    
    $mainKey = $settings['odirouter_api_key'] ?? '';
    $imageKey = $settings['odirouter_image_api_key'] ?? '';
    
    // Собираем все уникальные ключи
    $allKeys = [];
    
    // Ключи из пула
    foreach ($keys as $k) {
        if (empty($k['key']) || empty($k['enabled'])) continue;
        // Фильтр по типу
        if ($type === 'image' && !empty($k['type']) && $k['type'] === 'text') continue;
        if ($type === 'text' && !empty($k['type']) && $k['type'] === 'image') continue;
        $allKeys[] = $k;
    }
    
    // Ключи из настроек (если не дублируют)
    $poolKeyValues = array_column($allKeys, 'key');
    
    if ($mainKey && !in_array($mainKey, $poolKeyValues)) {
        $allKeys[] = ['key' => $mainKey, 'id' => 'settings_main', 'name' => 'Основной (настройки)', 'enabled' => true];
    }
    if ($imageKey && $imageKey !== $mainKey && !in_array($imageKey, $poolKeyValues)) {
        $allKeys[] = ['key' => $imageKey, 'id' => 'settings_image', 'name' => 'Картинки (настройки)', 'enabled' => true];
    }
    
    if (empty($allKeys)) return null;
    
    // Ищем ключ с оставшимся лимитом
    foreach ($allKeys as $k) {
        $keyId = $k['id'] ?? md5($k['key']);
        $used = $usage['keys'][$keyId] ?? 0;
        $remaining = ODIROUTER_DAILY_LIMIT - $used;
        
        if ($remaining > 0) {
            return [
                'key' => $k['key'],
                'id' => $keyId,
                'name' => $k['name'] ?? ('Ключ ' . substr($k['key'], 0, 8) . '...'),
                'remaining' => $remaining,
                'used' => $used,
            ];
        }
    }
    
    return null; // Все ключи исчерпаны
}

/**
 * Получить статистику по всем ключам
 */
function odiGetKeysStats(): array {
    $keys = odiLoadKeys();
    $usage = odiLoadUsage();
    $settings = file_exists(__DIR__ . '/../data/site-settings.json') 
        ? json_decode(file_get_contents(__DIR__ . '/../data/site-settings.json'), true) : [];
    
    $stats = [];
    $totalRemaining = 0;
    
    // Пул
    foreach ($keys as $k) {
        $keyId = $k['id'] ?? md5($k['key'] ?? '');
        $used = $usage['keys'][$keyId] ?? 0;
        $remaining = max(0, ODIROUTER_DAILY_LIMIT - $used);
        $totalRemaining += $remaining;
        $stats[] = [
            'id' => $keyId,
            'name' => $k['name'] ?? '',
            'type' => $k['type'] ?? 'all',
            'enabled' => !empty($k['enabled']),
            'used' => $used,
            'remaining' => $remaining,
            'limit' => ODIROUTER_DAILY_LIMIT,
            'masked' => substr($k['key'] ?? '', 0, 8) . '...' . substr($k['key'] ?? '', -4),
        ];
    }
    
    // Из настроек
    $mainKey = $settings['odirouter_api_key'] ?? '';
    $imageKey = $settings['odirouter_image_api_key'] ?? '';
    $poolKeyValues = array_column($keys, 'key');
    
    foreach ([['key'=>$mainKey,'name'=>'Основной','id'=>'settings_main'],['key'=>$imageKey,'name'=>'Картинки','id'=>'settings_image']] as $sk) {
        if ($sk['key'] && !in_array($sk['key'], $poolKeyValues)) {
            $used = $usage['keys'][$sk['id']] ?? 0;
            $remaining = max(0, ODIROUTER_DAILY_LIMIT - $used);
            $totalRemaining += $remaining;
            $stats[] = [
                'id' => $sk['id'],
                'name' => $sk['name'] . ' (настройки)',
                'type' => 'all',
                'enabled' => true,
                'used' => $used,
                'remaining' => $remaining,
                'limit' => ODIROUTER_DAILY_LIMIT,
                'masked' => substr($sk['key'], 0, 8) . '...' . substr($sk['key'], -4),
            ];
        }
    }
    
    return [
        'keys' => $stats,
        'total_remaining' => $totalRemaining,
        'total_keys' => count($stats),
        'date' => $usage['date'] ?? gmdate('Y-m-d'),
    ];
}

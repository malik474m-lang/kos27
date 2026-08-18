<?php
/**
 * OdiRouter Key Rotation — автоматическое переключение API ключей
 * 
 * ВАЖНО: Лимит 50 запросов/день считается НА АККАУНТ, а не на ключ.
 * Один аккаунт может иметь несколько API ключей, но общий лимит — 50.
 * Счётчики использования ведутся по аккаунту.
 * Счётчики сбрасываются в 00:00 UTC.
 */

define('ODIROUTER_DAILY_LIMIT', 50);
define('ODIROUTER_KEYS_FILE', __DIR__ . '/../data/odirouter-keys.json');
define('ODIROUTER_USAGE_FILE', __DIR__ . '/../data/odirouter-usage.json');
define('ODIROUTER_CURSOR_FILE', __DIR__ . '/../data/odirouter-cursor.json');
define('ODIROUTER_DISABLED_ACCOUNTS_FILE', __DIR__ . '/../data/odirouter-disabled-accounts.json');


function odiNormalizeAccountId(?string $account, string $fallbackId): string {
    $account = trim((string)$account);
    return $account !== '' ? $account : ('_no_account_' . $fallbackId);
}

function odiLoadKeys(): array {
    if (!file_exists(ODIROUTER_KEYS_FILE)) return [];
    $data = json_decode(file_get_contents(ODIROUTER_KEYS_FILE), true);
    return is_array($data) ? $data : [];
}

function odiSaveKeys(array $keys): bool {
    $dir = dirname(ODIROUTER_KEYS_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return file_put_contents(ODIROUTER_KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT)) !== false;
}

function odiLoadUsage(): array {
    if (!file_exists(ODIROUTER_USAGE_FILE)) return ['date' => '', 'accounts' => [], 'keys' => []];
    $data = json_decode(file_get_contents(ODIROUTER_USAGE_FILE), true);
    if (!is_array($data)) return ['date' => '', 'accounts' => [], 'keys' => []];
    
    $today = gmdate('Y-m-d');
    if (($data['date'] ?? '') !== $today) {
        return ['date' => $today, 'accounts' => [], 'keys' => []];
    }
    // Миграция: если нет accounts — создаём
    if (!isset($data['accounts'])) $data['accounts'] = [];
    return $data;
}

function odiSaveUsage(array $usage): void {
    @file_put_contents(ODIROUTER_USAGE_FILE, json_encode($usage));
}



function odiGetDisabledAccounts(): array {
    if (!file_exists(ODIROUTER_DISABLED_ACCOUNTS_FILE)) return [];
    $data = json_decode(file_get_contents(ODIROUTER_DISABLED_ACCOUNTS_FILE), true);
    return is_array($data) ? $data : [];
}

function odiSetDisabledAccounts(array $list): void {
    @file_put_contents(ODIROUTER_DISABLED_ACCOUNTS_FILE, json_encode(array_values(array_unique($list))));
}

function odiIsAccountDisabled(string $account): bool {
    return in_array($account, odiGetDisabledAccounts(), true);
}

function odiLoadCursor(): array {
    if (!file_exists(ODIROUTER_CURSOR_FILE)) return ['text' => 0, 'image' => 0];
    $data = json_decode(file_get_contents(ODIROUTER_CURSOR_FILE), true);
    if (!is_array($data)) return ['text' => 0, 'image' => 0];
    return [
        'text' => (int)($data['text'] ?? 0),
        'image' => (int)($data['image'] ?? 0),
    ];
}

function odiSaveCursor(array $cursor): void {
    @file_put_contents(ODIROUTER_CURSOR_FILE, json_encode($cursor));
}

function odiRotateAccounts(array $keys, string $type = 'text'): array {
    if (count($keys) <= 1) return $keys;
    $cursor = odiLoadCursor();
    $idx = (int)($cursor[$type] ?? 0);
    $idx = $idx % count($keys);
    $rotated = array_merge(array_slice($keys, $idx), array_slice($keys, 0, $idx));
    $cursor[$type] = ($idx + 1) % count($keys);
    odiSaveCursor($cursor);
    return $rotated;
}

/**
 * Определить аккаунт ключа (по id ключа)
 */
function odiGetAccountForKey(string $keyId): string {
    $keys = odiLoadKeys();
    foreach ($keys as $k) {
        $id = $k['id'] ?? md5($k['key'] ?? '');
        if ($id === $keyId) {
            return odiNormalizeAccountId($k['account'] ?? '', $keyId);
        }
    }
    return '_no_account_' . $keyId;
}

/**
 * Записать использование: +1 к аккаунту ключа
 */
function odiTrackUsage(string $keyId): void {
    $account = odiGetAccountForKey($keyId);
    $usage = odiLoadUsage();
    $usage['accounts'][$account] = ($usage['accounts'][$account] ?? 0) + 1;
    // Для совместимости храним и по ключу
    $usage['keys'][$keyId] = ($usage['keys'][$keyId] ?? 0) + 1;
    odiSaveUsage($usage);
}

/**
 * Получить использование аккаунта
 */
function odiGetAccountUsage(string $account): int {
    $usage = odiLoadUsage();
    return (int)($usage['accounts'][$account] ?? 0);
}

/**
 * Получить доступные ключи (с учётом лимита на аккаунт)
 */
function odiGetAvailableKeys(string $type = 'text'): array {
    $keys = odiLoadKeys();
    $usage = odiLoadUsage();

    $settingsFile = __DIR__ . '/../data/site-settings.json';
    $settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    $mainKey = $settings['odirouter_api_key'] ?? '';
    $imageKey = $settings['odirouter_image_api_key'] ?? '';

    $allKeys = [];
    foreach ($keys as $k) {
        if (empty($k['key']) || empty($k['enabled'])) continue;
        if ($type === 'image' && !empty($k['type']) && $k['type'] === 'text') continue;
        if ($type === 'text' && !empty($k['type']) && $k['type'] === 'image') continue;
        $allKeys[] = $k;
    }

    $poolKeyValues = array_column($allKeys, 'key');
    if ($mainKey && !in_array($mainKey, $poolKeyValues, true)) {
        $allKeys[] = ['key' => $mainKey, 'id' => 'settings_main', 'name' => 'Основной (настройки)', 'account' => '_settings_main', 'enabled' => true, 'type' => 'all'];
    }
    if ($imageKey && $imageKey !== $mainKey && !in_array($imageKey, $poolKeyValues, true)) {
        $allKeys[] = ['key' => $imageKey, 'id' => 'settings_image', 'name' => 'Картинки (настройки)', 'account' => '_settings_image', 'enabled' => true, 'type' => 'all'];
    }

    $result = [];
    foreach ($allKeys as $k) {
        $keyId = $k['id'] ?? md5($k['key']);
        $account = odiNormalizeAccountId($k['account'] ?? '', $keyId);
        
        $accountUsed = (int)($usage['accounts'][$account] ?? 0);
        $accountRemaining = ODIROUTER_DAILY_LIMIT - $accountUsed;
        if ($accountRemaining <= 0) continue;
        if (odiIsAccountDisabled($account)) continue; // аккаунт выключен вручную
        
        $result[] = [
            'key' => $k['key'],
            'id' => $keyId,
            'name' => $k['name'] ?? ('Ключ ' . substr($k['key'], 0, 8) . '...'),
            'account' => $account,
            'remaining' => $accountRemaining,
            'used' => $accountUsed,
            'type' => $k['type'] ?? 'all',
        ];
    }

    // Оставляем по одному лучшему ключу на аккаунт для текущего типа.
    $byAccount = [];
    foreach ($result as $item) {
        $acc = $item['account'];
        $score = (($item['type'] ?? 'all') === $type) ? 2 : 1;
        if (!isset($byAccount[$acc])) {
            $item['_score'] = $score;
            $byAccount[$acc] = $item;
            continue;
        }
        if ($score > ($byAccount[$acc]['_score'] ?? 0)) {
            $item['_score'] = $score;
            $byAccount[$acc] = $item;
        }
    }
    $result = array_values($byAccount);
    foreach ($result as &$row) unset($row['_score']);
    unset($row);

    // Round-robin по аккаунтам между последовательными вызовами.
    return odiRotateAccounts($result, $type);
}

/**
 * Пометить аккаунт ключа как исчерпанный
 */
function odiMarkKeyExhausted(string $keyId): void {
    $account = odiGetAccountForKey($keyId);
    $usage = odiLoadUsage();
    $usage['accounts'][$account] = ODIROUTER_DAILY_LIMIT;
    odiSaveUsage($usage);
}

function odiGetActiveKey(string $type = 'text'): ?array {
    $keys = odiGetAvailableKeys($type);
    return $keys[0] ?? null;
}

/**
 * Статистика по всем ключам — группировка по аккаунтам
 */
function odiGetKeysStats(): array {
    $keys = odiLoadKeys();
    $usage = odiLoadUsage();
    $settings = file_exists(__DIR__ . '/../data/site-settings.json') 
        ? json_decode(file_get_contents(__DIR__ . '/../data/site-settings.json'), true) : [];
    
    $stats = [];
    
    // Собираем все ключи (пул + настройки)
    $allKeys = $keys;
    $poolKeyValues = array_column($keys, 'key');
    $mainKey = $settings['odirouter_api_key'] ?? '';
    $imageKey = $settings['odirouter_image_api_key'] ?? '';
    
    if ($mainKey && !in_array($mainKey, $poolKeyValues)) {
        $allKeys[] = ['id' => 'settings_main', 'key' => $mainKey, 'name' => 'Основной (настройки)', 'account' => '_settings_main', 'type' => 'all', 'enabled' => true];
    }
    if ($imageKey && $imageKey !== $mainKey && !in_array($imageKey, $poolKeyValues)) {
        $allKeys[] = ['id' => 'settings_image', 'key' => $imageKey, 'name' => 'Картинки (настройки)', 'account' => '_settings_image', 'type' => 'all', 'enabled' => true];
    }
    
    // Считаем использование по аккаунтам
    $accountUsageMap = [];
    foreach ($allKeys as $k) {
        $keyId = $k['id'] ?? md5($k['key'] ?? '');
        $account = odiNormalizeAccountId($k['account'] ?? '', $keyId);
        if (!isset($accountUsageMap[$account])) {
            $accountUsageMap[$account] = (int)($usage['accounts'][$account] ?? 0);
        }
    }
    
    $totalRemaining = 0;
    
    foreach ($allKeys as $k) {
        $keyId = $k['id'] ?? md5($k['key'] ?? '');
        $account = odiNormalizeAccountId($k['account'] ?? '', $keyId);
        $accountUsed = $accountUsageMap[$account] ?? 0;
        $keyUsed = (int)($usage['keys'][$keyId] ?? 0);
        $accountRemaining = max(0, ODIROUTER_DAILY_LIMIT - $accountUsed);
        
        $stats[] = [
            'id' => $keyId,
            'name' => $k['name'] ?? '',
            'account' => $account,
            'type' => $k['type'] ?? 'all',
            'enabled' => !empty($k['enabled']),
            'key_used' => $keyUsed,           // использовано этим ключом
            'account_used' => $accountUsed,    // использовано всем аккаунтом
            'account_remaining' => $accountRemaining,
            'account_disabled' => odiIsAccountDisabled($account),
            'limit' => ODIROUTER_DAILY_LIMIT,
            'masked' => substr($k['key'] ?? '', 0, 8) . '...' . substr($k['key'] ?? '', -4),
        ];
    }
    
    // Считаем уникальные аккаунты для total_remaining
    $uniqueAccounts = [];
    foreach ($stats as $s) {
        $acc = $s['account'];
        if (!isset($uniqueAccounts[$acc])) {
            $uniqueAccounts[$acc] = $s['account_remaining'];
            $totalRemaining += $s['account_remaining'];
        }
    }
    
    return [
        'keys' => $stats,
        'total_remaining' => $totalRemaining,
        'total_keys' => count($stats),
        'total_accounts' => count($uniqueAccounts),
        'date' => $usage['date'] ?? gmdate('Y-m-d'),
    ];
}

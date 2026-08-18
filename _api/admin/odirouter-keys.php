<?php
/**
 * API управления пулом ключей OdiRouter
 * GET — список ключей и статистика
 * POST — добавить/удалить/переключить ключ
 */
require_once __DIR__ . '/../../includes/odirouter-keys.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(odiGetKeysStats(), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $data['action'] ?? '';
    
    if ($action === 'add') {
        $key = trim((string)($data['key'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $type = in_array($data['type'] ?? 'all', ['all','text','image']) ? $data['type'] : 'all';
        
        if (!$key) {
            echo json_encode(['error' => 'Ключ обязателен']);
            exit;
        }
        
        $keys = odiLoadKeys();
        $id = 'pool_' . substr(md5($key . time()), 0, 8);
        $account = trim((string)($data['account'] ?? ''));
        $keys[] = [
            'id' => $id,
            'key' => $key,
            'name' => $name ?: ('Ключ #' . (count($keys) + 1)),
            'account' => $account,
            'type' => $type,
            'enabled' => true,
            'added' => date('Y-m-d H:i:s'),
        ];
        odiSaveKeys($keys);
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
    
    if ($action === 'remove') {
        $id = $data['id'] ?? '';
        
        // Если удаляют ключ из настроек — очищаем настройку
        if ($id === 'settings_main' || $id === 'settings_image') {
            $settingsFile = __DIR__ . '/../../data/site-settings.json';
            if (file_exists($settingsFile)) {
                $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
                if ($id === 'settings_main') $settings['odirouter_api_key'] = '';
                if ($id === 'settings_image') $settings['odirouter_image_api_key'] = '';
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        
        // Удаляем из пула
        $keys = odiLoadKeys();
        $keys = array_values(array_filter($keys, function($k) use ($id) {
            return ($k['id'] ?? '') !== $id;
        }));
        odiSaveKeys($keys);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'toggle') {
        $id = $data['id'] ?? '';
        $keys = odiLoadKeys();
        foreach ($keys as &$k) {
            if (($k['id'] ?? '') === $id) {
                $k['enabled'] = !$k['enabled'];
                break;
            }
        }
        unset($k);
        odiSaveKeys($keys);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'update') {
        $id = $data['id'] ?? '';
        $keys = odiLoadKeys();
        foreach ($keys as &$k) {
            if (($k['id'] ?? '') === $id) {
                if (isset($data['name'])) $k['name'] = trim((string)$data['name']);
                if (isset($data['account'])) $k['account'] = trim((string)$data['account']);
                if (isset($data['type'])) $k['type'] = in_array($data['type'], ['all','text','image']) ? $data['type'] : 'all';
                break;
            }
        }
        unset($k);
        odiSaveKeys($keys);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'reset') {
        @unlink(ODIROUTER_USAGE_FILE);
        echo json_encode(['success' => true, 'message' => 'Счётчики сброшены']);
        exit;
    }
    
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

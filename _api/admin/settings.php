<?php
/**
 * API настроек сайта
 * GET — получить настройки
 * POST — сохранить настройки
 * POST с файлом — загрузить логотип
 */

$settingsFile = __DIR__ . '/../../data/site-settings.json';
$logoDir = __DIR__ . '/../../images';
$method = $_SERVER['REQUEST_METHOD'];

// Загрузка текущих настроек
function loadSettings(): array {
    global $settingsFile;
    $defaults = [
        'site_name' => 'Космозайм',
        'site_url' => 'https://kosmozaim.ru',
        'site_logo' => '',
        'site_favicon' => '',
        'yandex_gpt_api_key' => '',
        'yandex_folder_id' => '',
        'yandex_metrika_id' => '',
        'google_analytics_id' => '',
        'contact_email' => '',
        'smtp_enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 465,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'ssl',
        'mail_from' => '',
        'mail_from_name' => '',
        'article_image_prompt_template' => 'нарисуй 16:9 {title}',
        'article_image_provider' => 'yandex',
        'stability_api_key' => '',
        'gigachat_auth_key' => '',
        'gigachat_scope' => 'GIGACHAT_API_PERS',
        'social_proof_enabled' => true,
        'social_proof_interval' => 8000,
        'social_proof_duration' => 5000,
        'social_proof_min_amount' => 5000,
        'social_proof_max_amount' => 30000,
        'social_proof_position' => 'bottom-left',
    ];
    
    // Сначала из .env
    $defaults['yandex_gpt_api_key'] = getenv('YANDEX_GPT_API_KEY') ?: '';
    $defaults['yandex_folder_id'] = getenv('YANDEX_FOLDER_ID') ?: '';
    $defaults['yandex_metrika_id'] = getenv('NEXT_PUBLIC_YANDEX_METRIKA_ID') ?: '';
    $defaults['google_analytics_id'] = getenv('NEXT_PUBLIC_GOOGLE_ANALYTICS_ID') ?: '';
    $defaults['site_url'] = getenv('NEXT_PUBLIC_SITE_URL') ?: 'https://kosmozaim.ru';
    
    // Переопределяем из JSON если есть
    if (file_exists($settingsFile)) {
        $json = json_decode(file_get_contents($settingsFile), true);
        if ($json) {
            $defaults = array_merge($defaults, $json);
        }
    }
    
    return $defaults;
}

// GET — получить настройки
if ($method === 'GET') {
    $settings = loadSettings();
    // Маскируем API ключ для безопасности
    $masked = $settings;
    if (!empty($masked['leads_su_api_token'])) {
        $key = $masked['leads_su_api_token'];
        $masked['leads_su_api_token_masked'] = substr($key, 0, 8) . '...' . substr($key, -4);
    }
    if ($masked['yandex_gpt_api_key']) {
        $key = $masked['yandex_gpt_api_key'];
        $masked['yandex_gpt_api_key_masked'] = substr($key, 0, 8) . '...' . substr($key, -4);
    }
    if (!empty($masked['stability_api_key'])) {
        $key = (string)$masked['stability_api_key'];
        $masked['stability_api_key_masked'] = substr($key, 0, 6) . '...' . substr($key, -4);
    }
    if (!empty($masked['gigachat_auth_key'])) {
        $key = (string)$masked['gigachat_auth_key'];
        $masked['gigachat_auth_key_masked'] = substr($key, 0, 6) . '...' . substr($key, -4);
    }
    echo json_encode(['settings' => $masked]);
    exit;
}

// POST — сохранить настройки или загрузить логотип
if ($method === 'POST') {
    
    // Загрузка favicon
    if (!empty($_FILES['favicon'])) {
        $file = $_FILES['favicon'];
        $allowed = ['image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
        
        if (!in_array($file['type'], $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Разрешены: PNG, SVG, ICO']);
            exit;
        }
        
        if ($file['size'] > 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Максимальный размер 1 МБ']);
            exit;
        }
        
        $ext = match($file['type']) {
            'image/png' => 'png',
            'image/svg+xml' => 'svg',
            default => 'ico'
        };
        
        $filename = 'favicon.' . $ext;
        $destPath = __DIR__ . '/../../' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $settings = loadSettings();
            $settings['site_favicon'] = '/' . $filename;
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo json_encode(['success' => true, 'favicon' => '/' . $filename]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки']);
        }
        exit;
    }
    
    // Загрузка логотипа
    if (!empty($_FILES['logo'])) {
        $file = $_FILES['logo'];
        $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
        
        if (!in_array($file['type'], $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Разрешены только PNG, JPG, SVG, WebP']);
            exit;
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Максимальный размер 2 МБ']);
            exit;
        }
        
        $ext = match($file['type']) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
            default => 'png'
        };
        
        $filename = 'logo-' . time() . '.' . $ext;
        $destPath = $logoDir . '/' . $filename;
        
        if (!is_dir($logoDir)) @mkdir($logoDir, 0755, true);
        
        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            // Сохраняем путь к логотипу в настройках
            $settings = loadSettings();
            
            // Удаляем старый логотип
            if ($settings['site_logo'] && file_exists(__DIR__ . '/../../' . ltrim($settings['site_logo'], '/'))) {
                @unlink(__DIR__ . '/../../' . ltrim($settings['site_logo'], '/'));
            }
            
            $settings['site_logo'] = '/images/' . $filename;
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            echo json_encode(['success' => true, 'logo' => $settings['site_logo']]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка загрузки файла']);
        }
        exit;
    }
    
    // Сохранение настроек из JSON
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Неверные данные']);
        exit;
    }
    
    $settings = loadSettings();
    
    // Обновляем только переданные поля
    $allowedFields = ['site_name', 'site_url', 'site_favicon', 'yandex_gpt_api_key', 'yandex_folder_id', 'yandex_metrika_id', 'google_analytics_id', 'contact_email', 'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure', 'mail_from', 'mail_from_name', 'article_image_prompt_template', 'article_image_provider', 'stability_api_key', 'gigachat_auth_key', 'gigachat_scope', 'leads_su_api_token', 'social_proof_enabled', 'social_proof_interval', 'social_proof_duration', 'social_proof_min_amount', 'social_proof_max_amount', 'social_proof_position'];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            if (in_array($field, ['yandex_gpt_api_key','stability_api_key','gigachat_auth_key','leads_su_api_token'], true) && (!is_string($data[$field]) || $data[$field] === '' || strpos((string)$data[$field], '...') !== false)) {
                continue;
            }
            if (in_array($field, ['smtp_enabled', 'social_proof_enabled'], true)) {
                $settings[$field] = !empty($data[$field]);
            } elseif (in_array($field, ['smtp_port', 'social_proof_interval', 'social_proof_duration', 'social_proof_min_amount', 'social_proof_max_amount'], true)) {
                $settings[$field] = (int)$data[$field];
            } else {
                $settings[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }
    }
    
    // Сохраняем в JSON
    $dataDir = dirname($settingsFile);
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    
    if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        // Также обновляем .env для совместимости
        updateEnvFile($settings);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Не удалось сохранить']);
    }
    exit;
}

// Обновление .env файла
function updateEnvFile(array $settings): void {
    $envFile = __DIR__ . '/../../.env';
    if (!file_exists($envFile)) return;
    
    $content = file_get_contents($envFile);
    $lines = explode("\n", $content);
    $newLines = [];
    $updated = [];
    
    $mapping = [
        'NEXT_PUBLIC_SITE_URL' => $settings['site_url'] ?? '',
        'YANDEX_GPT_API_KEY' => $settings['yandex_gpt_api_key'] ?? '',
        'YANDEX_FOLDER_ID' => $settings['yandex_folder_id'] ?? '',
        'NEXT_PUBLIC_YANDEX_METRIKA_ID' => $settings['yandex_metrika_id'] ?? '',
        'NEXT_PUBLIC_GOOGLE_ANALYTICS_ID' => $settings['google_analytics_id'] ?? '',
    ];
    
    foreach ($lines as $line) {
        $found = false;
        foreach ($mapping as $key => $value) {
            if (strpos($line, $key . '=') === 0) {
                if ($value) {
                    $newLines[] = $key . '=' . $value;
                    $updated[$key] = true;
                }
                $found = true;
                break;
            }
        }
        if (!$found) {
            $newLines[] = $line;
        }
    }
    
    // Добавляем отсутствующие
    foreach ($mapping as $key => $value) {
        if ($value && empty($updated[$key])) {
            $newLines[] = $key . '=' . $value;
        }
    }
    
    file_put_contents($envFile, implode("\n", $newLines));
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);

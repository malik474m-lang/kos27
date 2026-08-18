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
        $type = odiDetectKeyType($data['name'] ?? '', $data['type'] ?? null);
        
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
                $k['type'] = odiDetectKeyType($data['name'] ?? ($k['name'] ?? ''), $data['type'] ?? ($k['type'] ?? null));
                break;
            }
        }
        unset($k);
        odiSaveKeys($keys);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'test') {
        // Тест конкретного аккаунта (или авто-первого доступного)
        require_once __DIR__ . '/../../includes/ai-providers.php';
        $account = trim((string)($data['account'] ?? ''));
        $testType = $data['type'] ?? 'text';

        $available = odiGetAvailableKeys($testType);
        if (!$available) {
            echo json_encode(['error' => 'Нет доступных ключей для теста']);
            exit;
        }

        $selected = null;
        if ($account !== '') {
            foreach ($available as $k) {
                $label = ($k['account'] ?? '') !== '' ? ($k['account'] ?? '') : 'Без аккаунта';
                if ($label === $account) { $selected = $k; break; }
            }
            if (!$selected) {
                echo json_encode(['error' => 'Для выбранного аккаунта нет доступного ключа этого типа']);
                exit;
            }
        } else {
            $selected = $available[0];
        }

        $targetKey = $selected['key'];
        $accountLabel = ($selected['account'] ?? '') !== '' ? $selected['account'] : 'Без аккаунта';
        
        if ($testType === 'text') {
            $config = getAIProvidersConfig();
            $model = $config['odirouter_text_model'] ?? 'free-gemini-2.5-flash';
            $ch = curl_init("https://api.odirouter.ai/v1/chat/completions");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 18,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $targetKey,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'Напиши 1 короткий абзац из 2-3 предложений о том, как выбрать займ без ошибок.']],
                    'max_tokens' => 180,
                ]),
            ]);
            $response = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($code >= 200 && $code < 300) {
                $data = json_decode($response, true);
                $text = $data['choices'][0]['message']['content'] ?? 'OK';
                echo json_encode(['success' => true, 'account_label' => $accountLabel, 'key_name' => ($selected['name'] ?? ''), 'key_type' => ($selected['type'] ?? ''), 'result' => "HTTP {$code}: {$text} (модель: {$model})"]);
            } else {
                if ($code === 429) { odiSetAccountCooldown($selected['account'] ?? $accountLabel); }
                echo json_encode(['error' => "HTTP {$code}", 'response' => $response]);
            }
        } else {
            // Полноценный тест картинки: submit -> poll -> response
            $config = getAIProvidersConfig();
            $model = $config['odirouter_image_model'] ?? 'free-nano-banana-2';
            $ch = curl_init("https://api.odirouter.ai/model/v1/queue/{$model}");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $targetKey,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'prompt' => 'simple blue circle on white background, minimal illustration',
                    'aspect_ratio' => '16:9',
                    'resolution' => '1K',
                ]),
            ]);
            $response = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                echo json_encode(['error' => 'cURL ' . $err]);
                exit;
            }
            if ($code < 200 || $code >= 300) {
                if ($code === 429) { odiSetAccountCooldown($selected['account'] ?? $accountLabel); }
                echo json_encode(['error' => "HTTP {$code}", 'response' => $response]);
                exit;
            }

            $data = json_decode($response, true);
            $statusUrl = $data['status_url'] ?? '';
            $responseUrl = $data['response_url'] ?? '';
            $reqId = $data['request_id'] ?? ($data['id'] ?? 'unknown');
            if (!$statusUrl || !$responseUrl) {
                echo json_encode(['error' => 'invalid queue response', 'response' => $response]);
                exit;
            }

            $ok = false;
            for ($i = 0; $i < 12; $i++) {
                sleep(4);
                $ch = curl_init($statusUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $targetKey],
                ]);
                $statusResp = curl_exec($ch);
                $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $statusErr = curl_error($ch);
                curl_close($ch);

                if ($statusErr) {
                    echo json_encode(['error' => 'status cURL ' . $statusErr]);
                    exit;
                }
                if ($statusCode === 429) { odiSetAccountCooldown($selected['account'] ?? $accountLabel); }
                if ($statusCode < 200 || $statusCode >= 300) {
                    echo json_encode(['error' => 'status HTTP ' . $statusCode, 'response' => $statusResp]);
                    exit;
                }

                $statusData = json_decode($statusResp, true);
                $status = $statusData['status'] ?? '';
                if ($status === 'COMPLETED') {
                    if (!empty($statusData['error'])) {
                        echo json_encode(['error' => $statusData['error']['message'] ?? 'task failed']);
                        exit;
                    }

                    $ch = curl_init($responseUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $targetKey],
                    ]);
                    $resultResp = curl_exec($ch);
                    $resultCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $resultErr = curl_error($ch);
                    curl_close($ch);

                    if ($resultErr) {
                        echo json_encode(['error' => 'response cURL ' . $resultErr]);
                        exit;
                    }
                    if ($resultCode === 429) { odiSetAccountCooldown($selected['account'] ?? $accountLabel); }
                    if ($resultCode < 200 || $resultCode >= 300) {
                        echo json_encode(['error' => 'response HTTP ' . $resultCode, 'response' => $resultResp]);
                        exit;
                    }

                    $ok = true;
                    echo json_encode([
                        'success' => true,
                        'account_label' => $accountLabel,
                        'key_name' => ($selected['name'] ?? ''),
                        'key_type' => ($selected['type'] ?? ''),
                        'result' => "HTTP {$code}: изображение полностью сгенерировано (модель: {$model}, id: {$reqId})"
                    ]);
                    exit;
                }
            }

            echo json_encode(['error' => 'timeout waiting for image generation', 'request_id' => $reqId]);
        }
        exit;
    }
    
    if ($action === 'toggle-account') {
        $account = trim((string)($data['account'] ?? ''));
        if (!$account) { echo json_encode(['error' => 'account required']); exit; }
        $disabled = odiGetDisabledAccounts();
        if (in_array($account, $disabled, true)) {
            $disabled = array_values(array_filter($disabled, fn($a) => $a !== $account));
        } else {
            $disabled[] = $account;
        }
        odiSetDisabledAccounts($disabled);
        echo json_encode(['success' => true, 'disabled' => in_array($account, $disabled, true)]);
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

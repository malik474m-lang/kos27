<?php
require_once __DIR__ . '/../../includes/leads-su-api.php';

$action = $_GET['action'] ?? 'offers';

try {
    switch ($action) {
        case 'offers':
            $platformId = (int)($_GET['platform_id'] ?? 0);
            echo json_encode(leadsSuGetOffers(0, $platformId));
            break;

        case 'platforms':
            echo json_encode(leadsSuGetPlatforms());
            break;

        case 'categories':
            echo json_encode(leadsSuGetCategories());
            break;

        case 'import':
            $data = json_decode(file_get_contents('php://input'), true);
            $offersToImport = $data['offers'] ?? [];
            $platformId = (int)($data['platform_id'] ?? 0);
            $activate = (bool)($data['activate'] ?? false);

            if (!$offersToImport || !$platformId) {
                echo json_encode(['error' => 'offers и platform_id обязательны']);
                exit;
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($offersToImport as $apiOffer) {
                $result = leadsSuImportOffer($apiOffer, $platformId, $activate);
                if ($result['ok']) {
                    $imported++;
                } elseif (!empty($result['skipped'])) {
                    $skipped++;
                } else {
                    $errors[] = ($apiOffer['name'] ?? '?') . ': ' . ($result['error'] ?? 'unknown');
                }
            }

            echo json_encode(['success' => true, 'imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 10)]);
            break;

        case 'test':
            $token = getLeadsSuToken();
            if (!$token) {
                echo json_encode(['ok' => false, 'error' => 'Токен не настроен. Укажите leads_su_api_token в Настройках.']);
                exit;
            }
            $result = leadsSuRequest('account');
            echo json_encode($result);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

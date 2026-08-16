<?php
/**
 * API для модуля Яндекс Директ
 */
require_once __DIR__ . '/../../includes/yandex-direct.php';

$action = $_GET['action'] ?? 'report';

switch ($action) {
    case 'report':
        // Отчёт по рекламному трафику
        $days = (int)($_GET['days'] ?? 30);
        echo json_encode(generateDirectReport($days));
        break;
        
    case 'generate-ads':
        // Генерация объявлений для офферов
        $category = $_GET['category'] ?? null;
        $template = $_GET['template'] ?? 'default';
        
        $db = getDB();
        $sql = "SELECT * FROM offers WHERE is_active = 1";
        if ($category) {
            $sql .= " AND category = ?";
            $stmt = $db->prepare($sql . " ORDER BY sort_order ASC LIMIT 50");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query($sql . " ORDER BY sort_order ASC LIMIT 50");
        }
        $offers = $stmt->fetchAll();
        
        $ads = [];
        foreach ($offers as $offer) {
            $ads[] = [
                'offer_id' => $offer['id'],
                'offer_title' => $offer['title'],
                'offer_slug' => $offer['slug'],
                'category' => $offer['category'],
                'ad' => generateDirectAd($offer, $template),
                'url' => generateDirectUrl('/offer/' . $offer['slug'], [
                    'utm_campaign' => 'direct_' . $offer['category'],
                    'utm_content' => $offer['slug'],
                ]),
            ];
        }
        echo json_encode(['ads' => $ads, 'count' => count($ads)]);
        break;
        
    case 'export-csv':
        // Экспорт в CSV для загрузки в Директ
        $category = $_GET['category'] ?? null;
        $campaignName = $_GET['campaign'] ?? 'Космозайм';
        
        $db = getDB();
        $sql = "SELECT * FROM offers WHERE is_active = 1";
        if ($category) {
            $stmt = $db->prepare($sql . " AND category = ? ORDER BY sort_order ASC LIMIT 50");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query($sql . " ORDER BY sort_order ASC LIMIT 50");
        }
        $offers = $stmt->fetchAll();
        
        $csv = exportDirectAdsCSV($offers, $campaignName);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="yandex-direct-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM для Excel
        echo $csv;
        exit;
        
    case 'keywords':
        // Ключевые слова по категории
        $category = $_GET['category'] ?? 'microloans';
        echo json_encode([
            'category' => $category,
            'keywords' => getDirectKeywords($category),
            'minus_words' => getDirectMinusWords(),
        ]);
        break;
        
    case 'preview-ad':
        // Превью объявления для конкретного оффера
        $offerId = (int)($_GET['offer_id'] ?? 0);
        $template = $_GET['template'] ?? 'default';
        
        if (!$offerId) {
            echo json_encode(['error' => 'offer_id required']);
            exit;
        }
        
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM offers WHERE id = ?");
        $stmt->execute([$offerId]);
        $offer = $stmt->fetch();
        
        if (!$offer) {
            echo json_encode(['error' => 'Offer not found']);
            exit;
        }
        
        $templates = ['default', 'urgent', 'free', 'trust', 'comparison'];
        $previews = [];
        foreach ($templates as $tpl) {
            $previews[$tpl] = generateDirectAd($offer, $tpl);
        }
        
        echo json_encode([
            'offer' => ['id' => $offer['id'], 'title' => $offer['title'], 'slug' => $offer['slug']],
            'selected_template' => $template,
            'ad' => $previews[$template],
            'all_templates' => $previews,
        ]);
        break;
        
    case 'analytics':
        // Детальная аналитика
        $days = (int)($_GET['days'] ?? 30);
        echo json_encode(analyzeDirectTraffic($days));
        break;
        
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['report', 'generate-ads', 'export-csv', 'keywords', 'preview-ad', 'analytics']]);
}

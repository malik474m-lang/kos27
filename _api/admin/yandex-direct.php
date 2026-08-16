<?php
/**
 * API для модуля Яндекс Директ
 */
require_once __DIR__ . '/../../includes/yandex-direct.php';

$action = $_GET['action'] ?? 'report';

try {
    switch ($action) {
        case 'report':
            $days = (int)($_GET['days'] ?? 30);
            echo json_encode(generateDirectReport($days));
            break;

        case 'generate-ads':
            $category = $_GET['category'] ?? null;
            $template = $_GET['template'] ?? 'default';

            $db = getDB();
            $sql = "SELECT * FROM offers WHERE is_active = 1";
            if ($category) {
                $stmt = $db->prepare($sql . " AND category = ? ORDER BY sort_order ASC LIMIT 50");
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
            echo "\xEF\xBB\xBF";
            echo $csv;
            exit;

        case 'keywords':
            $category = $_GET['category'] ?? 'microloans';
            echo json_encode([
                'category' => $category,
                'keywords' => getDirectKeywords($category),
                'minus_words' => getDirectMinusWords(),
            ]);
            break;

        case 'preview-ad':
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
            $days = (int)($_GET['days'] ?? 30);
            echo json_encode(analyzeDirectTraffic($days));
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

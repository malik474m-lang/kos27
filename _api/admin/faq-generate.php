<?php
require_once __DIR__ . "/../../includes/ai-compat.php";
require_once __DIR__ . '/../../includes/offer-faq.php';
/**
 * Генерация FAQ для оффера — YandexGPT + шаблонный fallback
 */
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$offerId = (int)($data['offer_id'] ?? 0);

if (!$offerId) { echo json_encode(['error' => 'offer_id required']); exit; }

$db = getDB();

// Создаём таблицу если нет
try { $db->query("SELECT 1 FROM offer_faqs LIMIT 1"); } catch (Exception $e) {
    $db->exec("CREATE TABLE IF NOT EXISTS `offer_faqs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `offer_id` int(11) NOT NULL,
        `question` varchar(500) NOT NULL,
        `answer` text NOT NULL,
        `sort_order` int(11) NOT NULL DEFAULT 0,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `generated_by` enum('template','yandexgpt','manual') NOT NULL DEFAULT 'template',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_offer` (`offer_id`, `is_active`, `sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

$stmt = $db->prepare("SELECT * FROM offers WHERE id = ? LIMIT 1");
$stmt->execute([$offerId]);
$offer = $stmt->fetch();
if (!$offer) { echo json_encode(['error' => 'Оффер не найден']); exit; }

$catNames = ['microloans'=>'микрозайм','credits'=>'кредит','credit_cards'=>'кредитная карта','debit_cards'=>'дебетовая карта'];
$catName = $catNames[$offer['category']] ?? 'финансовый продукт';
$title = $offer['title'];
$amountMin = number_format($offer['amount_min'], 0, '', ' ');
$amountMax = number_format($offer['amount_max'], 0, '', ' ');
$rate = $offer['rate'];
$freeTerm = (int)$offer['free_term_days'];
$termMin = (int)$offer['term_min_days'];
$termMax = (int)$offer['term_max_days'];

$provider = 'template';
$faqs = [];

// Пробуем YandexGPT
$apiKey = YANDEX_GPT_API_KEY;
$folderId = YANDEX_FOLDER_ID;

if ($apiKey && $folderId) {
    $hints = offerFaqPromptHints($offer);
    $prompt = "Сгенерируй 6 вопросов и ответов (FAQ) для страницы финансового продукта \"{$title}\".
"
        . "Тип продукта: {$catName}.
"
        . "Сумма: от {$amountMin} до {$amountMax} рублей.
"
        . "Ставка: от {$rate}% " . (($offer['rate_unit'] ?? 'day') === 'year' ? 'в год' : 'в день') . ".
"
        . "Срок: от {$termMin} до {$termMax} дней.
"
        . ($freeTerm > 0 ? "Льготный период: {$freeTerm} дней.\n" : "")
        . "Вопросы должны быть полезными именно для этого типа продукта.
"
        . "Освети темы: " . implode(', ', $hints['topics']) . ".
"
        . "Ограничения: " . implode('; ', $hints['forbidden']) . ".
"
        . "Ответы 2-4 предложения, конкретные, без воды, без фактических ошибок.

"
        . "Верни строго JSON массив объектов [{\"q\":\"вопрос\",\"a\":\"ответ\"}] без markdown.";

    $response = kosmozaimAIComplete('Ты SEO-копирайтер финансового сайта. Генерируешь FAQ. Отвечай только валидным JSON массивом без markdown.', $prompt);
if ($response) {
        $text = $response;
        // Извлекаем JSON из ответа
        $text = trim($text);
        $text = preg_replace('/^```json\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        $parsed = json_decode($text, true);
        if (is_array($parsed) && count($parsed) >= 3) {
            foreach ($parsed as &$_fq) { $_fq['q'] = preg_replace('/\*\*(.+?)\*\*/', '$1', $_fq['q'] ?? ''); $_fq['a'] = preg_replace('/\*\*(.+?)\*\*/', '$1', $_fq['a'] ?? ''); $_fq['q'] = preg_replace('/^#{1,6}\s+/m', '', $_fq['q']); $_fq['a'] = preg_replace('/^#{1,6}\s+/m', '', $_fq['a']); } unset($_fq);
            $faqs = $parsed;
            $provider = 'yandexgpt';
        }
    }
}

// Fallback — шаблонные FAQ
if (empty($faqs)) {
    $faqs = generateOfferFallbackFaqs($offer);
}

// Удаляем старые FAQ для этого оффера
$db->prepare("DELETE FROM offer_faqs WHERE offer_id = ?")->execute([$offerId]);

// Сохраняем
$insertStmt = $db->prepare("INSERT INTO offer_faqs (offer_id, question, answer, sort_order, generated_by) VALUES (?, ?, ?, ?, ?)");
foreach ($faqs as $i => $faq) {
    $insertStmt->execute([$offerId, $faq['q'], $faq['a'], $i, $provider]);
}

echo json_encode([
    'success' => true,
    'offer_id' => $offerId,
    'offer_title' => $title,
    'count' => count($faqs),
    'provider' => $provider,
    'faqs' => $faqs
]);

<?php
/**
 * Пакетная генерация FAQ для всех офферов без FAQ
 */
$db = getDB();
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

$offers = $db->query("SELECT id FROM offers WHERE is_active = 1 AND id NOT IN (SELECT DISTINCT offer_id FROM offer_faqs)")->fetchAll();
$generated = 0;
$errors = 0;

foreach ($offers as $offer) {
    // Вызываем генерацию для каждого оффера
    $_POST_BACKUP = file_get_contents('php://input');
    
    // Подготовка входных данных
    $fakeInput = json_encode(['offer_id' => $offer['id']]);
    
    // Используем внутренний вызов
    ob_start();
    $GLOBALS['_faq_input'] = $fakeInput;
    
    // Прямой вызов логики генерации
    $stmt2 = $db->prepare("SELECT * FROM offers WHERE id = ? LIMIT 1");
    $stmt2->execute([$offer['id']]);
    $offerData = $stmt2->fetch();
    
    if ($offerData) {
        $catNames = ['microloans'=>'микрозайм','credits'=>'кредит','credit_cards'=>'кредитная карта','debit_cards'=>'дебетовая карта'];
        $catName = $catNames[$offerData['category']] ?? 'финансовый продукт';
        $title = $offerData['title'];
        $amountMin = number_format($offerData['amount_min'], 0, '', ' ');
        $amountMax = number_format($offerData['amount_max'], 0, '', ' ');
        $rate = $offerData['rate'];
        $freeTerm = (int)$offerData['free_term_days'];
        
        $faqs = [];
        $faqs[] = ['q' => "Как оформить {$catName} в {$title}?", 'a' => "Для оформления перейдите на сайт {$title}, заполните онлайн-заявку, указав паспортные данные и номер банковской карты. Решение обычно приходит в течение нескольких минут."];
        $faqs[] = ['q' => "Какая сумма доступна в {$title}?", 'a' => "В {$title} можно оформить {$catName} на сумму от {$amountMin} ₽ до {$amountMax} ₽. Точная сумма зависит от вашей кредитной истории."];
        $faqs[] = ['q' => "Какая процентная ставка в {$title}?", 'a' => "Процентная ставка составляет от {$rate}% в день. ПСК — {$offerData['psk']}% годовых."];
        if ($freeTerm > 0) {
            $faqs[] = ['q' => "Есть ли беспроцентный период в {$title}?", 'a' => "Да, {$title} предлагает беспроцентный период {$freeTerm} дней для новых клиентов."];
        }
        $faqs[] = ['q' => "Как быстро придут деньги?", 'a' => "После одобрения деньги поступают на карту в течение 5-15 минут."];
        $faqs[] = ['q' => "Как проверить надёжность {$title}?", 'a' => "Проверьте наличие в реестре ЦБ РФ на сайте cbr.ru."];
        
        $insertStmt = $db->prepare("INSERT INTO offer_faqs (offer_id, question, answer, sort_order, generated_by) VALUES (?, ?, ?, ?, 'template')");
        foreach ($faqs as $i => $faq) {
            $insertStmt->execute([$offer['id'], $faq['q'], $faq['a'], $i]);
        }
        $generated++;
    }
    ob_end_clean();
}

echo json_encode(['success' => true, 'generated' => $generated, 'errors' => $errors]);

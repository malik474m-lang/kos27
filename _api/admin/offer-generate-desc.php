<?php
/**
 * Генерация описания оффера через AI
 * POST: { title, category, amountMin, amountMax, termMinDays, termMaxDays, rate, rateUnit, freeTermDays, borrowerCategory }
 */
require_once __DIR__ . '/../../includes/ai-compat.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$title = trim((string)($data['title'] ?? ''));
$category = trim((string)($data['category'] ?? 'microloans'));

if (!$title) {
    echo json_encode(['error' => 'Заполните название оффера']);
    exit;
}

$catLabels = [
    'microloans' => 'микрозайм',
    'credits' => 'банковский кредит',
    'credit_cards' => 'кредитная карта',
    'debit_cards' => 'дебетовая карта',
];
$catLabel = $catLabels[$category] ?? 'финансовый продукт';

// Собираем параметры
$params = [];
if (!empty($data['amountMin']) || !empty($data['amountMax'])) {
    $params[] = 'сумма: от ' . ($data['amountMin'] ?? '?') . ' до ' . ($data['amountMax'] ?? '?') . ' руб.';
}
if (!empty($data['termMinDays']) || !empty($data['termMaxDays'])) {
    $params[] = 'срок: от ' . ($data['termMinDays'] ?? '?') . ' до ' . ($data['termMaxDays'] ?? '?') . ' дней';
}
if (!empty($data['rate'])) {
    $rateUnit = ($data['rateUnit'] ?? 'day') === 'year' ? 'в год' : 'в день';
    $params[] = 'ставка: от ' . $data['rate'] . '% ' . $rateUnit;
}
if (!empty($data['freeTermDays'])) {
    $params[] = 'беспроцентный период: ' . $data['freeTermDays'] . ' дней';
}
if (!empty($data['borrowerCategory']) && $data['borrowerCategory'] !== 'any') {
    $bcLabels = ['employed'=>'работающие','unemployed'=>'безработные','pensioner'=>'пенсионеры','student'=>'студенты','self_employed'=>'самозанятые'];
    $params[] = 'для: ' . ($bcLabels[$data['borrowerCategory']] ?? $data['borrowerCategory']);
}

$paramsText = $params ? implode('; ', $params) : 'параметры не указаны';

$systemPrompt = "Ты копирайтер финансового сайта. Пишешь описания для карточек финансовых продуктов. Описание должно быть уникальным, информативным и кратким (3-5 предложений, 50-100 слов). Без markdown, без списков, без HTML. Нейтральный тон. Не начинай со слов 'мы', 'наш сайт'. Опирайся на конкретные параметры продукта.";

$prompt = "Напиши уникальное описание для карточки финансового продукта на сайте.\n\n"
    . "Название: {$title}\n"
    . "Тип: {$catLabel}\n"
    . "Параметры: {$paramsText}\n\n"
    . "Требования:\n"
    . "- 3-5 предложений, 50-100 слов\n"
    . "- Укажи ключевые преимущества продукта\n"
    . "- Упомяни конкретные параметры (сумму, ставку, срок)\n"
    . "- Если есть беспроцентный период — обязательно упомяни\n"
    . "- Без рекламных штампов\n"
    . "- Только текст, без форматирования";

$text = kosmozaimAIComplete($systemPrompt, $prompt);

if ($text) {
    // Чистим
    $text = preg_replace('/\*+/', '', $text);
    $text = preg_replace('/^#{1,6}\s*/m', '', $text);
    $text = preg_replace('/```/', '', $text);
    $text = preg_replace('/^["«]|["»]$/', '', trim($text));
    $text = trim($text);
    
    echo json_encode([
        'success' => true,
        'description' => $text,
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Не удалось сгенерировать описание. Проверьте настройки AI провайдера.']);
}

<?php
/**
 * AI-помощник для генерации JSON правил фильтрации
 * Не сохраняет данные — только генерирует подсказки
 */
require_once __DIR__ . '/../../includes/ai-providers.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$queries = $data['queries'] ?? [];
$category = $data['category'] ?? 'microloans';

if (!is_array($queries) || empty($queries)) {
    echo json_encode(['error' => 'Укажите массив запросов']);
    exit;
}

// Очищаем запросы
$queries = array_filter(array_map('trim', $queries));
if (empty($queries)) {
    echo json_encode(['error' => 'Пустой список запросов']);
    exit;
}

// Лимит
if (count($queries) > 50) {
    $queries = array_slice($queries, 0, 50);
}

$catLabels = [
    'microloans' => 'микрозаймы',
    'credits' => 'банковские кредиты',
    'credit_cards' => 'кредитные карты',
    'debit_cards' => 'дебетовые карты'
];
$catLabel = $catLabels[$category] ?? 'финансовые продукты';

// Формируем промпт для AI
$queryList = implode("\n", array_map(function($q, $i) { return ($i+1) . ". " . $q; }, $queries, array_keys($queries)));

$prompt = <<<PROMPT
Ты эксперт по финансовым продуктам России. Тебе нужно сформировать JSON-правила фильтрации офферов для каждого поискового запроса.

Категория: {$catLabel}

Доступные поля для фильтрации:
- term_max_days: максимальный срок в днях (число)
- term_min_days_min: минимальный срок не менее (число)
- term_max_days_min: максимальный срок не менее (число)  
- amount_max_min: максимальная сумма не менее (число в рублях)
- amount_max_max: максимальная сумма не более (число в рублях)
- amount_min_max: минимальная сумма не более (число в рублях)
- free_term_days_min: беспроцентный период минимум дней (число)
- rate_max: максимальная ставка % в день (число с точкой)
- borrower_category: категория заёмщика (employed, unemployed, pensioner, student, self_employed)

Список запросов:
{$queryList}

Для каждого запроса верни JSON-объект с правилами фильтрации. Ответ должен быть строго в формате JSON-массива:
[
  {"query": "Краткосрочный", "icon": "⏱️", "rules": {"term_max_days": 20}},
  {"query": "На 6 месяцев", "icon": "📆", "rules": {"term_max_days_min": 180}},
  ...
]

Выбирай подходящую иконку-эмодзи для каждого запроса.
Если запрос неясен или не относится к финансам — rules должен быть пустым объектом {}.
Отвечай ТОЛЬКО JSON-массивом, без пояснений.
PROMPT;

$systemPrompt = "Ты AI-ассистент финансового сайта. Возвращай только валидный JSON без markdown-разметки.";

$result = aiGenerateText($prompt, $systemPrompt);

if (empty($result['success']) || empty($result['text'])) {
    // Fallback: простые правила по ключевым словам
    $results = generateFallbackRules($queries, $category);
    echo json_encode([
        'success' => true,
        'results' => $results,
        'provider' => 'fallback'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Парсим ответ AI
$text = trim($result['text']);
// Убираем markdown обёртку если есть
$text = preg_replace('/^```\s*json?\s*/i', '', $text);
$text = preg_replace('/\s*```$/i', '', $text);

$parsed = json_decode($text, true);

if (!is_array($parsed)) {
    // Fallback
    $results = generateFallbackRules($queries, $category);
    echo json_encode([
        'success' => true,
        'results' => $results,
        'provider' => 'fallback',
        'ai_raw' => $text
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

echo json_encode([
    'success' => true,
    'results' => $parsed,
    'provider' => $result['provider'] ?? 'ai'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * Fallback: генерация правил по ключевым словам без AI
 */
function generateFallbackRules(array $queries, string $category): array {
    $results = [];
    
    $patterns = [
        // Сроки
        '/краткосрочн|на.*?(неделю|7.*?дн)|быстр/iu' => ['icon' => '⏱️', 'rules' => ['term_max_days' => 14]],
        '/на.*?месяц|30.*?дн/iu' => ['icon' => '📅', 'rules' => ['term_max_days_min' => 30]],
        '/на.*?3.*?месяц|90.*?дн/iu' => ['icon' => '📆', 'rules' => ['term_max_days_min' => 90]],
        '/на.*?6.*?месяц|180.*?дн|полгода/iu' => ['icon' => '📆', 'rules' => ['term_max_days_min' => 180]],
        '/на.*?год|12.*?месяц|365.*?дн/iu' => ['icon' => '🗓️', 'rules' => ['term_max_days_min' => 365]],
        '/долгосроч/iu' => ['icon' => '📆', 'rules' => ['term_max_days_min' => 180]],
        
        // Суммы
        '/мини|микро|небольш|маленьк/iu' => ['icon' => '💵', 'rules' => ['amount_max_max' => 15000]],
        '/до.*?5.*?тыс/iu' => ['icon' => '💵', 'rules' => ['amount_max_max' => 5000]],
        '/до.*?10.*?тыс/iu' => ['icon' => '💵', 'rules' => ['amount_max_max' => 10000]],
        '/до.*?30.*?тыс/iu' => ['icon' => '💰', 'rules' => ['amount_max_max' => 30000]],
        '/до.*?50.*?тыс/iu' => ['icon' => '💰', 'rules' => ['amount_max_max' => 50000]],
        '/до.*?100.*?тыс/iu' => ['icon' => '💰', 'rules' => ['amount_max_max' => 100000]],
        '/крупн|больш|от.*?100|от.*?50/iu' => ['icon' => '💎', 'rules' => ['amount_max_min' => 100000]],
        
        // Условия
        '/без.*?процент|0.*?%|бесплатн|первый.*?бесплатн/iu' => ['icon' => '🎁', 'rules' => ['free_term_days_min' => 1]],
        '/низк.*?(процент|ставк)|выгодн/iu' => ['icon' => '📉', 'rules' => ['rate_max' => 0.5]],
        
        // Способ получения
        '/на.*?карт|онлайн|моментальн|срочн|24.*?час/iu' => ['icon' => '💳', 'rules' => []],
        '/сбербанк/iu' => ['icon' => '🏦', 'rules' => []],
        '/тинькофф/iu' => ['icon' => '💛', 'rules' => []],
        '/альфа/iu' => ['icon' => '🔴', 'rules' => []],
        
        // Категории заёмщиков
        '/пенсионер/iu' => ['icon' => '👴', 'rules' => ['borrower_category' => 'pensioner']],
        '/студент/iu' => ['icon' => '🎓', 'rules' => ['borrower_category' => 'student']],
        '/безработн|без.*?работ/iu' => ['icon' => '🏠', 'rules' => ['borrower_category' => 'unemployed']],
        '/самозанят|ип|предприниматель/iu' => ['icon' => '💼', 'rules' => ['borrower_category' => 'self_employed']],
        
        // Кредитные карты
        '/с.*?кэшбэк|кэшбек|cashback/iu' => ['icon' => '💸', 'rules' => []],
        '/с.*?грейс|льготн.*?период|беспроцентн.*?период/iu' => ['icon' => '⏳', 'rules' => ['free_term_days_min' => 30]],
        '/рассрочк/iu' => ['icon' => '🛒', 'rules' => []],
        '/путешеств|миль|travel/iu' => ['icon' => '✈️', 'rules' => []],
        
        // Дебетовые карты
        '/с.*?процент.*?остат/iu' => ['icon' => '📈', 'rules' => []],
        '/бесплатн.*?обслуж/iu' => ['icon' => '🆓', 'rules' => []],
        '/виртуальн/iu' => ['icon' => '📱', 'rules' => []],
    ];
    
    foreach ($queries as $query) {
        $matched = false;
        foreach ($patterns as $pattern => $data) {
            if (preg_match($pattern, $query)) {
                $results[] = [
                    'query' => $query,
                    'icon' => $data['icon'],
                    'rules' => $data['rules']
                ];
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $results[] = [
                'query' => $query,
                'icon' => '📋',
                'rules' => []
            ];
        }
    }
    
    return $results;
}

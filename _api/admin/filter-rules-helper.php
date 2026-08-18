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

$queries = array_values(array_filter(array_map('trim', $queries)));
if (empty($queries)) {
    echo json_encode(['error' => 'Пустой список запросов']);
    exit;
}
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

$queryList = implode("\n", array_map(function($q, $i) { return ($i+1) . ". " . $q; }, $queries, array_keys($queries)));

$prompt = <<<PROMPT
Ты эксперт по финансовым продуктам России. Сформируй JSON-правила фильтрации офферов для каждого поискового запроса.

Категория: {$catLabel}

Доступные поля для фильтрации:
- term_max_days: максимальный срок в днях (число). Пример: краткосрочный → 20
- term_min_days_min: минимальный срок не менее N дней
- term_max_days_min: максимальный срок не менее N дней. Пример: на 6 месяцев → 180
- amount_max_min: максимальная сумма оффера не менее N руб. Пример: крупный → 100000
- amount_max_max: максимальная сумма оффера не более N руб. Пример: мини → 10000
- amount_min_max: минимальная сумма оффера не более N руб. Пример: от 1000 рублей → 1000
- free_term_days_min: беспроцентный период минимум N дней. Пример: под 0% → 1
- rate_max: максимальная ставка % в день (дробное). Пример: низкий процент → 0.5
- borrower_category: категория заёмщика (employed, unemployed, pensioner, student, self_employed)

ВАЖНО:
- Если запрос описывает способ получения (на карту, на счёт, через госуслуги), возраст (с 18 лет), название банка (сбербанк) или другой признак, которого нет в полях — ставь rules = {} (пустой объект) и пиши пояснение в поле hint.
- Поле hint — краткое пояснение, почему выбраны именно такие правила или почему они пустые.

Список запросов:
{$queryList}

Ответ строго в JSON:
[
  {"query": "Краткосрочный", "icon": "⏱️", "rules": {"term_max_days": 20}, "hint": "Срок до 20 дней"},
  {"query": "С 18 лет на карту", "icon": "🎂", "rules": {}, "hint": "Нет поля возраста в фильтрах, показать все офферы"},
  ...
]
Без пояснений, только JSON.
PROMPT;

$systemPrompt = "Ты AI-ассистент финансового сайта. Возвращай только валидный JSON без markdown-разметки.";

$result = aiGenerateText($prompt, $systemPrompt);

if (empty($result['success']) || empty($result['text'])) {
    $results = generateFallbackRules($queries, $category);
    echo json_encode([
        'success' => true,
        'results' => $results,
        'provider' => 'fallback'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$text = trim($result['text']);
$text = preg_replace('/^```\s*json?\s*/i', '', $text);
$text = preg_replace('/\s*```$/i', '', $text);

$parsed = json_decode($text, true);

if (!is_array($parsed)) {
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

    // Порядок важен — сначала более специфичные паттерны
    $patterns = [
        // === Сроки ===
        '/на\s*(1|одну)\s*недел/iu' => ['icon'=>'📅','rules'=>['term_max_days'=>7],'hint'=>'Срок до 7 дней'],
        '/на\s*2\s*недел/iu' => ['icon'=>'📅','rules'=>['term_max_days'=>14],'hint'=>'Срок до 14 дней'],
        '/краткосрочн/iu' => ['icon'=>'⏱️','rules'=>['term_max_days'=>20],'hint'=>'Срок до 20 дней'],
        '/на\s*месяц|на\s*30\s*дн/iu' => ['icon'=>'📅','rules'=>['term_max_days_min'=>30],'hint'=>'Срок от 30 дней'],
        '/на\s*3\s*месяц|на\s*90\s*дн/iu' => ['icon'=>'📆','rules'=>['term_max_days_min'=>90],'hint'=>'Срок от 90 дней'],
        '/на\s*6\s*месяц|на\s*180\s*дн|на\s*полгода/iu' => ['icon'=>'📆','rules'=>['term_max_days_min'=>180],'hint'=>'Срок от 180 дней'],
        '/на\s*(год|12\s*месяц|365\s*дн)/iu' => ['icon'=>'🗓️','rules'=>['term_max_days_min'=>365],'hint'=>'Срок от 1 года'],
        '/на\s*2\s*года/iu' => ['icon'=>'🗓️','rules'=>['term_max_days_min'=>730],'hint'=>'Срок от 2 лет'],
        '/долгосроч/iu' => ['icon'=>'📆','rules'=>['term_max_days_min'=>180],'hint'=>'Срок от 180 дней'],
        '/на\s*длительн/iu' => ['icon'=>'📆','rules'=>['term_max_days_min'=>180],'hint'=>'Срок от 180 дней'],

        // === Суммы ===
        '/до\s*1\s*тыс/iu' => ['icon'=>'💵','rules'=>['amount_min_max'=>1000],'hint'=>'Минимальная сумма до 1 000 ₽'],
        '/до\s*3\s*тыс/iu' => ['icon'=>'💵','rules'=>['amount_min_max'=>3000],'hint'=>'Минимальная сумма до 3 000 ₽'],
        '/до\s*5\s*тыс/iu' => ['icon'=>'💵','rules'=>['amount_max_max'=>5000],'hint'=>'Сумма до 5 000 ₽'],
        '/до\s*10\s*тыс/iu' => ['icon'=>'💵','rules'=>['amount_max_max'=>10000],'hint'=>'Сумма до 10 000 ₽'],
        '/до\s*15\s*тыс/iu' => ['icon'=>'💵','rules'=>['amount_max_max'=>15000],'hint'=>'Сумма до 15 000 ₽'],
        '/до\s*30\s*тыс/iu' => ['icon'=>'💰','rules'=>['amount_max_max'=>30000],'hint'=>'Сумма до 30 000 ₽'],
        '/до\s*50\s*тыс/iu' => ['icon'=>'💰','rules'=>['amount_max_max'=>50000],'hint'=>'Сумма до 50 000 ₽'],
        '/до\s*100\s*тыс/iu' => ['icon'=>'💰','rules'=>['amount_max_max'=>100000],'hint'=>'Сумма до 100 000 ₽'],
        '/мини\s*зай|микро\s*зай|небольш|маленьк/iu' => ['icon'=>'💵','rules'=>['amount_max_max'=>15000],'hint'=>'Небольшая сумма до 15 000 ₽'],
        '/крупн|больш|от\s*100\s*тыс/iu' => ['icon'=>'💎','rules'=>['amount_max_min'=>100000],'hint'=>'Сумма от 100 000 ₽'],
        '/от\s*50\s*тыс/iu' => ['icon'=>'💎','rules'=>['amount_max_min'=>50000],'hint'=>'Сумма от 50 000 ₽'],

        // === Условия ===
        '/без\s*процент|под\s*0\s*%|0\s*процент|первый\s*бесплатн|бесплатн/iu' => ['icon'=>'🎁','rules'=>['free_term_days_min'=>1],'hint'=>'Есть беспроцентный период'],
        '/низк.{0,5}(процент|ставк)|выгодн/iu' => ['icon'=>'📉','rules'=>['rate_max'=>0.5],'hint'=>'Ставка до 0.5% в день'],
        '/без\s*отказ|с\s*любой\s*историей|плох.{0,5}истори/iu' => ['icon'=>'✅','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],
        '/без\s*проверк|без\s*справок|без\s*документ/iu' => ['icon'=>'📝','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],
        '/срочн|быстр|мгновенн|моментальн/iu' => ['icon'=>'⚡','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],

        // === Способ получения (нет фильтра) ===
        '/на\s*карту\s*24|круглосуточн|24.?7/iu' => ['icon'=>'🌙','rules'=>[],'hint'=>'Нет фильтра по режиму — показать все'],
        '/на\s*карту/iu' => ['icon'=>'💳','rules'=>[],'hint'=>'Нет фильтра по способу — показать все'],
        '/на\s*(банковск|расчётн|счёт)/iu' => ['icon'=>'🏦','rules'=>[],'hint'=>'Нет фильтра по способу — показать все'],
        '/на\s*киви|на\s*qiwi/iu' => ['icon'=>'🟠','rules'=>[],'hint'=>'Нет фильтра по кошельку — показать все'],
        '/наличн/iu' => ['icon'=>'💵','rules'=>[],'hint'=>'Нет фильтра по способу — показать все'],
        '/онлайн|через\s*интернет/iu' => ['icon'=>'🌐','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],

        // === Банки (нет фильтра) ===
        '/сбербанк|сбер\b/iu' => ['icon'=>'🟢','rules'=>[],'hint'=>'Нет фильтра по банку — показать все'],
        '/тинькофф|тинькоф|т-банк/iu' => ['icon'=>'💛','rules'=>[],'hint'=>'Нет фильтра по банку — показать все'],
        '/альфа/iu' => ['icon'=>'🔴','rules'=>[],'hint'=>'Нет фильтра по банку — показать все'],
        '/втб/iu' => ['icon'=>'🔵','rules'=>[],'hint'=>'Нет фильтра по банку — показать все'],

        // === Возраст (нет фильтра) ===
        '/с\s*18\s*лет|от\s*18\s*лет/iu' => ['icon'=>'🎂','rules'=>[],'hint'=>'Нет поля возраста — показать все (все МФО работают с 18 лет)'],
        '/с\s*21\s*года|от\s*21\s*года/iu' => ['icon'=>'🎂','rules'=>[],'hint'=>'Нет поля возраста — показать все'],
        '/с\s*16\s*лет|от\s*16\s*лет/iu' => ['icon'=>'🎂','rules'=>[],'hint'=>'Нет поля возраста — показать все'],

        // === Категории заёмщиков ===
        '/пенсионер/iu' => ['icon'=>'👴','rules'=>['borrower_category'=>'pensioner'],'hint'=>'Категория: пенсионер'],
        '/студент/iu' => ['icon'=>'🎓','rules'=>['borrower_category'=>'student'],'hint'=>'Категория: студент'],
        '/безработн|без\s*работ|домохозяйк/iu' => ['icon'=>'🏠','rules'=>['borrower_category'=>'unemployed'],'hint'=>'Категория: безработный'],
        '/самозанят|ип\b|предприниматель|бизнес/iu' => ['icon'=>'💼','rules'=>['borrower_category'=>'self_employed'],'hint'=>'Категория: самозанятый / ИП'],
        '/работающ|с\s*работой/iu' => ['icon'=>'👔','rules'=>['borrower_category'=>'employed'],'hint'=>'Категория: работающий'],
        '/декрет|в\s*декрет/iu' => ['icon'=>'👶','rules'=>[],'hint'=>'Нет спец. фильтра — показать все'],
        '/военнослужащ|военн/iu' => ['icon'=>'🎖️','rules'=>[],'hint'=>'Нет спец. фильтра — показать все'],

        // === Кредитные карты ===
        '/кэшбэк|кэшбек|cashback/iu' => ['icon'=>'💸','rules'=>[],'hint'=>'Нет фильтра по кэшбэку — показать все'],
        '/грейс|льготн.{0,5}период|беспроцентн.{0,5}период/iu' => ['icon'=>'⏳','rules'=>['free_term_days_min'=>30],'hint'=>'Льготный период от 30 дней'],
        '/рассрочк/iu' => ['icon'=>'🛒','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],
        '/путешеств|миль|travel|авиа/iu' => ['icon'=>'✈️','rules'=>[],'hint'=>'Нет фильтра — показать все (контентная страница)'],
        '/100\s*дн|100\s*день/iu' => ['icon'=>'💯','rules'=>['free_term_days_min'=>100],'hint'=>'Льготный период от 100 дней'],

        // === Дебетовые карты ===
        '/процент.{0,5}остат/iu' => ['icon'=>'📈','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/бесплатн.{0,5}обслуж/iu' => ['icon'=>'🆓','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/виртуальн/iu' => ['icon'=>'📱','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],

        // === Другое ===
        '/рефинансир|перекредит/iu' => ['icon'=>'🔄','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/залог|под\s*залог/iu' => ['icon'=>'🏠','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/без\s*залог/iu' => ['icon'=>'🔓','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/мат.{0,5}капитал/iu' => ['icon'=>'👶','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/ипотек/iu' => ['icon'=>'🏡','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/автокредит|на\s*авто/iu' => ['icon'=>'🚗','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
        '/госуслуг/iu' => ['icon'=>'🇷🇺','rules'=>[],'hint'=>'Нет фильтра — контентная страница'],
    ];

    foreach ($queries as $query) {
        $matched = false;
        foreach ($patterns as $pattern => $data) {
            if (preg_match($pattern, $query)) {
                $results[] = [
                    'query' => $query,
                    'icon' => $data['icon'],
                    'rules' => $data['rules'],
                    'hint' => $data['hint'],
                ];
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $results[] = [
                'query' => $query,
                'icon' => '📋',
                'rules' => [],
                'hint' => 'Не удалось определить фильтр — будут показаны все офферы категории',
            ];
        }
    }

    return $results;
}

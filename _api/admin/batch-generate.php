<?php
require_once __DIR__ . "/../../includes/ai-compat.php";
/**
 * Пакетная автогенерация текстов/метаданных
 * POST /api/admin/batch-generate
 * 
 * Параметры:
 * - entity: offers|articles|categories|tags
 * - ids: массив ID для генерации
 * - fields: массив полей для генерации (meta_title, meta_description, description, seo_text)
 * - overwrite: перезаписывать существующие (bool)
 */
header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true);
$entity = $data['entity'] ?? '';
$ids = $data['ids'] ?? [];
$fields = $data['fields'] ?? ['meta_title', 'meta_description'];
$overwrite = !empty($data['overwrite']);

if (!$entity || !is_array($ids) || empty($ids)) {
    echo json_encode(['error' => 'Укажите entity и ids']);
    exit;
}

$db = getDB();
$siteName = SITE_NAME;
$results = ['success' => 0, 'skipped' => 0, 'errors' => 0, 'details' => []];

// Маппинг таблиц и полей
$entityConfig = [
    'offers' => [
        'table' => 'offers',
        'name_field' => 'title',
        'fields' => ['description', 'seo_keywords'],
    ],
    'articles' => [
        'table' => 'articles',
        'name_field' => 'title',
        'fields' => ['meta_title', 'meta_description'],
    ],
    'categories' => [
        'table' => 'categories',
        'name_field' => 'name',
        'fields' => ['meta_title', 'meta_description', 'h1', 'description', 'seo_text'],
    ],
    'tags' => [
        'table' => 'offer_tags',
        'name_field' => 'title',
        'fields' => ['meta_title', 'meta_description', 'description'],
    ],
];

if (!isset($entityConfig[$entity])) {
    echo json_encode(['error' => 'Неизвестный тип сущности: ' . $entity]);
    exit;
}

$config = $entityConfig[$entity];
$table = $config['table'];
$nameField = $config['name_field'];

// Функции-хелперы
function mb_limit(string $text, int $limit): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $limit) return $text;
    $short = mb_substr($text, 0, $limit - 1);
    $space = mb_strrpos($short, ' ');
    if ($space !== false && $space > ($limit * 0.6)) $short = mb_substr($short, 0, $space);
    return rtrim($short, " ,.-") . '…';
}

function callYandexGPT(string $prompt, string $systemPrompt = ''): ?string {
    if (!YANDEX_GPT_API_KEY || !YANDEX_FOLDER_ID) return null;
    
    $messages = [];
    if ($systemPrompt) {
        $messages[] = ['role' => 'system', 'text' => $systemPrompt];
    }
    $messages[] = ['role' => 'user', 'text' => $prompt];
    
    $response = kosmozaimAIComplete('Ты помощник', $prompt);
if (!$response) return null;
    
    $text = $response;
    
    return $text ? trim($text) : null;
}

function generateMeta(array $item, string $entity, string $siteName): array {
    $name = $item['name'] ?? $item['title'] ?? '';
    $h1 = $item['h1'] ?? $name;
    $desc = $item['description'] ?? '';
    $category = $item['category'] ?? '';
    
    // Промпт для GPT
    $prompt = "Сгенерируй SEO meta title (до 70 символов) и meta description (до 160 символов) для ";
    
    switch ($entity) {
        case 'offers':
            $prompt .= "финансового продукта (МФО/банк): \"{$name}\".";
            if ($item['amount_min'] || $item['amount_max']) {
                $prompt .= " Сумма от " . number_format($item['amount_min'] ?? 0, 0, '', ' ') . " до " . number_format($item['amount_max'] ?? 0, 0, '', ' ') . " ₽.";
            }
            break;
        case 'articles':
            $prompt .= "статьи: \"{$name}\".";
            if ($item['excerpt']) $prompt .= " Анонс: " . mb_substr($item['excerpt'], 0, 200);
            break;
        case 'categories':
            $prompt .= "категории финансовых продуктов: \"{$name}\".";
            break;
        case 'tags':
            $prompt .= "страницы подборки/тега: \"{$name}\".";
            break;
    }
    
    $prompt .= "\nСайт: {$siteName}. Верни ТОЛЬКО JSON: {\"metaTitle\":\"...\",\"metaDescription\":\"...\"}";
    
    $gptResponse = callYandexGPT($prompt, 'Ты SEO-специалист. Генерируешь краткие продающие meta-теги. Отвечай только JSON без markdown.');
    
    if ($gptResponse) {
        // Парсим JSON из ответа
        $gptResponse = preg_replace('/^```\s*json\s*/i', '', $gptResponse);
        $gptResponse = preg_replace('/```$/', '', $gptResponse);
        $gptResponse = trim($gptResponse);
        
        $parsed = json_decode($gptResponse, true);
        if (is_array($parsed) && !empty($parsed['metaTitle'])) {
            return [
                'meta_title' => mb_limit($parsed['metaTitle'], 70),
                'meta_description' => mb_limit($parsed['metaDescription'] ?? '', 160),
                'provider' => 'YandexGPT'
            ];
        }
    }
    
    // Fallback на шаблон
    return [
        'meta_title' => mb_limit($h1 . ' | ' . $siteName, 70),
        'meta_description' => mb_limit($desc ?: ("Узнайте больше о «{$name}» на сайте {$siteName}."), 160),
        'provider' => 'template'
    ];
}

function generateDescription(array $item, string $entity, string $siteName): ?string {
    $name = $item['name'] ?? $item['title'] ?? '';
    
    $prompt = "Напиши краткое описание (2-3 предложения, до 300 символов) для ";
    
    switch ($entity) {
        case 'offers':
            $prompt .= "финансового продукта \"{$name}\".";
            if ($item['rate']) $prompt .= " Ставка: {$item['rate']}%.";
            if ($item['amount_max']) $prompt .= " Максимальная сумма: " . number_format($item['amount_max'], 0, '', ' ') . " ₽.";
            break;
        case 'tags':
            $prompt .= "подборки предложений \"{$name}\".";
            break;
        default:
            return null;
    }
    
    $prompt .= " Сайт: {$siteName}. Пиши без markdown, только текст.";
    
    $result = callYandexGPT($prompt, 'Ты копирайтер финансового сайта. Пишешь кратко, информативно, без воды.');
    
    return $result ? mb_limit(strip_tags($result), 500) : null;
}

function generateSeoKeywords(array $item, string $entity, string $siteName): ?string {
    if ($entity !== 'offers') return null;

    $name = trim((string)($item['title'] ?? $item['name'] ?? ''));
    $category = (string)($item['category'] ?? 'microloans');
    $amountMax = (int)($item['amount_max'] ?? 0);
    $rate = trim((string)($item['rate'] ?? ''));
    $borrower = (string)($item['borrower_category'] ?? 'any');

    $categoryMap = [
        'microloans' => ['микрозайм', 'займ онлайн', 'займ на карту', 'срочный займ'],
        'credits' => ['кредит онлайн', 'потребительский кредит', 'кредит наличными', 'заявка на кредит'],
        'credit_cards' => ['кредитная карта', 'карта онлайн', 'кредитный лимит', 'льготный период'],
        'debit_cards' => ['дебетовая карта', 'карта с кэшбэком', 'банковская карта', 'оформить карту'],
    ];
    $borrowerMap = [
        'students' => ['для студентов', 'студентам'],
        'pensioners' => ['для пенсионеров', 'пенсионерам'],
        'unemployed' => ['безработным', 'без официальной работы'],
        'bad_history' => ['с плохой кредитной историей', 'без отказа'],
        'any' => ['онлайн', 'без визита в офис'],
    ];

    if (YANDEX_GPT_API_KEY && YANDEX_FOLDER_ID) {
        $prompt = "Сгенерируй 8-14 SEO ключевых фраз на русском языке для карточки финансового предложения. "
            . "Верни только список через запятую, без нумерации, без точек, без markdown. "
            . "Используй коммерческие и информационные запросы. "
            . "Данные: " . json_encode([
                'title' => $name,
                'category' => $category,
                'amount_max' => $amountMax,
                'rate' => $rate,
                'borrower_category' => $borrower,
                'site' => $siteName,
            ], JSON_UNESCAPED_UNICODE);

        $result = callYandexGPT($prompt, 'Ты SEO-специалист. Генерируешь естественные ключевые фразы через запятую. Без пояснений.');
        if ($result) {
            $result = preg_replace('/^```.*?
/s', '', trim($result));
            $result = preg_replace('/```$/', '', trim($result));
            $result = trim(strip_tags($result));
            if ($result !== '') return mb_limit($result, 500);
        }
    }

    $keywords = array_merge(
        [$name, $name . ' отзывы', $name . ' условия'],
        $categoryMap[$category] ?? ['финансовое предложение', 'онлайн заявка'],
        $borrowerMap[$borrower] ?? []
    );

    if ($amountMax > 0) {
        $keywords[] = $name . ' до ' . number_format($amountMax, 0, '', ' ') . ' рублей';
        $keywords[] = ($category === 'microloans' ? 'займ до ' : 'кредит до ') . number_format($amountMax, 0, '', ' ') . ' рублей';
    }
    if ($rate !== '' && $rate !== '0') {
        $keywords[] = $name . ' ставка ' . $rate . '%';
    }

    $keywords = array_values(array_unique(array_filter(array_map('trim', $keywords))));
    return mb_limit(implode(', ', array_slice($keywords, 0, 14)), 500);
}

function generateSeoText(array $item, string $entity, string $siteName): ?string {
    $name = $item['name'] ?? $item['title'] ?? '';
    
    if ($entity !== 'categories' && $entity !== 'tags') return null;
    
    $prompt = "Напиши SEO-текст (200-400 слов) для страницы категории \"{$name}\" финансового сайта {$siteName}. ";
    $prompt .= "Текст должен помочь пользователю сориентироваться в выборе. ";
    $prompt .= "Используй HTML-разметку: <p>, <h3>, <ul><li>. Не используй <h1>, <h2>.";
    
    $result = callYandexGPT($prompt, 'Ты SEO-копирайтер. Пишешь полезные тексты для финансовых сайтов.');
    
    return $result ?: null;
}

// Обрабатываем каждую сущность
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("SELECT * FROM {$table} WHERE id IN ({$placeholders})");
$stmt->execute($ids);
$items = $stmt->fetchAll();

foreach ($items as $item) {
    $id = $item['id'];
    $name = $item[$nameField] ?? "ID {$id}";
    $updates = [];
    $generated = [];
    
    // Генерируем meta_title и meta_description
    if (in_array('meta_title', $fields) || in_array('meta_description', $fields)) {
        $needMeta = false;
        
        if (in_array('meta_title', $fields)) {
            if ($overwrite || empty($item['meta_title'])) $needMeta = true;
        }
        if (in_array('meta_description', $fields)) {
            if ($overwrite || empty($item['meta_description'])) $needMeta = true;
        }
        
        if ($needMeta) {
            $meta = generateMeta($item, $entity, $siteName);
            
            if (in_array('meta_title', $fields) && ($overwrite || empty($item['meta_title']))) {
                $updates['meta_title'] = $meta['meta_title'];
                $generated[] = 'meta_title';
            }
            if (in_array('meta_description', $fields) && ($overwrite || empty($item['meta_description']))) {
                $updates['meta_description'] = $meta['meta_description'];
                $generated[] = 'meta_description';
            }
        }
    }
    
    // Генерируем description
    if (in_array('description', $fields) && ($overwrite || empty($item['description']))) {
        $desc = generateDescription($item, $entity, $siteName);
        if ($desc) {
            $updates['description'] = $desc;
            $generated[] = 'description';
        }
    }
    
    // Генерируем seo_keywords (для офферов)
    if (in_array('seo_keywords', $fields) && ($overwrite || empty($item['seo_keywords']))) {
        $seoKeywords = generateSeoKeywords($item, $entity, $siteName);
        if ($seoKeywords) {
            $updates['seo_keywords'] = $seoKeywords;
            $generated[] = 'seo_keywords';
        }
    }

    // Генерируем seo_text (только для категорий/тегов)
    if (in_array('seo_text', $fields) && ($overwrite || empty($item['seo_text']))) {
        $seo = generateSeoText($item, $entity, $siteName);
        if ($seo) {
            $updates['seo_text'] = $seo;
            $generated[] = 'seo_text';
        }
    }
    
    // Сохраняем
    if (!empty($updates)) {
        $setParts = [];
        $values = [];
        foreach ($updates as $field => $value) {
            $setParts[] = "`{$field}` = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        try {
            $db->prepare("UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE id = ?")->execute($values);
            $results['success']++;
            $results['details'][] = [
                'id' => $id,
                'name' => $name,
                'status' => 'ok',
                'fields' => $generated,
            ];
        } catch (Exception $e) {
            $results['errors']++;
            $results['details'][] = [
                'id' => $id,
                'name' => $name,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    } else {
        $results['skipped']++;
        $results['details'][] = [
            'id' => $id,
            'name' => $name,
            'status' => 'skipped',
            'reason' => 'Все поля уже заполнены',
        ];
    }
    
    // Небольшая пауза между запросами к API
    if (YANDEX_GPT_API_KEY) {
        usleep(300000); // 300ms
    }
}

echo json_encode($results, JSON_UNESCAPED_UNICODE);

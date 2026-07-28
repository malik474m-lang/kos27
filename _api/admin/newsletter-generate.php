<?php
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? 'topics'; // topics | body

if (!YANDEX_GPT_API_KEY || !YANDEX_FOLDER_ID) {
    http_response_code(400);
    echo json_encode(['error' => 'API-ключ YandexGPT не настроен. Добавьте в Настройки.']);
    exit;
}

$db = getDB();

// Получаем офферы для контекста
$offers = $db->query("SELECT title, rate, free_term_days, amount_max FROM offers WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 10")->fetchAll();
$offersList = implode(', ', array_map(function($o) { return $o['title']; }, $offers));

// Последние статьи
$articles = $db->query("SELECT title FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll();
$articlesList = implode(', ', array_map(function($a) { return $a['title']; }, $articles));

if ($action === 'topics') {
    $prompt = "Придумай 5 тем для email-рассылки финансового сервиса Космозайм.\n\n"
        . "Наши предложения: {$offersList}.\n"
        . "Последние статьи: {$articlesList}.\n\n"
        . "Темы должны быть цепляющими, короткими (до 60 символов), побуждающими открыть письмо.\n"
        . "Выведи только список тем, по одной на строку, без нумерации, без пояснений.";
    $sysPrompt = "Ты email-маркетолог. Придумываешь темы для рассылок. Коротко, цепляюще, на русском.";
} else {
    $subject = trim($data['subject'] ?? '');
    if (!$subject) {
        http_response_code(400);
        echo json_encode(['error' => 'Укажите тему письма']);
        exit;
    }

    $offersHtml = '';
    foreach ($offers as $o) {
        $free = $o['free_term_days'] > 0 ? " (0% на {$o['free_term_days']} дней)" : '';
        $offersHtml .= "- {$o['title']}: ставка от {$o['rate']}%, до " . number_format($o['amount_max'], 0, '', ' ') . " ₽{$free}\n";
    }

    $prompt = "Напиши HTML-письмо для email-рассылки на тему: \"{$subject}\"\n\n"
        . "Сайт: Космозайм (kosmozaim.ru) — сервис подбора займов, кредитов и банковских карт.\n\n"
        . "Наши предложения:\n{$offersHtml}\n"
        . "Последние статьи: {$articlesList}\n\n"
        . "Требования к письму:\n"
        . "- Используй HTML-теги: h2, h3, p, ul, li, a, strong\n"
        . "- Добавь ссылки на сайт: <a href=\"https://kosmozaim.ru/zajmy\">Смотреть предложения</a>\n"
        . "- Письмо должно быть 150-300 слов\n"
        . "- Стиль: дружелюбный, информативный\n"
        . "- Не добавляй ссылку отписки (она добавляется автоматически)\n"
        . "- Не оборачивай в блоки кода, без тройных кавычек\n"
        . "- Не используй markdown";
    $sysPrompt = "Ты email-маркетолог финансового сервиса. Пишешь HTML-письма для рассылки на русском языке. Используй HTML-теги для форматирования. Без markdown, без блоков кода, без тройных кавычек.";
}

$response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
        'content' => json_encode([
            'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
            'completionOptions' => ['stream' => false, 'temperature' => 0.6, 'maxTokens' => $action === 'topics' ? 1000 : 4000],
            'messages' => [
                ['role' => 'system', 'text' => $sysPrompt],
                ['role' => 'user', 'text' => $prompt],
            ],
        ]),
        'timeout' => 60,
    ],
]));

if (!$response) {
    echo json_encode(['error' => 'Нет ответа от YandexGPT']);
    exit;
}

$result = json_decode($response, true);
$text = $result['result']['alternatives'][0]['message']['text'] ?? '';

if (!$text) {
    echo json_encode(['error' => 'Пустой ответ от GPT']);
    exit;
}

if ($action === 'topics') {
    $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), function($l) {
        return $l && !preg_match('/^\d+[\.\)]/', $l) || true;
    }));
    // Убираем нумерацию
    $topics = array_map(function($l) {
        return preg_replace('/^\d+[\.\)]\s*/', '', $l);
    }, $lines);
    $topics = array_values(array_filter($topics, function($t) { return mb_strlen($t) > 5; }));
    echo json_encode(['success' => true, 'topics' => array_slice($topics, 0, 5)]);
} else {
    // Чистим от markdown
    $text = preg_replace('/^```\s*html?\s*\n?/i', '', $text);
    $text = preg_replace('/\n?```\s*$/', '', $text);
    $text = preg_replace('/```/', '', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^#\s+(.+)$/m', '<h2>$1</h2>', $text);
    echo json_encode(['success' => true, 'html' => trim($text)]);
}

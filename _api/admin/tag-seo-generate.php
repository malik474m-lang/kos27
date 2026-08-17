<?php
require_once __DIR__ . "/../../includes/ai-compat.php";
require_once __DIR__ . '/../../includes/content-quality.php';
header('Content-Type: application/json; charset=UTF-8');
requireAdmin();

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$title = trim((string)($data['title'] ?? ''));
$category = trim((string)($data['category'] ?? 'microloans'));
$siteName = SITE_NAME;

if ($title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Название тега обязательно']);
    exit;
}

$categoryLabels = [
    'microloans' => 'займы',
    'credits' => 'кредиты',
    'credit_cards' => 'кредитные карты',
    'debit_cards' => 'дебетовые карты',
];
$categoryLabel = $categoryLabels[$category] ?? 'финансовые предложения';

function tag_mb_limit(string $text, int $limit): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $limit) return $text;
    $short = mb_substr($text, 0, $limit - 1);
    $space = mb_strrpos($short, ' ');
    if ($space !== false && $space > ($limit * 0.6)) $short = mb_substr($short, 0, $space);
    return rtrim($short, " ,.-") . '…';
}

function tag_clean_json_block(string $text): string {
    $text = trim($text);
    $text = preg_replace('/^```\s*json\s*/i', '', $text);
    $text = preg_replace('/^```\s*/i', '', $text);
    $text = preg_replace('/```$/', '', $text);
    return trim($text);
}

function tag_fallback_seo(string $title, string $categoryLabel, string $siteName): array {
    $h1 = $title;
    $description = 'Актуальная подборка по теме «' . $title . '». Сравните условия, требования и особенности оформления.';
    $metaTitle = tag_mb_limit($title . ' | ' . $siteName, 70);
    $metaDescription = tag_mb_limit('Сравните лучшие ' . $categoryLabel . ' по запросу «' . $title . '». Актуальные условия, оформление онлайн, особенности предложений.', 160);
    $content = '<h3>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>'
        . "\n<p>На этой странице собраны {$categoryLabel} по запросу «{$title}». Мы собрали предложения, которые помогают быстро сравнить условия, требования к заёмщику, сроки оформления и ключевые особенности продуктов.</p>"
        . "\n<h3>Что важно сравнить</h3>"
        . "\n<ul><li>Ставку и полную стоимость</li><li>Требования к клиенту</li><li>Сроки и лимиты</li><li>Дополнительные бонусы и акции</li></ul>"
        . "\n<h3>Как выбрать подходящий вариант</h3>"
        . "\n<p>Сравните несколько предложений, изучите условия одобрения и обратите внимание на реальные параметры продукта. Это поможет подобрать оптимальный вариант именно под ваш запрос.</p>";
    $queries = [
        $title,
        mb_strtolower($title),
        $title . ' онлайн',
        $title . ' лучшие предложения',
        $title . ' сравнить',
    ];
    $queries = array_values(array_unique(array_filter(array_map('trim', $queries))));
    return [
        'h1' => $h1,
        'description' => $description,
        'metaTitle' => $metaTitle,
        'metaDescription' => $metaDescription,
        'content' => $content,
        'searchQueries' => implode("\n", $queries),
        'provider' => 'template',
    ];
}

$fallback = tag_fallback_seo($title, $categoryLabel, $siteName);

if (YANDEX_GPT_API_KEY && YANDEX_FOLDER_ID) {
    $prompt = "Сгенерируй SEO-комплект для страницы тега финансового сайта. "
        . "Тема тега: {$title}. Категория: {$categoryLabel}. "
        . "Верни строго JSON без markdown и без пояснений. "
        . "Формат: {\"h1\":\"...\",\"description\":\"...\",\"metaTitle\":\"...\",\"metaDescription\":\"...\",\"content\":\"...\",\"searchQueries\":[\"...\",\"...\"]}. "
        . "Требования: h1 до 120 символов, краткое описание до 220 символов, metaTitle до 70 символов, metaDescription до 160 символов. "
        . "content — полезный SEO-текст 2-4 абзаца в HTML, с 2-3 подзаголовками и одним списком. Без markdown. searchQueries — 5-8 естественных поисковых запросов.";

    $response = kosmozaimAIComplete('Ты SEO-редактор финансового сайта. Возвращаешь только валидный JSON без markdown и пояснений.', $prompt);
if ($response) {
    $text = trim((string)$response);
        $text = tag_clean_json_block($text);
        $parsed = json_decode($text, true);
        if (is_array($parsed)) {
            $queries = $parsed['searchQueries'] ?? [];
            if (is_string($queries)) {
                $queries = preg_split('/\r\n|\r|\n|,/', $queries);
            }
            if (!is_array($queries)) $queries = [];
            $queries = array_values(array_unique(array_filter(array_map(fn($q) => trim((string)$q), $queries))));
            echo json_encode([
                'success' => true,
                'h1' => tag_mb_limit((string)($parsed['h1'] ?? $fallback['h1']), 120),
                'description' => tag_mb_limit((string)($parsed['description'] ?? $fallback['description']), 220),
                'metaTitle' => tag_mb_limit((string)($parsed['metaTitle'] ?? $fallback['metaTitle']), 70),
                'metaDescription' => tag_mb_limit((string)($parsed['metaDescription'] ?? $fallback['metaDescription']), 160),
                'content' => cq_strip_markdown((string)($parsed['content'] ?? $fallback['content'])),
                'searchQueries' => implode("\n", $queries ?: explode("\n", $fallback['searchQueries'])),
                'provider' => 'YandexGPT',
            ]);
            exit;
        }
    }
}

echo json_encode(array_merge(['success' => true], $fallback));

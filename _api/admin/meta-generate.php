<?php
header('Content-Type: application/json; charset=UTF-8');
$data = json_decode(file_get_contents('php://input'), true);
$entity = $data['entity'] ?? 'generic';
$siteName = SITE_NAME;

function mb_limit(string $text, int $limit): string {
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if (mb_strlen($text) <= $limit) return $text;
    $short = mb_substr($text, 0, $limit - 1);
    $space = mb_strrpos($short, ' ');
    if ($space !== false && $space > ($limit * 0.6)) $short = mb_substr($short, 0, $space);
    return rtrim($short, " ,.-") . '…';
}

function strip_text(string $html): string {
    $html = preg_replace('/<[^>]+>/', ' ', $html);
    return trim(preg_replace('/\s+/', ' ', html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function fallbackMeta(array $data, string $entity, string $siteName): array {
    $title = trim($data['title'] ?? $data['name'] ?? $data['h1'] ?? '');
    $h1 = trim($data['h1'] ?? '');
    $desc = trim($data['description'] ?? '');
    $content = strip_text((string)($data['content'] ?? ''));
    $cityName = trim($data['cityName'] ?? '');
    $cityPrep = trim($data['cityPrep'] ?? '');
    $categoryName = trim($data['categoryName'] ?? '');

    switch ($entity) {
        case 'article':
            $metaTitle = mb_limit(($title ?: 'Статья') . ' | ' . $siteName, 70);
            $base = $desc ?: $content ?: $title;
            $metaDescription = mb_limit($base, 160);
            break;
        case 'category':
            $baseTitle = $h1 ?: $title ?: $categoryName ?: 'Категория';
            $metaTitle = mb_limit($baseTitle . ' | ' . $siteName, 70);
            $metaDescription = mb_limit(($desc ?: ('Сравните лучшие предложения в разделе «' . ($title ?: $categoryName ?: 'категория') . '» на сайте ' . $siteName . '.')), 160);
            break;
        case 'tag':
            $baseTitle = $h1 ?: $title ?: 'Подборка предложений';
            $metaTitle = mb_limit($baseTitle . ' | ' . $siteName, 70);
            $metaDescription = mb_limit(($desc ?: ('Сравните условия по подборке «' . ($title ?: 'предложения') . '». Актуальные офферы, ставки и особенности оформления.')), 160);
            break;
        case 'city':
            $baseTitle = $data['metaTitleBase'] ?? ($h1 ?: (($categoryName ?: 'Предложения') . ($cityPrep ? ' в ' . $cityPrep : '')));
            $metaTitle = mb_limit($baseTitle . ' | ' . $siteName, 70);
            $metaDescription = mb_limit(($desc ?: (($categoryName ?: 'Финансовые предложения') . ($cityPrep ? ' в ' . $cityPrep : '') . '. Сравните условия и оформите онлайн.')), 160);
            break;
        default:
            $metaTitle = mb_limit(($title ?: 'Страница') . ' | ' . $siteName, 70);
            $metaDescription = mb_limit(($desc ?: $content ?: $title ?: 'Описание страницы'), 160);
    }

    return ['metaTitle' => $metaTitle, 'metaDescription' => $metaDescription];
}

// GPT optional
require_once __DIR__ . '/../../includes/ai-compat.php';

$fallback = fallbackMeta($data, $entity, $siteName);
$prompt = "Сгенерируй SEO meta title и meta description на русском языке. "
    . "Верни ТОЛЬКО JSON без markdown и без тройных кавычек. Формат: {\"metaTitle\":\"...\",\"metaDescription\":\"...\"}. "
    . "Ограничения: meta title до 70 символов, meta description до 160 символов. "
    . "Данные страницы: " . json_encode([
        'entity' => $entity,
        'title' => $data['title'] ?? null,
        'h1' => $data['h1'] ?? null,
        'description' => $data['description'] ?? null,
        'categoryName' => $data['categoryName'] ?? null,
        'cityName' => $data['cityName'] ?? null,
        'cityPrep' => $data['cityPrep'] ?? null,
        'siteName' => $siteName,
    ], JSON_UNESCAPED_UNICODE);

$systemPrompt = 'Ты SEO-специалист. Генерируешь только JSON с metaTitle и metaDescription. Без markdown, без пояснений.';
$aiText = kosmozaimAIComplete($systemPrompt, $prompt);

if ($aiText) {
    $jsonText = trim($aiText);
    $jsonText = preg_replace('/^```\s*json\s*/i', '', $jsonText);
    $jsonText = preg_replace('/```$/', '', $jsonText);
    $jsonText = trim($jsonText);
    $parsed = json_decode($jsonText, true);
    if (is_array($parsed) && !empty($parsed['metaTitle']) && !empty($parsed['metaDescription'])) {
        echo json_encode([
            'success' => true,
            'metaTitle' => mb_limit($parsed['metaTitle'], 70),
            'metaDescription' => mb_limit($parsed['metaDescription'], 160),
            'provider' => 'AI',
        ]);
        exit;
    }
}

echo json_encode(array_merge(['success' => true, 'provider' => 'template'], $fallback));

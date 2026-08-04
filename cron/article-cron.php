<?php
/**
 * Автогенерация статей через YandexGPT
 * Запуск: php cron/article-cron.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/article-image.php';

$topics = [
    'Как получить займ без отказа','Первый займ без процентов','Как улучшить шансы на одобрение займа',
    'Что делать при просрочке микрозайма','Рефинансирование займов','Займы для пенсионеров',
    'Безопасность при онлайн-займе','Как выбрать надёжную МФО','Новые правила выдачи микрозаймов',
    'Займы на карту мгновенно','Чем отличается займ от кредита','Как снизить ставку по кредиту',
    'Досрочное погашение: плюсы и минусы','Кредитная история: как проверить','Как выбрать кредитную карту',
    'Дебетовые карты с кэшбеком','Кредитные каникулы: кому положены','Автокредит или потребительский кредит',
];

$topic = $topics[array_rand($topics)];
$content = null;
$provider = 'template';

echo "[START] Генерация статьи: $topic\n";

if (YANDEX_GPT_API_KEY && YANDEX_FOLDER_ID) {
    $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
            'content' => json_encode([
                'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
                'completionOptions' => ['stream' => false, 'temperature' => 0.4, 'maxTokens' => 8000],
                'messages' => [
                    ['role' => 'system', 'text' => 'Ты финансовый журналист для сайта Космозайм. Пиши развёрнутые статьи минимум 1500 слов на русском языке. Подзаголовки на отдельной строке. ВАЖНО: пиши ТОЛЬКО чистый текст без форматирования. Без markdown, без тройных кавычек, без блоков кода, без звёздочек, без решёток.'],
                    ['role' => 'user', 'text' => "Напиши развёрнутую статью на тему \"$topic\". Минимум 1500 слов."],
                ],
            ]),
            'timeout' => 120,
        ],
    ]));

    if ($response) {
        $data = json_decode($response, true);
        $text = $data['result']['alternatives'][0]['message']['text'] ?? null;
        if ($text) {
            // Убираем markdown мусор
            $content = preg_replace('/^```\s*\w*\s*\n?/i', '', $text);
            $content = preg_replace('/\n?```\s*$/', '', $content);
            $content = preg_replace('/```/', '', $content);
            $content = preg_replace('/\*\*(.+?)\*\*/', '$1', $content);
            $content = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '$1', $content);
            $content = preg_replace('/^#{1,6}\s+/m', '', $content);
            $content = preg_replace('/__(.+?)__/s', '$1', $content);
            $content = preg_replace('/~~(.+?)~~/s', '$1', $content);
            $content = preg_replace('/^>\s?/m', '', $content);
            $content = trim($content);
            $provider = 'YandexGPT';
        }
    }
}

if (!$content) {
    $content = "$topic\n\nПри выборе финансового продукта обращайте внимание на ставку и ПСК.\n\nРекомендации:\n- Сравнивайте предложения\n- Читайте договор\n- Не берите больше, чем можете вернуть\n\nНа сайте Космозайм сравните условия и выберите лучшее предложение.";
}

$paragraphs = array_filter(explode("\n\n", $content));
$excerpt = isset($paragraphs[1]) ? mb_substr($paragraphs[1], 0, 200) . '...' : mb_substr($content, 0, 200) . '...';
$slug = slugify($topic) . '-' . time();
$coverImage = generateArticleCoverImage($topic);

$db = getDB();
try { $db->query("SELECT content_status FROM articles LIMIT 1");
    $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, cover_image, is_published, content_status, quality_score) VALUES (?,?,?,?,?,?,?,?,?,?)")
       ->execute([$topic, $slug, $excerpt, $content, "$topic | " . SITE_NAME, mb_substr($excerpt, 0, 155), $coverImage, 0, 'draft', 0]);
} catch (Exception $e) {
    $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, cover_image, is_published) VALUES (?,?,?,?,?,?,?,0)")
       ->execute([$topic, $slug, $excerpt, $content, "$topic | " . SITE_NAME, mb_substr($excerpt, 0, 155), $coverImage]);
}

require_once __DIR__ . '/../includes/auto-indexing.php';
autoSubmitUrl('/articles/' . $slug);
echo "[DONE] Статья создана ($provider" . ($coverImage ? ", image" : ", no-image") . "): $topic\n";

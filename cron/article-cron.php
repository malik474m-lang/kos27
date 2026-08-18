<?php
/**
 * Автогенерация статей через YandexGPT
 * Запуск: php cron/article-cron.php
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/page-cache.php';
register_shutdown_function('pageCacheClear');
require_once __DIR__ . '/../includes/article-image.php';

$topics = [
    'Как получить займ без отказа','Первый займ без процентов','Как улучшить шансы на одобрение займа',
    'Что делать при просрочке микрозайма','Рефинансирование займов','Займы для пенсионеров',
    'Безопасность при онлайн-займе','Как выбрать надёжную МФО','Новые правила выдачи микрозаймов',
    'Займы на карту мгновенно','Чем отличается займ от кредита','Как снизить ставку по кредиту',
    'Досрочное погашение: плюсы и минусы','Кредитная история: как проверить','Как выбрать кредитную карту',
    'Дебетовые карты с кэшбеком','Кредитные каникулы: кому положены','Автокредит или потребительский кредит',
];

// Фильтруем темы — убираем уже существующие
$db = getDB();
$existingTitles = $db->query("SELECT LOWER(title) as t FROM articles")->fetchAll(PDO::FETCH_COLUMN);

$availableTopics = [];
foreach ($topics as $t) {
    $tLower = mb_strtolower($t);
    $isDupe = false;
    foreach ($existingTitles as $existing) {
        // Нормализуем: убираем знаки препинания и множественные пробелы
        $normExisting = preg_replace('/[^\p{L}\p{N}\s]/u', '', $existing);
        $normTopic = preg_replace('/[^\p{L}\p{N}\s]/u', '', $tLower);
        $normExisting = preg_replace('/\s+/', ' ', trim($normExisting));
        $normTopic = preg_replace('/\s+/', ' ', trim($normTopic));
        
        if ($normExisting === $normTopic 
            || str_contains((string)($normExisting), $normTopic) 
            || str_contains((string)($normTopic), $normExisting)
            || similar_text($normExisting, $normTopic) / max(mb_strlen($normExisting), mb_strlen($normTopic), 1) > 0.7) {
            $isDupe = true;
            break;
        }
    }
    if (!$isDupe) $availableTopics[] = $t;
}

if (empty($availableTopics)) {
    echo "[SKIP] Все темы уже использованы\n";
    exit;
}

$topic = $availableTopics[array_rand($availableTopics)];
$content = null;
$provider = 'template';

echo "[START] Генерация статьи: $topic\n";
require_once __DIR__ . '/../includes/ai-compat.php';

$systemPrompt = 'Ты финансовый журналист для сайта Космозайм. Пиши развёрнутые статьи минимум 1500 слов на русском языке. Формат: HTML (используй h2, h3, p, ul, li, strong). Без markdown, без тройных кавычек, без блоков кода, без звёздочек, без решёток. Только HTML-теги.';
$userPrompt = "Напиши развёрнутую статью на тему \"$topic\". Минимум 1500 слов.";
$aiText = kosmozaimAIComplete($systemPrompt, $userPrompt);

if ($aiText) {
    $content = preg_replace('/^```\s*html?\s*/i', '', $aiText);
    $content = preg_replace('/\s*```$/i', '', $content);
    $content = preg_replace('/^#{1,2}\s+(.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    if (!preg_match('/<(p|h[1-6]|ul|ol|li|div)\b/i', $content)) {
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
        $content = '<p>' . implode('</p><p>', $paragraphs) . '</p>';
    }
    $content = trim($content);
    $provider = 'AI';
}

if (!$content) {
    $content = "$topic\n\nПри выборе финансового продукта обращайте внимание на ставку и ПСК.\n\nРекомендации:\n- Сравнивайте предложения\n- Читайте договор\n- Не берите больше, чем можете вернуть\n\nНа сайте Космозайм сравните условия и выберите лучшее предложение.";
}

$paragraphs = array_filter(explode("\n\n", $content));
$excerpt = isset($paragraphs[1]) ? mb_substr($paragraphs[1], 0, 200) . '...' : mb_substr($content, 0, 200) . '...';
$slug = slugify($topic) . '-' . time();
$imageResult = generateArticleCoverImageResult($topic);
$coverImage = $imageResult['path'] ?? '';
$imageProvider = $imageResult['provider'] ?? '';

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
echo "[DONE] Статья создана ($provider" . ($coverImage ? ", image: " . articleImageProviderLabel($imageProvider ?: 'yandex') : ", no-image") . "): $topic\n";

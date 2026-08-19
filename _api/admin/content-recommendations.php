<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
/**
 * Рекомендации по контенту на основе поисковых запросов
 * Анализирует запросы из Яндекс.Вебмастер и Google Search Console
 * и предлагает создать допзапросы, статьи или FAQ
 */
requireAdmin();

require_once __DIR__ . '/../../includes/yandex-webmaster.php';
require_once __DIR__ . '/../../includes/google-indexing.php';
require_once __DIR__ . '/../../includes/subcategories.php';
require_once __DIR__ . '/../../includes/ai-providers.php';

$db = getDB();
$action = $_GET['action'] ?? 'analyze';
$currentYear = date('Y');

switch ($action) {

case 'analyze':
    $days = max(7, min(90, (int)($_GET['days'] ?? 30)));
    $minShows = max(1, (int)($_GET['min_shows'] ?? 10));
    $limit = max(50, min(500, (int)($_GET['limit'] ?? 200)));
    
    // Собираем запросы из обоих источников
    $allQueries = collectSearchQueries($days, $limit);
    
    if (empty($allQueries)) {
        echo json_encode(['error' => 'Нет данных из Яндекс.Вебмастер или Google Search Console. Проверьте настройки.']);
        exit;
    }
    
    // Фильтруем по минимальному количеству показов
    $allQueries = array_filter($allQueries, fn($q) => $q['shows'] >= $minShows);
    
    // Получаем существующий контент для сравнения
    $existingContent = getExistingContent();
    
    // Анализируем запросы и формируем рекомендации
    $recommendations = analyzeQueries($allQueries, $existingContent);
    
    // Сортируем по приоритету (opportunity score)
    usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
    
    echo json_encode([
        'success' => true,
        'days' => $days,
        'total_queries' => count($allQueries),
        'recommendations_count' => count($recommendations),
        'recommendations' => array_slice($recommendations, 0, 50), // Топ-50
    ]);
    break;

case 'analyze-smart':
    // Умный анализ: очищает запросы от брендов и группирует похожие
    $days = max(7, min(90, (int)($_GET['days'] ?? 30)));
    $minShows = max(1, (int)($_GET['min_shows'] ?? 5));
    $limit = max(50, min(500, (int)($_GET['limit'] ?? 300)));
    
    // Собираем запросы
    $allQueries = collectSearchQueries($days, $limit);
    
    if (empty($allQueries)) {
        echo json_encode(['error' => 'Нет данных из Яндекс.Вебмастер или Google Search Console.']);
        exit;
    }
    
    // Фильтруем по минимальному количеству показов
    $allQueries = array_filter($allQueries, fn($q) => $q['shows'] >= $minShows);
    
    // Разделяем: чистые запросы и брендовые
    $cleanQueries = [];
    $brandQueries = [];
    
    foreach ($allQueries as $q) {
        $result = cleanQueryFromBrands($q['query']);
        if ($result['brand']) {
            // Запрос содержит бренд
            $brandQueries[] = array_merge($q, ['cleaned' => $result['cleaned'], 'brand' => $result['brand'], 'has_meaning' => $result['has_meaning']]);
        } else {
            $cleanQueries[] = $q;
        }
    }
    
    // Группируем брендовые запросы по очищенной версии
    $groupedBrand = groupCleanedQueries($brandQueries);
    
    // Фильтруем группы: оставляем только те, где после очистки есть смысл
    $groupedBrand = array_filter($groupedBrand, fn($g) => mb_strlen($g['query']) >= 5);
    
    // Сортируем по суммарным показам
    usort($groupedBrand, fn($a, $b) => $b['shows'] <=> $a['shows']);
    
    // Получаем существующий контент
    $existingContent = getExistingContent();
    
    // Анализируем чистые запросы обычным способом
    $cleanRecommendations = analyzeQueries($cleanQueries, $existingContent);
    usort($cleanRecommendations, fn($a, $b) => $b['score'] <=> $a['score']);
    
    // Для групп тоже проверяем, нет ли уже контента
    $otherTitles = array_merge(
        $existingContent['subcategories'],
        $existingContent['tags']
    );
    
    $brandRecommendations = [];
    foreach ($groupedBrand as $group) {
        $group['content_type'] = detectContentType($group['query']);
        $group['category'] = detectCategory($group['query']);
        $group['action'] = getActionLabel($group['content_type']);
        $group['score'] = $group['shows'] * 2 + $group['clicks'] * 10;
        $group['is_from_brand'] = true;

        $existingArticle = ($group['content_type'] === 'article') ? findExistingArticleMatch($group['query'], $existingContent) : null;
        if ($existingArticle) {
            $group['already_exists'] = true;
            $group['existing_kind'] = 'article';
            $group['existing_title'] = $existingArticle['title'];
            $group['existing_slug'] = $existingArticle['slug'];
            $group['existing_id'] = $existingArticle['id'];
            $group['existing_published'] = !empty($existingArticle['is_published']);
            $brandRecommendations[] = $group;
            continue;
        }

        $hasOtherContent = false;
        foreach ($otherTitles as $title) {
            similar_text(mb_strtolower($group['query']), $title, $percent);
            if ($percent > 70 || mb_stripos($title, $group['query']) !== false) {
                $hasOtherContent = true;
                break;
            }
        }
        if ($hasOtherContent) {
            continue;
        }

        $group['already_exists'] = false;
        $brandRecommendations[] = $group;
    }
    
    echo json_encode([
        'success' => true,
        'days' => $days,
        'total_queries' => count($allQueries),
        'clean_queries' => count($cleanQueries),
        'brand_queries' => count($brandQueries),
        'recommendations' => array_slice($cleanRecommendations, 0, 30),
        'brand_recommendations' => array_slice($brandRecommendations, 0, 20),
    ]);
    break;


case 'generate-subcat':
    // Быстрое создание допзапроса на основе рекомендации
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $query = trim($data['query'] ?? '');
    $category = $data['category'] ?? 'microloans';
    
    if (!$query) {
        echo json_encode(['error' => 'Укажите запрос']);
        exit;
    }
    
    // Определяем правила фильтрации
    $rules = detectFilterRules($query);
    
    // Генерируем slug
    $slug = slugify($query);
    
    // Проверяем, не существует ли уже
    $exists = $db->prepare("SELECT id FROM subcategories WHERE slug = ? AND category = ?");
    $exists->execute([$slug, $category]);
    if ($exists->fetch()) {
        echo json_encode(['error' => 'Допзапрос с таким slug уже существует', 'slug' => $slug]);
        exit;
    }
    
    // Определяем иконку
    $icon = detectIcon($query);
    
    // Создаём допзапрос
    $stmt = $db->prepare("INSERT INTO subcategories (title, slug, category, icon, filter_rules, is_active, sort_order) VALUES (?, ?, ?, ?, ?, 1, 0)");
    $stmt->execute([
        mb_ucfirst($query),
        $slug,
        $category,
        $icon,
        json_encode($rules, JSON_UNESCAPED_UNICODE),
    ]);
    $newId = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'id' => (int)$newId,
        'slug' => $slug,
        'rules' => $rules,
        'message' => 'Допзапрос создан. Не забудьте сгенерировать SEO-текст!',
    ]);
    break;

case 'generate-article-idea':
    // Генерация идеи статьи по запросу через AI
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $query = trim($data['query'] ?? '');
    
    if (!$query) {
        echo json_encode(['error' => 'Укажите запрос']);
        exit;
    }
    
    $prompt = "Пользователь ищет: \"{$query}\"\n\nПредложи заголовок и краткий план статьи (3-5 пунктов) для финансового сайта о займах и кредитах. Если в заголовке или плане нужен год, используй только актуальный год: {$currentYear}. Ответь в JSON:\n{\"title\": \"...\", \"outline\": [\"пункт 1\", \"пункт 2\", ...], \"target_keywords\": [\"ключ1\", \"ключ2\"]}";
    
    $result = aiGenerateText($prompt, "Ты SEO-копирайтер финансового сайта. Возвращай только JSON.");
    
    if (empty($result['success'])) {
        // Fallback — шаблонная идея
        echo json_encode([
            'success' => true,
            'title' => mb_ucfirst($query) . ' — полное руководство ' . date('Y'),
            'outline' => [
                'Что такое ' . mb_strtolower($query),
                'Как выбрать лучшее предложение',
                'Пошаговая инструкция оформления',
                'Частые ошибки и как их избежать',
                'FAQ по теме',
            ],
            'target_keywords' => [$query, mb_strtolower($query) . ' онлайн'],
            'provider' => 'template',
        ]);
        exit;
    }
    
    $text = trim($result['text']);
    $text = preg_replace('/^```\s*json?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/i', '', $text);
    $parsed = json_decode($text, true);
    
    if (!$parsed) {
        $parsed = [
            'title' => mb_ucfirst($query),
            'outline' => ['Введение', 'Основная информация', 'Заключение'],
            'target_keywords' => [$query],
        ];
    }
    $parsed['provider'] = $result['provider'] ?? 'ai';
    $parsed['success'] = true;
    
    echo json_encode($parsed);
    break;

case 'write-article':
    // Генерация полной статьи по плану из рекомендации
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $title = trim($data['title'] ?? '');
    $outline = $data['outline'] ?? [];
    $keywords = $data['keywords'] ?? [];
    $themeCategory = trim($data['theme_category'] ?? 'займы');
    
    if (!$title) {
        echo json_encode(['error' => 'Укажите заголовок статьи']);
        exit;
    }

    $existingContent = getExistingContent();
    $existingArticle = findExistingArticleMatch($title, $existingContent);
    if ($existingArticle) {
        echo json_encode([
            'error' => 'Статья с похожим заголовком уже существует.',
            'existing_id' => $existingArticle['id'],
            'existing_slug' => $existingArticle['slug'],
            'existing_title' => $existingArticle['title'],
            'existing_published' => !empty($existingArticle['is_published']),
        ]);
        exit;
    }
    
    // Формируем промпт для AI
    $outlineText = '';
    if ($outline) {
        $outlineText = "\nПлан статьи:\n";
        foreach ($outline as $i => $point) {
            $outlineText .= ($i + 1) . ". " . $point . "\n";
        }
    }
    
    $keywordsText = $keywords ? "\nКлючевые слова для SEO: " . implode(', ', $keywords) . "\n" : '';
    
    $categoryContext = [
        'займы' => 'микрозаймов, МФО и быстрых денег на карту',
        'кредиты' => 'банковских кредитов, ипотеки и рефинансирования',
        'карты' => 'кредитных и дебетовых карт, кэшбека и льготных периодов',
        'банки' => 'банковских услуг и выбора банка',
        'мфо' => 'микрофинансовых организаций',
    ];
    $context = $categoryContext[$themeCategory] ?? 'финансовых продуктов';
    
    $prompt = <<<PROMPT
Напиши подробную статью для финансового сайта.

Заголовок: {$title}
Тематика: {$context}
{$outlineText}{$keywordsText}

Требования:
- Объём: 3000-5000 символов
- Если упоминаешь год, используй только актуальный год: {$currentYear}
- Формат: HTML (используй <h2>, <h3>, <p>, <ul>, <li>, <strong>)
- Стиль: экспертный, понятный, с практическими советами
- Добавь вводный абзац и заключение
- Упоминай, что на сайте Космозайм можно сравнить предложения
- Не используй markdown, только HTML-теги
- Пиши на русском языке
PROMPT;

    $sysPrompt = "Ты SEO-копирайтер финансового сайта Космозайм. Пишешь экспертные статьи на русском языке. Возвращаешь только HTML-текст без markdown. Если нужен год, используй только актуальный год из промпта пользователя.";
    
    $result = aiGenerateLongFormText($prompt, $sysPrompt);
    
    if (empty($result['success']) || empty($result['text'])) {
        echo json_encode(['error' => 'AI не смог сгенерировать статью. Попробуйте позже.', 'ai_error' => $result['error'] ?? '']);
        exit;
    }
    
    $articleContent = trim($result['text']);
    // Убираем markdown обёртку если есть
    $articleContent = preg_replace('/^```\s*html?\s*/i', '', $articleContent);
    $articleContent = preg_replace('/\s*```$/i', '', $articleContent);
    
    // Генерируем excerpt
    $plainText = strip_tags($articleContent);
    $excerpt = mb_substr($plainText, 0, 250) . '...';
    
    // Генерируем meta
    $metaTitle = mb_substr($title . ' | ' . SITE_NAME, 0, 70);
    $metaDesc = mb_substr($excerpt, 0, 160);
    
    // Создаём slug
    $slug = slugify($title) . '-' . time();
    
    // Сохраняем в БД
    $db = getDB();
    
    // Проверяем поля (миграции могут быть не применены)
    $hasStatusFields = dbTableHasColumn('articles', 'content_status');
    $hasEeatFields = dbTableHasColumn('articles', 'author_name');
    
    if ($hasStatusFields && $hasEeatFields) {
        $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, is_published, content_status, quality_score, author_name, author_title) VALUES (?,?,?,?,?,?,0,'draft',0,'Редакция Космозайм','Финансовый редактор')")
           ->execute([$title, $slug, $excerpt, $articleContent, $metaTitle, $metaDesc]);
    } elseif ($hasStatusFields) {
        $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, is_published, content_status, quality_score) VALUES (?,?,?,?,?,?,0,'draft',0)")
           ->execute([$title, $slug, $excerpt, $articleContent, $metaTitle, $metaDesc]);
    } else {
        $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, is_published) VALUES (?,?,?,?,?,?,0)")
           ->execute([$title, $slug, $excerpt, $articleContent, $metaTitle, $metaDesc]);
    }
    
    $articleId = (int)$db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'article_id' => $articleId,
        'slug' => $slug,
        'title' => $title,
        'content_length' => mb_strlen($articleContent),
        'provider' => $result['provider'] ?? 'ai',
    ]);
    break;

default:
    echo json_encode(['error' => 'Unknown action']);
}

// ============ HELPER FUNCTIONS ============

function collectSearchQueries(int $days, int $limit): array {
    $queries = [];
    
    // Яндекс
    $cfg = getYandexWebmasterConfig();
    if ($cfg && !empty($cfg['oauth_token'])) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        $url = 'https://api.webmaster.yandex.net/v4/user/' . rawurlencode((string)$cfg['user_id'])
             . '/hosts/' . rawurlencode((string)$cfg['host_id'])
             . '/search-queries/popular?order_by=TOTAL_SHOWS'
             . '&query_indicator=TOTAL_SHOWS&query_indicator=TOTAL_CLICKS&query_indicator=AVG_SHOW_POSITION'
             . '&date_from=' . $dateFrom . '&date_to=' . $dateTo . '&limit=' . $limit;
        $result = yandexWebmasterApiRequest('GET', $url);
        if (!empty($result['success'])) {
            foreach (($result['data']['queries'] ?? []) as $q) {
                $ind = $q['indicators'] ?? [];
                $text = mb_strtolower(trim($q['query_text']));
                if (!isset($queries[$text])) {
                    $queries[$text] = ['query' => $text, 'shows' => 0, 'clicks' => 0, 'position' => 0, 'sources' => []];
                }
                $queries[$text]['shows'] += (int)($ind['TOTAL_SHOWS'] ?? 0);
                $queries[$text]['clicks'] += (int)($ind['TOTAL_CLICKS'] ?? 0);
                $queries[$text]['position'] = round((float)($ind['AVG_SHOW_POSITION'] ?? 0), 1);
                $queries[$text]['sources'][] = 'yandex';
            }
        }
    }
    
    // Google
    $token = generateGoogleSearchConsoleToken();
    if ($token) {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        $body = ['startDate' => $dateFrom, 'endDate' => $dateTo, 'dimensions' => ['query'], 'rowLimit' => $limit];
        $ch = curl_init('https://www.googleapis.com/webmasters/v3/sites/' . urlencode(SITE_URL) . '/searchAnalytics/query');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            foreach ((json_decode($response, true)['rows'] ?? []) as $row) {
                $text = mb_strtolower(trim($row['keys'][0] ?? ''));
                if (!$text) continue;
                if (!isset($queries[$text])) {
                    $queries[$text] = ['query' => $text, 'shows' => 0, 'clicks' => 0, 'position' => 0, 'sources' => []];
                }
                $queries[$text]['shows'] += (int)($row['impressions'] ?? 0);
                $queries[$text]['clicks'] += (int)($row['clicks'] ?? 0);
                $queries[$text]['position'] = $queries[$text]['position'] ?: round((float)($row['position'] ?? 0), 1);
                $queries[$text]['sources'][] = 'google';
            }
        }
    }
    
    return array_values($queries);
}

function getExistingContent(): array {
    global $db;
    $content = [
        'subcategories' => [],
        'articles' => [],
        'article_items' => [],
        'tags' => [],
        'offers' => [],
    ];
    
    // Допзапросы
    try {
        $rows = $db->query("SELECT title, slug, category FROM subcategories WHERE is_active = 1")->fetchAll();
        foreach ($rows as $r) {
            $content['subcategories'][] = mb_strtolower($r['title']);
        }
    } catch (Exception $e) {}
    
    // Статьи — берём ВСЕ, включая черновики
    try {
        $rows = $db->query("SELECT id, title, slug, is_published FROM articles ORDER BY id DESC")->fetchAll();
        foreach ($rows as $r) {
            $content['articles'][] = mb_strtolower($r['title']);
            $content['article_items'][] = [
                'id' => (int)$r['id'],
                'title' => (string)$r['title'],
                'title_lc' => mb_strtolower((string)$r['title']),
                'slug' => (string)($r['slug'] ?? ''),
                'is_published' => !empty($r['is_published']),
            ];
        }
    } catch (Exception $e) {}
    
    // Теги
    try {
        $rows = $db->query("SELECT title FROM offer_tags WHERE is_active = 1")->fetchAll();
        foreach ($rows as $r) {
            $content['tags'][] = mb_strtolower($r['title']);
        }
    } catch (Exception $e) {}
    
    // Офферы
    try {
        $rows = $db->query("SELECT title FROM offers WHERE is_active = 1")->fetchAll();
        foreach ($rows as $r) {
            $content['offers'][] = mb_strtolower($r['title']);
        }
    } catch (Exception $e) {}
    
    return $content;
}

function normalizeRecText(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);
    return preg_replace('/\s+/u', ' ', trim($s));
}

function findExistingArticleMatch(string $query, array $existingContent): ?array {
    $queryNorm = normalizeRecText($query);
    if ($queryNorm === '') return null;
    foreach (($existingContent['article_items'] ?? []) as $article) {
        $titleNorm = $article['title_lc'] ?? normalizeRecText((string)($article['title'] ?? ''));
        if ($titleNorm === '' ) continue;
        if ($titleNorm === $queryNorm) return $article;
        if (mb_stripos($titleNorm, $queryNorm) !== false || mb_stripos($queryNorm, $titleNorm) !== false) return $article;
        similar_text($queryNorm, $titleNorm, $percent);
        if ($percent > 72) return $article;
    }
    return null;
}

function analyzeQueries(array $queries, array $existingContent): array {
    $recommendations = [];
    
    $otherTitles = array_merge(
        $existingContent['subcategories'],
        $existingContent['tags'],
        $existingContent['offers']
    );
    
    foreach ($queries as $q) {
        $query = $q['query'];
        $shows = $q['shows'];
        $clicks = $q['clicks'];
        $position = $q['position'];
        
        if (mb_strlen($query) < 5) continue;
        if (isCompetitorOrBrandQuery($query)) {
            $rephrased = rephraseCompetitorQuery($query);
            if ($rephrased) {
                $query = $rephrased['rephrased'];
                $q['original_query'] = $rephrased['original'];
                $q['competitor_removed'] = $rephrased['competitor'];
                $q['is_rephrased'] = true;
            } else {
                continue;
            }
        }

        $contentType = detectContentType($query);
        $category = detectCategory($query);
        $score = $shows * 2 + $clicks * 10;
        if ($position > 10 && $position < 50) $score *= 1.5;
        if ($position > 50) $score *= 0.7;

        $existingArticle = ($contentType === 'article') ? findExistingArticleMatch($query, $existingContent) : null;
        if ($existingArticle) {
            $recommendations[] = [
                'query' => $query,
                'shows' => $shows,
                'clicks' => $clicks,
                'position' => $position,
                'ctr' => $shows > 0 ? round($clicks / $shows * 100, 1) : 0,
                'sources' => array_unique($q['sources']),
                'score' => round($score),
                'content_type' => $contentType,
                'category' => $category,
                'action' => '📝 Уже есть статья',
                'already_exists' => true,
                'existing_kind' => 'article',
                'existing_title' => $existingArticle['title'],
                'existing_slug' => $existingArticle['slug'],
                'existing_id' => $existingArticle['id'],
                'existing_published' => !empty($existingArticle['is_published']),
                'is_rephrased' => $q['is_rephrased'] ?? false,
                'original_query' => $q['original_query'] ?? null,
                'competitor_removed' => $q['competitor_removed'] ?? null,
            ];
            continue;
        }

        $hasOtherContent = false;
        foreach ($otherTitles as $title) {
            if (mb_stripos($title, $query) !== false || mb_stripos($query, $title) !== false) {
                $hasOtherContent = true;
                break;
            }
            similar_text(mb_strtolower($query), $title, $percent);
            if ($percent > 70) {
                $hasOtherContent = true;
                break;
            }
        }
        if ($hasOtherContent) continue;
        
        $recommendations[] = [
            'query' => $query,
            'shows' => $shows,
            'clicks' => $clicks,
            'position' => $position,
            'ctr' => $shows > 0 ? round($clicks / $shows * 100, 1) : 0,
            'sources' => array_unique($q['sources']),
            'score' => round($score),
            'content_type' => $contentType,
            'category' => $category,
            'action' => getActionLabel($contentType),
            'already_exists' => false,
            'is_rephrased' => $q['is_rephrased'] ?? false,
            'original_query' => $q['original_query'] ?? null,
            'competitor_removed' => $q['competitor_removed'] ?? null,
        ];
    }
    
    return $recommendations;
}

function detectContentType(string $query): string {
    // Информационные запросы → статья
    if (preg_match('/как\s|что\s|какой|сколько|можно\s*ли|почему|зачем|где\s|когда|чем\s|кто\s/iu', $query)) {
        return 'article';
    }
    
    // Сравнения, рейтинги → статья
    if (preg_match('/лучш|сравн|рейтинг|топ\s*\d|обзор|отзыв/iu', $query)) {
        return 'article';
    }
    
    // Конкретные условия → допзапрос
    if (preg_match('/на\s*карту|под\s*0|без\s*отказ|с\s*18|на\s*\d+\s*(день|дней|месяц|год)|до\s*\d+\s*(тыс|руб)|круглосуточ|срочн|быстр|мгновенн|онлайн/iu', $query)) {
        return 'subcategory';
    }
    
    // Категории заёмщиков → допзапрос
    if (preg_match('/пенсионер|студент|безработн|самозанят|ип\b|предприниматель/iu', $query)) {
        return 'subcategory';
    }
    
    // По умолчанию — допзапрос (фильтрованная страница)
    return 'subcategory';
}

function detectCategory(string $query): string {
    if (preg_match('/кредитн.{0,3}карт/iu', $query)) return 'credit_cards';
    if (preg_match('/дебетов.{0,3}карт/iu', $query)) return 'debit_cards';
    if (preg_match('/кредит(?!н)/iu', $query)) return 'credits';
    if (preg_match('/ипотек/iu', $query)) return 'credits';
    if (preg_match('/займ|микрозайм|мфо|деньги\s*в\s*долг/iu', $query)) return 'microloans';
    return 'microloans'; // По умолчанию
}

function detectFilterRules(string $query): array {
    $rules = [];
    
    // Сроки
    if (preg_match('/на\s*(\d+)\s*дн/iu', $query, $m)) {
        $days = (int)$m[1];
        if ($days <= 30) $rules['term_max_days'] = $days;
        else $rules['term_max_days_min'] = $days;
    }
    if (preg_match('/на\s*(\d+)\s*месяц/iu', $query, $m)) {
        $rules['term_max_days_min'] = (int)$m[1] * 30;
    }
    if (preg_match('/краткосрочн/iu', $query)) $rules['term_max_days'] = 20;
    if (preg_match('/долгосрочн/iu', $query)) $rules['term_max_days_min'] = 180;
    
    // Суммы
    if (preg_match('/до\s*(\d+)\s*тыс/iu', $query, $m)) {
        $rules['amount_max_max'] = (int)$m[1] * 1000;
    }
    if (preg_match('/от\s*(\d+)\s*тыс/iu', $query, $m)) {
        $rules['amount_max_min'] = (int)$m[1] * 1000;
    }
    if (preg_match('/мини|микро|небольш/iu', $query)) {
        $rules['amount_max_max'] = 15000;
    }
    
    // Условия
    if (preg_match('/под\s*0|без\s*процент|бесплатн/iu', $query)) {
        $rules['free_term_days_min'] = 1;
    }
    
    // Категории заёмщиков
    if (preg_match('/пенсионер/iu', $query)) $rules['borrower_category'] = 'pensioner';
    if (preg_match('/студент/iu', $query)) $rules['borrower_category'] = 'student';
    if (preg_match('/безработн/iu', $query)) $rules['borrower_category'] = 'unemployed';
    if (preg_match('/самозанят|ип\b/iu', $query)) $rules['borrower_category'] = 'self_employed';
    
    return $rules;
}

function detectIcon(string $query): string {
    if (preg_match('/пенсионер/iu', $query)) return '👴';
    if (preg_match('/студент/iu', $query)) return '🎓';
    if (preg_match('/безработн/iu', $query)) return '🏠';
    if (preg_match('/18\s*лет/iu', $query)) return '🎂';
    if (preg_match('/карт/iu', $query)) return '💳';
    if (preg_match('/срочн|быстр|мгновенн/iu', $query)) return '⚡';
    if (preg_match('/0\s*%|бесплатн|под\s*0/iu', $query)) return '🎁';
    if (preg_match('/краткосрочн/iu', $query)) return '⏱️';
    if (preg_match('/долгосрочн|на\s*год/iu', $query)) return '📆';
    if (preg_match('/круглосуточ|24/iu', $query)) return '🌙';
    return '📋';
}

function getActionLabel(string $type): string {
    return match($type) {
        'article' => '📝 Написать статью',
        'subcategory' => '📑 Создать допзапрос',
        'faq' => '❓ Добавить в FAQ',
        default => '📋 Создать страницу',
    };
}

if (!function_exists("mb_ucfirst")) { function mb_ucfirst(string $str): string {
    return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1); } }

/**
 * Проверяет, является ли запрос брендовым или конкурентным
 * Такие запросы не имеет смысла использовать для создания контента
 */
function isCompetitorOrBrandQuery(string $query): bool {
    // Свой бренд
    if (preg_match('/космозайм|kosmozaim/iu', $query)) {
        return true;
    }
    
    // Конкуренты и агрегаторы
    $competitors = [
        // Агрегаторы финансовых продуктов
        'sravni', 'сравни', 'sravni.ru',
        'banki', 'банки.ру', 'banki.ru',
        'выберу', 'vyberu', 'vyberu.ru',
        'sravniki', 'сравники',
        'finuslugi', 'финуслуги',
        'brobank', 'бробанк',
        'kredity-tut', 'кредиты тут',
        'creditkarma', 'кредит карма',
        'bankiros', 'банкирос',
        'mainfin', 'мейнфин',
        'vsezaimyonline', 'все займы онлайн',
        'creditzzz', 'кредитззз',
        'zaim', 'zaymi', 'займы',
        
        // Крупные МФО (бренды)
        'займер', 'zaymer',
        'веббанкир', 'webbankir',
        'монеза', 'moneza',
        'микрозайм', 'vivus', 'вивус',
        'смсфинанс', 'smsfinance',
        'екапуста', 'ekapusta',
        'турбозайм', 'turbozaim',
        'займи|займи', // точное совпадение
        'кредито24', 'credito24',
        'platiza', 'платиза',
        'moneyman', 'манимен',
        'lime', 'лайм',
        'kviku', 'квику',
        
        // Банки (если не относятся к вашим офферам)
        // Раскомментируйте если нужно исключать
        // 'сбербанк', 'sberbank',
        // 'тинькофф', 'tinkoff',
        // 'альфа', 'alfa',
        // 'втб', 'vtb',
        
        // Общие стоп-слова
        'официальный сайт', 'офиц сайт', 'оф сайт',
        'личный кабинет', 'лк ',
        'вход в', 'войти',
        'регистрация на',
        'телефон горячей', 'горячая линия',
        'отзывы о ', 'отзывы про ',
        'жалоба на',
        'скачать приложение',
    ];
    
    $queryLower = mb_strtolower($query);
    
    foreach ($competitors as $competitor) {
        if (mb_strpos($queryLower, mb_strtolower($competitor)) !== false) {
            return true;
        }
    }
    
    // URL-паттерны (пользователь ищет конкретный сайт)
    if (preg_match('/\.(ru|com|net|org|рф)\b/iu', $query)) {
        return true;
    }
    
    return false;
}

/**
 * Очищает запрос от брендов конкурентов, сохраняя смысловую часть
 * "подобрать кредит sravni" → "подобрать кредит"
 * "займ онлайн banki.ru" → "займ онлайн"
 * 
 * @return array ['cleaned' => очищенный запрос, 'brand' => найденный бренд, 'has_meaning' => есть ли смысл в очищенном]
 */
function cleanQueryFromBrands(string $query): array {
    $brands = [
        // Агрегаторы
        'sravni\.ru', 'sravni', 'сравни\.ру', 'сравни ру', 'сравни',
        'banki\.ru', 'banki ru', 'banki', 'банки\.ру', 'банки ру', 'банки ru',
        'vyberu\.ru', 'vyberu', 'выберу\.ру', 'выберу ру', 'выберу',
        'brobank', 'бробанк',
        'bankiros', 'банкирос',
        'mainfin', 'мейнфин',
        'finuslugi', 'финуслуги',
        
        // МФО бренды
        'zaymer', 'займер',
        'webbankir', 'веббанкир',
        'moneza', 'монеза',
        'vivus', 'вивус',
        'turbozaim', 'турбозайм',
        'ekapusta', 'екапуста',
        'moneyman', 'манимен',
        'kviku', 'квику',
        
        // Банки
        'сбербанк', 'sberbank', 'сбер',
        'тинькофф', 'tinkoff', 'т-банк',
        'альфа-банк', 'альфабанк', 'alfabank',
        'втб', 'vtb',
        'газпромбанк',
        'райффайзен', 'raiffeisen',
        'россельхозбанк',
        'открытие',
        'совкомбанк',
        'почта банк',
        'хоум кредит', 'home credit',
        'ренессанс', 'renaissance',
        'мтс банк',
        'озон банк', 'ozon',
        
        // Прочее
        'официальный сайт', 'офиц\.? сайт', 'оф\.? сайт',
        'личный кабинет', 'лк\b',
        '\.ru\b', '\.com\b', '\.рф\b',
    ];
    
    $queryLower = mb_strtolower(trim($query));
    $foundBrand = null;
    $cleaned = $queryLower;
    
    foreach ($brands as $brand) {
        $pattern = '/\b' . $brand . '\b/iu';
        if (preg_match($pattern, $cleaned, $matches)) {
            $foundBrand = $matches[0];
            $cleaned = preg_replace($pattern, '', $cleaned);
        }
    }
    
    // Убираем лишние пробелы и предлоги в конце
    $cleaned = preg_replace('/\s+/', ' ', $cleaned);
    $cleaned = preg_replace('/\s+(на|в|с|от|для|по|через|и|или)\s*$/iu', '', $cleaned);
    $cleaned = trim($cleaned);
    
    // Проверяем, осталось ли что-то осмысленное (минимум 3 символа)
    $hasMeaning = mb_strlen($cleaned) >= 3 && !preg_match('/^(на|в|с|от|для|по)$/iu', $cleaned);
    
    return [
        'original' => $query,
        'cleaned' => $cleaned,
        'brand' => $foundBrand,
        'has_meaning' => $hasMeaning,
    ];
}

/**
 * Группирует похожие запросы после очистки от брендов
 * ["подобрать кредит sravni", "подобрать кредит banki.ru"] → "подобрать кредит" (2 запроса)
 */
function groupCleanedQueries(array $queries): array {
    $groups = [];
    
    foreach ($queries as $q) {
        $result = cleanQueryFromBrands($q['query']);
        
        if (!$result['has_meaning']) continue;
        
        $key = $result['cleaned'];
        
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'query' => $result['cleaned'],
                'original_queries' => [],
                'brands_found' => [],
                'shows' => 0,
                'clicks' => 0,
                'positions' => [],
                'sources' => [],
            ];
        }
        
        $groups[$key]['original_queries'][] = $q['query'];
        if ($result['brand']) {
            $groups[$key]['brands_found'][] = $result['brand'];
        }
        $groups[$key]['shows'] += $q['shows'];
        $groups[$key]['clicks'] += $q['clicks'];
        $groups[$key]['positions'][] = $q['position'];
        $groups[$key]['sources'] = array_unique(array_merge($groups[$key]['sources'], $q['sources'] ?? []));
    }
    
    // Вычисляем среднюю позицию и убираем дубли брендов
    foreach ($groups as $key => &$group) {
        $group['position'] = count($group['positions']) > 0 
            ? round(array_sum($group['positions']) / count($group['positions']), 1) 
            : 0;
        $group['brands_found'] = array_unique($group['brands_found']);
        $group['merged_count'] = count($group['original_queries']);
        unset($group['positions']);
    }
    
    return array_values($groups);
}

/**
 * Пытается перефразировать запрос с конкурентом в чистый поисковый интент
 * Возвращает null если запрос чисто навигационный (нет смысла перефразировать)
 */
function rephraseCompetitorQuery(string $query): ?array {
    $queryLower = mb_strtolower(trim($query));
    
    // Навигационные паттерны — нет смысла перефразировать
    $navigationalPatterns = [
        '/личный кабинет/iu',
        '/вход|войти|авторизац|логин/iu',
        '/регистрац|зарегистр/iu',
        '/официальный сайт|офиц\.?\s*сайт/iu',
        '/скачать|установить|приложение/iu',
        '/телефон|горячая линия|позвонить|контакт/iu',
        '/отзыв|жалоб|обман|мошенник|развод/iu',
        '/адрес|офис|отделение/iu',
        '/реквизит|инн|огрн/iu',
    ];
    
    foreach ($navigationalPatterns as $pattern) {
        if (preg_match($pattern, $queryLower)) {
            return null; // Чисто навигационный — пропускаем
        }
    }
    
    // Список конкурентов для удаления из запроса
    $competitorsToRemove = [
        // Агрегаторы
        'sravni.ru', 'sravni ru', 'sravni', 'сравни.ру', 'сравни ру', 'сравни',
        'banki.ru', 'banki ru', 'banki', 'банки.ру', 'банки ру',
        'vyberu.ru', 'vyberu ru', 'vyberu', 'выберу.ру', 'выберу ру', 'выберу',
        'brobank', 'бробанк',
        'finuslugi', 'финуслуги',
        'bankiros', 'банкирос',
        'mainfin', 'мейнфин',
        
        // МФО бренды
        'zaymer', 'займер',
        'webbankir', 'веббанкир',
        'moneza', 'монеза',
        'vivus', 'вивус',
        'moneyman', 'манимен',
        'turbozaim', 'турбозайм',
        'ekapusta', 'екапуста',
        'kviku', 'квику',
        'platiza', 'платиза',
        'lime', 'лайм займ',
        'credito24', 'кредито24',
        'smsfinance', 'смсфинанс',
    ];
    
    // Удаляем конкурента из запроса
    $cleanQuery = $queryLower;
    $foundCompetitor = null;
    
    foreach ($competitorsToRemove as $comp) {
        if (mb_strpos($cleanQuery, mb_strtolower($comp)) !== false) {
            $foundCompetitor = $comp;
            $cleanQuery = str_ireplace($comp, '', $cleanQuery);
            break;
        }
    }
    
    if (!$foundCompetitor) {
        return null; // Конкурент не найден
    }
    
    // Чистим результат
    $cleanQuery = preg_replace('/\s+/', ' ', $cleanQuery); // Множественные пробелы
    $cleanQuery = preg_replace('/^[\s\-\—\–]+|[\s\-\—\–]+$/u', '', $cleanQuery); // Trim с дефисами
    $cleanQuery = trim($cleanQuery);
    
    // Если осталось слишком мало — запрос был только о конкуренте
    if (mb_strlen($cleanQuery) < 5) {
        return null;
    }
    
    // Улучшаем перефразированный запрос
    $rephrased = improveRephrasedQuery($cleanQuery);
    
    return [
        'original' => $query,
        'competitor' => $foundCompetitor,
        'rephrased' => $rephrased,
        'clean' => $cleanQuery,
    ];
}

/**
 * Улучшает перефразированный запрос, добавляя полезные слова
 */
function improveRephrasedQuery(string $query): string {
    $query = mb_strtolower(trim($query));
    
    // Если начинается с "подобрать" — добавляем "сравнить и"
    if (preg_match('/^подобрать\s/iu', $query)) {
        $query = 'сравнить и ' . $query;
    }
    
    // Если есть "кредит" но нет "онлайн" — добавляем
    if (preg_match('/кредит/iu', $query) && !preg_match('/онлайн/iu', $query)) {
        $query .= ' онлайн';
    }
    
    // Если есть "займ/займы" но нет способа получения
    if (preg_match('/займ|займы/iu', $query) && !preg_match('/на карту|онлайн|быстр/iu', $query)) {
        $query .= ' на карту';
    }
    
    // Капитализируем первую букву
    $query = mb_strtoupper(mb_substr($query, 0, 1)) . mb_substr($query, 1);
    
    return $query;
}

/**
 * Очищает запрос от брендов конкурентов, извлекая реальный интент
 * "подобрать кредит sravni" → "подобрать кредит"
 * "займ на карту веббанкир" → "займ на карту"
 * 
 * @return array ['original' => ..., 'cleaned' => ..., 'brand_removed' => ...]
 */

function shouldRecommendCleanedQuery(string $cleanedQuery, array $existingContent): bool {
    $allTitles = array_merge(
        $existingContent['subcategories'] ?? [],
        $existingContent['articles'] ?? [],
        $existingContent['tags'] ?? []
    );
    
    foreach ($allTitles as $title) {
        if (mb_stripos($title, $cleanedQuery) !== false || mb_stripos($cleanedQuery, $title) !== false) {
            return false;
        }
        similar_text(mb_strtolower($cleanedQuery), $title, $percent);
        if ($percent > 75) {
            return false;
        }
    }
    
    return true;
}

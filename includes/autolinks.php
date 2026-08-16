<?php
function getOfferLinks(string $preferredCategory = ""): array {
    $cacheFile = __DIR__ . '/../data/offer-links-cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    $links = [];
    try {
        $db = getDB();
        $offers = $db->query("SELECT title, slug, category FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        foreach ($offers as $offer) {
            $url = '/offer/' . $offer['slug'];
            $priorityBase = ($preferredCategory !== '' && ($offer['category'] ?? '') === $preferredCategory) ? 28 : 20;
            $links[] = ['phrase' => $offer['title'], 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн', 'priority' => $priorityBase];
            $lower = mb_strtolower($offer['title']);
            if ($lower !== $offer['title']) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн', 'priority' => $priorityBase - 1];
            }
        }
        @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {}
    return $links;
}


function getCityLinks(): array {
    require_once __DIR__ . '/../data/cities.php';
    $cacheFile = __DIR__ . '/../data/city-links-cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }

    $links = [];
    foreach (getCities() as $city) {
        $prep = $city['prep'];
        $name = $city['name'];
        $slug = $city['slug'];

        // Займы
        foreach (['займы', 'займ', 'микрозаймы', 'микрозайм'] as $phrase) {
            $links[] = ['phrase' => $phrase . ' в ' . $prep, 'url' => '/zajmy/' . $slug, 'title' => 'Займы в ' . $prep, 'priority' => 35];
            $links[] = ['phrase' => $phrase . ' ' . $name, 'url' => '/zajmy/' . $slug, 'title' => 'Займы в ' . $prep, 'priority' => 34];
        }

        // Кредиты
        foreach (['кредиты', 'кредит', 'банковские кредиты'] as $phrase) {
            $links[] = ['phrase' => $phrase . ' в ' . $prep, 'url' => '/kredity/' . $slug, 'title' => 'Кредиты в ' . $prep, 'priority' => 35];
            $links[] = ['phrase' => $phrase . ' ' . $name, 'url' => '/kredity/' . $slug, 'title' => 'Кредиты в ' . $prep, 'priority' => 34];
        }

        // Карты
        foreach (['карты', 'банковские карты', 'кредитные карты', 'дебетовые карты'] as $phrase) {
            $links[] = ['phrase' => $phrase . ' в ' . $prep, 'url' => '/karty/' . $slug, 'title' => 'Карты в ' . $prep, 'priority' => 35];
            $links[] = ['phrase' => $phrase . ' ' . $name, 'url' => '/karty/' . $slug, 'title' => 'Карты в ' . $prep, 'priority' => 34];
        }
    }

    @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    return $links;
}

function getTagLinks(): array {
    $cacheFile = __DIR__ . '/../data/tag-links-cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    $links = [];
    $catUrls = ['microloans'=>'/zajmy','credits'=>'/kredity','credit_cards'=>'/karty/kreditnye','debit_cards'=>'/karty/debetovye'];
    try {
        $db = getDB();
        $tags = $db->query("SELECT title, slug, category, h1, search_queries FROM offer_tags WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        foreach ($tags as $tag) {
            $catUrl = $catUrls[$tag['category']] ?? '/zajmy';
            $url = $catUrl . '/type/' . $tag['slug'];
            $title = $tag['title'];
            $links[] = ['phrase' => $title, 'url' => $url, 'title' => $title, 'priority' => 50];
            foreach (['Займы ', 'Кредиты ', 'Карты ', 'Кредитные ', 'Дебетовые '] as $prefix) {
                if (mb_stripos($title, $prefix) === 0) {
                    $short = trim(mb_substr($title, mb_strlen($prefix)));
                    if (mb_strlen($short) >= 4) {
                        $links[] = ['phrase' => $short, 'url' => $url, 'title' => $title, 'priority' => 10];
                        $links[] = ['phrase' => mb_strtolower($short), 'url' => $url, 'title' => $title, 'priority' => 9];
                    }
                }
            }
            if (!empty($tag['h1']) && $tag['h1'] !== $title) {
                $links[] = ['phrase' => $tag['h1'], 'url' => $url, 'title' => $title, 'priority' => 40];
            }
            if (!empty($tag['search_queries'])) {
                $queries = preg_split('/\r\n|\r|\n/', (string)$tag['search_queries']);
                foreach ($queries as $q) {
                    $q = trim($q);
                    if (mb_strlen($q) < 3) continue;
                    $links[] = ['phrase' => $q, 'url' => $url, 'title' => $title, 'priority' => 100];
                    $ql = mb_strtolower($q);
                    if ($ql !== $q) {
                        $links[] = ['phrase' => $ql, 'url' => $url, 'title' => $title, 'priority' => 99];
                    }
                }
            }
            $lower = mb_strtolower($title);
            if ($lower !== $title) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $title, 'priority' => 30];
            }
        }
        @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {}
    return $links;
}


function getArticleTitleLinkPhrases(string $title): array {
    $title = trim(preg_replace('/\s+/u', ' ', $title));
    if ($title === '') return [];

    $variants = [$title];
    $clean = trim(str_replace(['«','»','"'], '', $title));
    if ($clean !== $title) $variants[] = $clean;

    foreach ([
        '/^как\s+/iu',
        '/^что\s+такое\s+/iu',
        '/^почему\s+/iu',
        '/^когда\s+/iu',
        '/^зачем\s+/iu',
        '/^можно\s+ли\s+/iu',
    ] as $pattern) {
        $short = trim((string)preg_replace($pattern, '', $clean));
        if (mb_strlen($short) >= 12) $variants[] = $short;
    }

    if (preg_match('/[:—-]\s*(.+)$/u', $clean, $m)) {
        $tail = trim($m[1]);
        if (mb_strlen($tail) >= 12) $variants[] = $tail;
    }

    $unique = [];
    foreach ($variants as $variant) {
        $variant = trim($variant);
        if (mb_strlen($variant) < 8 || mb_strlen($variant) > 120) continue;
        $unique[$variant] = true;
        $lower = mb_strtolower($variant);
        $unique[$lower] = true;
    }

    return array_keys($unique);
}

function getArticleLinks(string $currentSlug = ''): array {
    $cacheFile = __DIR__ . '/../data/article-links-cache.json';
    $articles = [];

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 900) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (is_array($cached)) $articles = $cached;
    }

    if (!$articles) {
        try {
            $db = getDB();
            $articles = $db->query("SELECT title, slug FROM articles WHERE is_published = 1 ORDER BY updated_at DESC, created_at DESC LIMIT 200")->fetchAll();
            @file_put_contents($cacheFile, json_encode($articles, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            $articles = [];
        }
    }

    $links = [];
    foreach ($articles as $article) {
        if (!empty($currentSlug) && ($article['slug'] ?? '') === $currentSlug) continue;
        $url = '/articles/' . $article['slug'];
        $title = trim((string)($article['title'] ?? ''));
        if ($title === '') continue;

        foreach (getArticleTitleLinkPhrases($title) as $phrase) {
            $links[] = [
                'phrase' => $phrase,
                'url' => $url,
                'title' => $title,
                'priority' => mb_strtolower($phrase) === mb_strtolower($title) ? 16 : 12,
            ];
        }
    }

    return $links;
}

function extractArticleKeywords(string $text, int $limit = 18): array {
    $text = trim(strip_tags($text));
    $text = mb_strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);

    $stop = [
        'это','для','при','над','под','после','перед','если','когда','чтобы','также','который','которая','которые','можно','нужно','через','между','такой','такая','такие','займ','займы','кредит','кредиты','карта','карты','банк','банки','онлайн','2024','2025','2026','как','что','или','где','про','его','её','них','она','они','оно','все','всё','ещё','этот','эта','эти','для','без','под','над','вам','нас','при','из','на','по','до','от','со','не','но','а','и','в','с','у','к','о','об','за','то','же'
    ];
    $stopMap = array_fill_keys($stop, true);

    $weights = [];
    foreach (explode(' ', $text) as $word) {
        $word = trim($word, '-');
        if (mb_strlen($word) < 4) continue;
        if (isset($stopMap[$word])) continue;
        if (preg_match('/^\d+$/', $word)) continue;
        $weights[$word] = ($weights[$word] ?? 0) + 1;
    }

    arsort($weights);
    return array_slice(array_keys($weights), 0, $limit);
}

function findRelatedArticles(array $currentArticle, int $limit = 3): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, title, slug, cover_image, excerpt, content, created_at, updated_at FROM articles WHERE is_published = 1 AND id != ? ORDER BY updated_at DESC, created_at DESC LIMIT 80");
        $stmt->execute([(int)$currentArticle['id']]);
        $candidates = $stmt->fetchAll();
        if (!$candidates) return [];

        $sourceText = trim((string)($currentArticle['title'] ?? '') . ' ' . ($currentArticle['excerpt'] ?? '') . ' ' . mb_substr((string)($currentArticle['content'] ?? ''), 0, 2500));
        $keywords = extractArticleKeywords($sourceText, 18);
        if (!$keywords) {
            return array_slice($candidates, 0, $limit);
        }
        $keywordSet = array_fill_keys($keywords, true);

        foreach ($candidates as &$candidate) {
            $score = 0;
            $candidateText = trim((string)($candidate['title'] ?? '') . ' ' . ($candidate['excerpt'] ?? '') . ' ' . mb_substr((string)($candidate['content'] ?? ''), 0, 1800));
            $candidateKeywords = extractArticleKeywords($candidateText, 18);

            foreach ($candidateKeywords as $kw) {
                if (isset($keywordSet[$kw])) $score += 4;
            }

            $titleLower = mb_strtolower((string)$candidate['title']);
            foreach ($keywords as $kw) {
                if (mb_strlen($kw) >= 5 && str_contains($titleLower, $kw)) $score += 3;
            }

            similar_text(
                mb_strtolower((string)$currentArticle['title']),
                mb_strtolower((string)$candidate['title']),
                $titleSimilarity
            );
            $score += (int)floor($titleSimilarity / 12);

            $candidate['_score'] = $score;
        }
        unset($candidate);

        usort($candidates, function($a, $b) {
            $scoreDiff = ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
            if ($scoreDiff !== 0) return $scoreDiff;
            return strtotime($b['updated_at'] ?? $b['created_at'] ?? 'now') <=> strtotime($a['updated_at'] ?? $a['created_at'] ?? 'now');
        });

        return array_slice($candidates, 0, $limit);
    } catch (Exception $e) {
        return [];
    }
}


function detectArticleOfferCategory(array $article): string {
    $text = mb_strtolower(trim(
        (string)($article['title'] ?? '') . ' ' .
        (string)($article['excerpt'] ?? '') . ' ' .
        mb_substr((string)($article['content'] ?? ''), 0, 3000)
    ));

    $scores = [
        'microloans' => 0,
        'credits' => 0,
        'credit_cards' => 0,
        'debit_cards' => 0,
    ];

    $rules = [
        'microloans' => [
            'займ' => 3, 'займы' => 3, 'микрозайм' => 4, 'микрозаймы' => 4, 'мфо' => 5,
            'до зарплаты' => 4, 'займ на карту' => 5, 'первый займ' => 4,
        ],
        'credits' => [
            'кредит ' => 3, 'кредиты' => 3, 'кредит наличными' => 5, 'потребительский кредит' => 5,
            'рефинансирование' => 5, 'ежемесячный платеж' => 3, 'ставка по кредиту' => 4,
        ],
        'credit_cards' => [
            'кредитная карта' => 6, 'кредитные карты' => 6, 'грейс' => 4, 'льготный период' => 4,
            'кредитный лимит' => 5, 'минимальный платеж' => 3,
        ],
        'debit_cards' => [
            'дебетовая карта' => 6, 'дебетовые карты' => 6, 'карта с кэшбеком' => 5,
            'процент на остаток' => 5, 'обслуживание карты' => 3, 'банковская карта' => 2,
        ],
    ];

    foreach ($rules as $category => $phrases) {
        foreach ($phrases as $phrase => $weight) {
            if (str_contains($text, $phrase)) $scores[$category] += $weight;
        }
    }

    arsort($scores);
    $best = array_key_first($scores);
    return ($scores[$best] ?? 0) > 0 ? $best : 'microloans';
}

function getArticleCategoryMeta(string $category): array {
    return match ($category) {
        'credits' => ['label' => 'Кредиты по теме статьи', 'url' => '/kredity'],
        'credit_cards' => ['label' => 'Кредитные карты по теме статьи', 'url' => '/karty/kreditnye'],
        'debit_cards' => ['label' => 'Дебетовые карты по теме статьи', 'url' => '/karty/debetovye'],
        default => ['label' => 'Займы по теме статьи', 'url' => '/zajmy'],
    };
}

function findRelatedOffersForArticle(array $article, int $limit = 3): array {
    try {
        $db = getDB();
        $category = detectArticleOfferCategory($article);
        $meta = getArticleCategoryMeta($category);

        $sourceText = mb_strtolower(trim(
            (string)($article['title'] ?? '') . ' ' .
            (string)($article['excerpt'] ?? '') . ' ' .
            mb_substr((string)($article['content'] ?? ''), 0, 2500)
        ));
        $keywords = extractArticleKeywords($sourceText, 16);

        $stmt = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY rating DESC, review_count DESC, sort_order ASC LIMIT 30");
        $stmt->execute([$category]);
        $offers = $stmt->fetchAll();

        if (!$offers) {
            return ['offers' => [], 'category' => $category, 'meta' => $meta];
        }

        foreach ($offers as &$offer) {
            $score = (float)($offer['rating'] ?? 0) * 4 + min(10, ((int)($offer['review_count'] ?? 0)) / 10);
            $haystack = mb_strtolower(trim(
                (string)($offer['title'] ?? '') . ' ' .
                (string)($offer['description'] ?? '') . ' ' .
                (string)($offer['seo_keywords'] ?? '')
            ));

            foreach ($keywords as $kw) {
                if (mb_strlen($kw) >= 4 && str_contains($haystack, $kw)) {
                    $score += 3;
                }
            }

            if ($category === 'microloans' && str_contains($sourceText, 'первый займ') && ((int)($offer['free_term_days'] ?? 0) > 0)) {
                $score += 5;
            }
            if ($category === 'credit_cards' && str_contains($sourceText, 'льготн') && ((int)($offer['free_term_days'] ?? 0) > 0)) {
                $score += 4;
            }
            if ($category === 'debit_cards' && str_contains($sourceText, 'кэшбек') && str_contains($haystack, 'кэшбек')) {
                $score += 4;
            }

            $offer['_article_score'] = $score;
        }
        unset($offer);

        usort($offers, fn($a, $b) => (($b['_article_score'] ?? 0) <=> ($a['_article_score'] ?? 0)));
        return ['offers' => array_slice($offers, 0, $limit), 'category' => $category, 'meta' => $meta];
    } catch (Exception $e) {
        return ['offers' => [], 'category' => 'microloans', 'meta' => getArticleCategoryMeta('microloans')];
    }
}

function buildAutoLinkMap(array $options = []): array {
    $staticLinks = [
        ['phrase' => 'микрозаймы онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн', 'priority' => 5],
        ['phrase' => 'микрозайм онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн', 'priority' => 5],
        ['phrase' => 'займы онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн', 'priority' => 5],
        ['phrase' => 'займ онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн', 'priority' => 5],
        ['phrase' => 'оформить займ', 'url' => '/zajmy', 'title' => 'Оформить займ', 'priority' => 5],
        ['phrase' => 'взять займ', 'url' => '/zajmy', 'title' => 'Взять займ', 'priority' => 5],
        ['phrase' => 'получить займ', 'url' => '/zajmy', 'title' => 'Получить займ', 'priority' => 5],
        ['phrase' => 'займы на карту', 'url' => '/zajmy', 'title' => 'Займы на карту', 'priority' => 5],
        ['phrase' => 'займ на карту', 'url' => '/zajmy', 'title' => 'Займы на карту', 'priority' => 5],
        ['phrase' => 'микрозаймы', 'url' => '/zajmy', 'title' => 'Микрозаймы', 'priority' => 4],
        ['phrase' => 'микрозайм', 'url' => '/zajmy', 'title' => 'Микрозаймы', 'priority' => 4],
        ['phrase' => 'потребительский кредит', 'url' => '/kredity', 'title' => 'Потребительские кредиты', 'priority' => 5],
        ['phrase' => 'банковский кредит', 'url' => '/kredity', 'title' => 'Банковские кредиты', 'priority' => 5],
        ['phrase' => 'кредит наличными', 'url' => '/kredity', 'title' => 'Кредиты наличными', 'priority' => 5],
        ['phrase' => 'оформить кредит', 'url' => '/kredity', 'title' => 'Оформить кредит', 'priority' => 5],
        ['phrase' => 'взять кредит', 'url' => '/kredity', 'title' => 'Взять кредит', 'priority' => 5],
        ['phrase' => 'кредитные карты', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты', 'priority' => 4],
        ['phrase' => 'кредитная карта', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты', 'priority' => 4],
        ['phrase' => 'дебетовые карты', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты', 'priority' => 4],
        ['phrase' => 'дебетовая карта', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты', 'priority' => 4],
        ['phrase' => 'карта с кэшбеком', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты', 'priority' => 4],
        ['phrase' => 'калькулятор займа', 'url' => '/calculator', 'title' => 'Калькулятор займа', 'priority' => 4],
        ['phrase' => 'калькулятор кредита', 'url' => '/calculator', 'title' => 'Калькулятор кредита', 'priority' => 4],
        ['phrase' => 'сравнить предложения', 'url' => '/compare', 'title' => 'Сравнение предложений', 'priority' => 4],
        ['phrase' => 'полная стоимость кредита', 'url' => '/glossary/psk', 'title' => 'Что такое ПСК', 'priority' => 3],
        ['phrase' => 'процентная ставка', 'url' => '/glossary/procentnaya-stavka', 'title' => 'Процентная ставка', 'priority' => 3],
        ['phrase' => 'льготный период', 'url' => '/glossary/grejs-period', 'title' => 'Льготный период', 'priority' => 3],
        ['phrase' => 'грейс-период', 'url' => '/glossary/grejs-period', 'title' => 'Грейс-период', 'priority' => 3],
        ['phrase' => 'кредитная история', 'url' => '/glossary/kreditnaya-istoriya', 'title' => 'Кредитная история', 'priority' => 3],
        ['phrase' => 'микрофинансовая организация', 'url' => '/glossary/mfo', 'title' => 'Что такое МФО', 'priority' => 3],
        ['phrase' => 'скоринг', 'url' => '/glossary/skoring', 'title' => 'Что такое скоринг', 'priority' => 3],
        ['phrase' => 'рефинансирование', 'url' => '/glossary/refinansirovanie', 'title' => 'Рефинансирование', 'priority' => 3],
        ['phrase' => 'кэшбек', 'url' => '/glossary/keshbek', 'title' => 'Что такое кэшбек', 'priority' => 3],
        ['phrase' => 'аннуитетный платёж', 'url' => '/glossary/annuitetnyj-platezh', 'title' => 'Аннуитетный платёж', 'priority' => 3],
        ['phrase' => 'ПСК', 'url' => '/glossary/psk', 'title' => 'Полная стоимость кредита', 'priority' => 3],
        ['phrase' => 'МФО', 'url' => '/glossary/mfo', 'title' => 'Микрофинансовая организация', 'priority' => 3],
        ['phrase' => 'БКИ', 'url' => '/glossary/bki', 'title' => 'Бюро кредитных историй', 'priority' => 3],
        ['phrase' => 'ЦБ РФ', 'url' => '/glossary/reestr-cb', 'title' => 'Центральный банк', 'priority' => 3],
        ['phrase' => 'сайте Космозайм', 'url' => '/', 'title' => 'Космозайм', 'priority' => 1],
        ['phrase' => 'Космозайм', 'url' => '/', 'title' => 'Космозайм', 'priority' => 1],
    ];
    $currentArticleSlug = (string)($options['current_article_slug'] ?? '');
    $preferredOfferCategory = (string)($options['preferred_offer_category'] ?? '');
    $linkMap = array_merge(getTagLinks(), getCityLinks(), $staticLinks, getOfferLinks($preferredOfferCategory), getArticleLinks($currentArticleSlug));
    usort($linkMap, function($a, $b) {
        $ap = (int)($a['priority'] ?? 0);
        $bp = (int)($b['priority'] ?? 0);
        if ($bp !== $ap) return $bp <=> $ap;
        return mb_strlen($b['phrase']) <=> mb_strlen($a['phrase']);
    });
    return $linkMap;
}

function plainTextToHtml(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $text = str_replace(["
", "
"], "
", $text);
    $blocks = preg_split('/
{2,}/', $text);
    $htmlBlocks = [];
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        $lines = array_values(array_filter(array_map('trim', explode("
", $block)), fn($v) => $v !== ''));
        if (!$lines) continue;
        $allList = true;
        foreach ($lines as $line) {
            if (!preg_match('/^[-*]\s+/', $line)) { $allList = false; break; }
        }
        if ($allList) {
            $items = '';
            foreach ($lines as $line) {
                $item = preg_replace('/^[-*]\s+/', '', $line);
                $items .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $htmlBlocks[] = '<ul>' . $items . '</ul>';
            continue;
        }
        if (count($lines) === 1 && mb_strlen($lines[0]) < 120) {
            $htmlBlocks[] = '<h3>' . htmlspecialchars($lines[0], ENT_QUOTES, 'UTF-8') . '</h3>';
            continue;
        }
        $htmlBlocks[] = '<p>' . htmlspecialchars(implode(' ', $lines), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return implode("
", $htmlBlocks);
}

function autoLinkText(string $html, int $maxLinks = 10, array $options = []): string {
    if (!preg_match('/<[^>]+>/', $html)) {
        $html = plainTextToHtml($html);
    }
    $linkMap = buildAutoLinkMap($options);
    $currentUrl = (string)($options['current_url'] ?? '');
    $usedUrls = [];
    $usedPhrases = [];
    $linkedCount = 0;

    $parts = preg_split('/(<[^>]+>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!$parts) return $html;

    foreach ($parts as $idx => $part) {
        if ($linkedCount >= $maxLinks) break;
        if ($part === '' || $part[0] === '<') continue;

        foreach ($linkMap as $item) {
            if ($linkedCount >= $maxLinks) break;
            if (in_array($item['url'], $usedUrls, true)) continue;
            if ($currentUrl !== '' && $item['url'] === $currentUrl) continue;

            $phrase = trim((string)$item['phrase']);
            if ($phrase === '' || mb_strlen($phrase) < 3) continue;

            $phraseLower = mb_strtolower($phrase);
            $skip = false;
            foreach ($usedPhrases as $used) {
                if (str_contains($phraseLower, $used) || str_contains($used, $phraseLower)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            $pattern = '/(?<![\p{L}\p{N}])(' . preg_quote($phrase, '/') . ')(?![\p{L}\p{N}])/ui';
            if (preg_match($pattern, $parts[$idx])) {
                $link = '<a href="' . $item['url'] . '" title="' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '" class="text-primary hover:underline">$1</a>';
                $parts[$idx] = preg_replace($pattern, $link, $parts[$idx], 1);
                $linkedCount++;
                $usedUrls[] = $item['url'];
                $usedPhrases[] = $phraseLower;
                break;
            }
        }
    }

    return implode('', $parts);
}



function getArticleInlineCtaVariant(): string {
    static $variant = null;
    if ($variant !== null) return $variant;

    $cookieKey = 'article_inline_cta_ab';
    if (!empty($_COOKIE[$cookieKey]) && in_array($_COOKIE[$cookieKey], ['a', 'b'], true)) {
        return $variant = $_COOKIE[$cookieKey];
    }

    $variant = mt_rand(0, 1) === 0 ? 'a' : 'b';
    setcookie($cookieKey, $variant, time() + 86400 * 30, '/');
    $_COOKIE[$cookieKey] = $variant;
    return $variant;
}

function renderArticleInlineOfferCta(array $offer): string {
    $logo = normalizeMediaUrl($offer['logo_url'] ?? '');
    $amount = '';
    if (!empty($offer['amount_max'])) {
        $amount = 'до ' . formatMoney((int)$offer['amount_max']);
    }
    $rate = !empty($offer['rate']) ? formatRateDisplay($offer) : '';
    $free = !empty($offer['free_term_days']) ? ' • 0% на ' . (int)$offer['free_term_days'] . ' дн.' : '';
    $category = (string)($offer['category'] ?? 'microloans');
    $defaultButton = match ($category) {
        'credits' => 'Подать заявку',
        'credit_cards' => 'Оформить карту',
        'debit_cards' => 'Заказать карту',
        default => 'Перейти к оформлению',
    };

    $variant = getArticleInlineCtaVariant();
    $labelTop = $variant === 'b' ? 'Не откладывайте решение' : 'Подходящее предложение по теме';
    $headline = $variant === 'b'
        ? 'Проверьте условия ' . $offer['title'] . ' прямо сейчас'
        : $offer['title'];
    $subline = $variant === 'b'
        ? 'Пока читаете статью, можно сразу открыть анкету и сравнить условия.'
        : 'Условия, которые хорошо подходят к теме этой статьи.';
    $buttonText = $variant === 'b' ? 'Открыть заявку сейчас' : $defaultButton;
    $wrapClass = $variant === 'b'
        ? 'not-prose my-8 overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 shadow-sm'
        : 'not-prose my-8 overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-green-50 shadow-sm';
    $ringClass = $variant === 'b' ? 'ring-amber-100' : 'ring-emerald-100';
    $topClass = $variant === 'b' ? 'text-amber-700' : 'text-emerald-700';
    $buttonClass = $variant === 'b'
        ? 'inline-flex items-center justify-center rounded-xl bg-orange-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-700'
        : 'inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700';

    ob_start(); ?>
    <div class="<?= $wrapClass ?>" data-inline-cta-variant="<?= e($variant) ?>">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4 min-w-0">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 <?= $ringClass ?>">
                    <?php if ($logo): ?>
                        <img src="<?= e($logo) ?>" alt="<?= e($offer['title']) ?>" class="h-full w-full object-contain p-2" loading="lazy">
                    <?php else: ?>
                        <span class="text-2xl"><?= $variant === 'b' ? '⚡' : '💰' ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide <?= $topClass ?>"><?= e($labelTop) ?></p>
                    <a href="/offer/<?= e($offer['slug']) ?>" class="block text-lg font-bold text-gray-900 hover:text-emerald-700"><?= e($headline) ?></a>
                    <p class="mt-1 text-sm text-gray-600"><?= e($subline) ?></p>
                    <p class="mt-2 text-sm text-gray-600">
                        <?php if ($amount): ?><span><?= e($amount) ?></span><?php endif; ?>
                        <?php if ($amount && $rate): ?><span> • </span><?php endif; ?>
                        <?php if ($rate): ?><span><?= e($rate) ?></span><?php endif; ?>
                        <?php if ($free): ?><span><?= e($free) ?></span><?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex-shrink-0">
                <a href="/click/<?= (int)$offer['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored"
                   onclick="setTimeout(function(){window.location='/thankyou?offer=<?= (int)$offer['id'] ?>';},300)"
                   class="<?= $buttonClass ?>">
                    <?= e($buttonText) ?>
                </a>
            </div>
        </div>
    </div>
    <?php return ob_get_clean();
}

function injectInlineOfferCta(string $html, ?array $offer = null, int $afterParagraph = 2): string {
    if (!$offer) return $html;
    if (stripos($html, '/offer/' . ($offer['slug'] ?? '')) !== false) return $html;

    $cta = renderArticleInlineOfferCta($offer);

    if (preg_match_all('/<p\b[^>]*>.*?<\/p>/isu', $html, $matches, PREG_OFFSET_CAPTURE) && count($matches[0]) > $afterParagraph) {
        $target = $matches[0][$afterParagraph - 1];
        $insertPos = $target[1] + strlen($target[0]);
        return substr($html, 0, $insertPos) . $cta . substr($html, $insertPos);
    }

    if (preg_match('/(<h2\b[^>]*>.*?<\/h2>)/isu', $html, $m, PREG_OFFSET_CAPTURE)) {
        $insertPos = $m[1][1] + strlen($m[1][0]);
        return substr($html, 0, $insertPos) . $cta . substr($html, $insertPos);
    }

    return $cta . $html;
}

function safeAutoLink(string $text, int $maxLinks = 10, array $options = []): string {
    $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $html = nl2br($html);
    return autoLinkText($html, $maxLinks, $options);
}

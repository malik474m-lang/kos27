<?php
function getOfferLinks(): array {
    $cacheFile = __DIR__ . '/../data/offer-links-cache.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    $links = [];
    try {
        $db = getDB();
        $offers = $db->query("SELECT title, slug FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        foreach ($offers as $offer) {
            $url = '/offer/' . $offer['slug'];
            $links[] = ['phrase' => $offer['title'], 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн', 'priority' => 20];
            $lower = mb_strtolower($offer['title']);
            if ($lower !== $offer['title']) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн', 'priority' => 19];
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
    foreach ($cities as $city) {
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

function buildAutoLinkMap(): array {
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
    $linkMap = array_merge(getTagLinks(), getCityLinks(), $staticLinks, getOfferLinks());
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
", ""], "
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

function autoLinkText(string $html, int $maxLinks = 10): string {
    if (!preg_match('/<[^>]+>/', $html)) {
        $html = plainTextToHtml($html);
    }
    $linkMap = buildAutoLinkMap();
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

function safeAutoLink(string $text, int $maxLinks = 10): string {
    $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $html = nl2br($html);
    return autoLinkText($html, $maxLinks);
}

<?php
/**
 * Автоматическая перелинковка в текстах
 * - Теги и офферы подтягиваются из БД автоматически
 * - Каждая фраза линкуется только 1 раз
 * - Один URL не повторяется
 * - Длинные фразы первыми
 */

/**
 * Получить ссылки на офферы из БД (кэш 5 мин)
 */
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
            $links[] = ['phrase' => $offer['title'], 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн'];
            $lower = mb_strtolower($offer['title']);
            if ($lower !== $offer['title']) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $offer['title'] . ' — оформить онлайн'];
            }
        }
        @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {}
    return $links;
}

/**
 * Получить ссылки на теги из БД (кэш 5 мин)
 */
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
        $tags = $db->query("SELECT title, slug, category, h1 FROM offer_tags WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        foreach ($tags as $tag) {
            $catUrl = $catUrls[$tag['category']] ?? '/zajmy';
            $url = $catUrl . '/type/' . $tag['slug'];
            $title = $tag['title'];
            // Основная фраза
            $links[] = ['phrase' => $title, 'url' => $url, 'title' => $title];
            // Без "Займы " / "Кредиты " в начале — если остаётся осмысленная фраза
            foreach (['Займы ', 'Кредиты ', 'Карты '] as $prefix) {
                if (mb_stripos($title, $prefix) === 0) {
                    $short = mb_substr($title, mb_strlen($prefix));
                    if (mb_strlen($short) >= 4) {
                        $links[] = ['phrase' => $short, 'url' => $url, 'title' => $title];
                        $links[] = ['phrase' => mb_strtolower($short), 'url' => $url, 'title' => $title];
                    }
                }
            }
            // Из H1 если отличается от title
            if ($tag['h1'] && $tag['h1'] !== $title) {
                $links[] = ['phrase' => $tag['h1'], 'url' => $url, 'title' => $title];
            }
            // Строчная версия
            $lower = mb_strtolower($title);
            if ($lower !== $title) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $title];
            }
        }
        @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {}
    return $links;
}

function autoLinkText(string $text, int $maxLinks = 10): string {
    // Статическая карта — общие фразы
    $linkMap = [
        // === ЗАЙМЫ общие ===
        ['phrase' => 'микрозаймы онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн'],
        ['phrase' => 'микрозайм онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн'],
        ['phrase' => 'займы онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн'],
        ['phrase' => 'займ онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн'],
        ['phrase' => 'оформить займ', 'url' => '/zajmy', 'title' => 'Оформить займ'],
        ['phrase' => 'взять займ', 'url' => '/zajmy', 'title' => 'Взять займ'],
        ['phrase' => 'получить займ', 'url' => '/zajmy', 'title' => 'Получить займ'],
        ['phrase' => 'займы на карту', 'url' => '/zajmy', 'title' => 'Займы на карту'],
        ['phrase' => 'займ на карту', 'url' => '/zajmy', 'title' => 'Займы на карту'],
        ['phrase' => 'микрозаймы', 'url' => '/zajmy', 'title' => 'Микрозаймы'],
        ['phrase' => 'микрозайм', 'url' => '/zajmy', 'title' => 'Микрозаймы'],

        // === КРЕДИТЫ общие ===
        ['phrase' => 'потребительский кредит', 'url' => '/kredity', 'title' => 'Потребительские кредиты'],
        ['phrase' => 'банковский кредит', 'url' => '/kredity', 'title' => 'Банковские кредиты'],
        ['phrase' => 'кредит наличными', 'url' => '/kredity', 'title' => 'Кредиты наличными'],
        ['phrase' => 'оформить кредит', 'url' => '/kredity', 'title' => 'Оформить кредит'],
        ['phrase' => 'взять кредит', 'url' => '/kredity', 'title' => 'Взять кредит'],

        // === КАРТЫ общие ===
        ['phrase' => 'кредитные карты', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты'],
        ['phrase' => 'кредитная карта', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты'],
        ['phrase' => 'дебетовые карты', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],
        ['phrase' => 'дебетовая карта', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],
        ['phrase' => 'карта с кэшбеком', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],

        // === ИНСТРУМЕНТЫ ===
        ['phrase' => 'калькулятор займа', 'url' => '/calculator', 'title' => 'Калькулятор займа'],
        ['phrase' => 'калькулятор кредита', 'url' => '/calculator', 'title' => 'Калькулятор кредита'],
        ['phrase' => 'сравнить предложения', 'url' => '/compare', 'title' => 'Сравнение предложений'],

        // === ГЛОССАРИЙ ===
        ['phrase' => 'полная стоимость кредита', 'url' => '/glossary/psk', 'title' => 'Что такое ПСК'],
        ['phrase' => 'процентная ставка', 'url' => '/glossary/procentnaya-stavka', 'title' => 'Процентная ставка'],
        ['phrase' => 'льготный период', 'url' => '/glossary/grejs-period', 'title' => 'Льготный период'],
        ['phrase' => 'грейс-период', 'url' => '/glossary/grejs-period', 'title' => 'Грейс-период'],
        ['phrase' => 'кредитная история', 'url' => '/glossary/kreditnaya-istoriya', 'title' => 'Кредитная история'],
        ['phrase' => 'микрофинансовая организация', 'url' => '/glossary/mfo', 'title' => 'Что такое МФО'],
        ['phrase' => 'скоринг', 'url' => '/glossary/skoring', 'title' => 'Что такое скоринг'],
        ['phrase' => 'рефинансирование', 'url' => '/glossary/refinansirovanie', 'title' => 'Рефинансирование'],
        ['phrase' => 'кэшбек', 'url' => '/glossary/keshbek', 'title' => 'Что такое кэшбек'],
        ['phrase' => 'аннуитетный платёж', 'url' => '/glossary/annuitetnyj-platezh', 'title' => 'Аннуитетный платёж'],
        ['phrase' => 'ПСК', 'url' => '/glossary/psk', 'title' => 'Полная стоимость кредита'],
        ['phrase' => 'МФО', 'url' => '/glossary/mfo', 'title' => 'Микрофинансовая организация'],
        ['phrase' => 'БКИ', 'url' => '/glossary/bki', 'title' => 'Бюро кредитных историй'],
        ['phrase' => 'ЦБ РФ', 'url' => '/glossary/reestr-cb', 'title' => 'Центральный банк'],

        // === САЙТ ===
        ['phrase' => 'сайте Космозайм', 'url' => '/', 'title' => 'Космозайм'],
        ['phrase' => 'Космозайм', 'url' => '/', 'title' => 'Космозайм'],
    ];

    // Добавляем теги из БД (автоматически!)
    $tagLinks = getTagLinks();
    $linkMap = array_merge($tagLinks, $linkMap);

    // Добавляем офферы из БД
    $offerLinks = getOfferLinks();
    $linkMap = array_merge($offerLinks, $linkMap);

    // Сортируем по длине (длинные первыми)
    usort($linkMap, function($a, $b) {
        return mb_strlen($b['phrase']) <=> mb_strlen($a['phrase']);
    });

    $linkedCount = 0;
    $usedUrls = [];
    $usedPhrases = [];

    foreach ($linkMap as $item) {
        if ($linkedCount >= $maxLinks) break;
        if (in_array($item['url'], $usedUrls, true)) continue;

        $phraseLower = mb_strtolower($item['phrase']);
        $skip = false;
        foreach ($usedPhrases as $used) {
            if (str_contains($phraseLower, $used) || str_contains($used, $phraseLower)) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        $pattern = '/(?<![а-яёa-z\">])(' . preg_quote($item['phrase'], '/') . ')(?![а-яёa-z\"<])/ui';

        if (preg_match($pattern, $text)) {
            $link = '<a href="' . $item['url'] . '" title="' . htmlspecialchars($item['title'], ENT_QUOTES) . '" class="text-primary hover:underline">$1</a>';
            $text = preg_replace($pattern, $link, $text, 1);
            $linkedCount++;
            $usedUrls[] = $item['url'];
            $usedPhrases[] = $phraseLower;
        }
    }

    return $text;
}

/**
 * Безопасный вывод текста с автолинками
 */
function safeAutoLink(string $text, int $maxLinks = 10): string {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = nl2br($text);
    $text = autoLinkText($text, $maxLinks);
    return $text;
}

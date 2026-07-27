<?php
/**
 * Автоматическая перелинковка в текстах
 * - Каждая фраза линкуется только 1 раз (первое вхождение)
 * - Один URL не повторяется (даже для разных фраз)
 * - Офферы подтягиваются автоматически из БД
 * - Сначала обрабатываются длинные фразы, потом короткие
 */

/**
 * Получить карту ссылок на офферы из БД (кэшируется на 5 мин)
 */
function getOfferLinks(): array {
    $cacheFile = __DIR__ . '/../data/offer-links-cache.json';
    
    // Кэш на 5 минут
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached) return $cached;
    }
    
    $links = [];
    try {
        $db = getDB();
        $offers = $db->query("SELECT title, slug FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        
        foreach ($offers as $offer) {
            $title = $offer['title'];
            $url = '/offer/' . $offer['slug'];
            
            // Добавляем точное название
            $links[] = ['phrase' => $title, 'url' => $url, 'title' => $title . ' — оформить онлайн'];
            
            // Добавляем вариации (без регистра, если латиница)
            $lower = mb_strtolower($title);
            if ($lower !== $title) {
                $links[] = ['phrase' => $lower, 'url' => $url, 'title' => $title . ' — оформить онлайн'];
            }
        }
        
        // Кэшируем
        @file_put_contents($cacheFile, json_encode($links, JSON_UNESCAPED_UNICODE));
    } catch (Exception $e) {
        // Если БД недоступна — пустой список
    }
    
    return $links;
}

function autoLinkText(string $text, int $maxLinks = 10): string {
    // Статическая карта перелинковки
    $linkMap = [
        // === ЗАЙМЫ ===
        ['phrase' => 'займы без отказа', 'url' => '/zajmy/type/bez-otkaza', 'title' => 'Займы без отказа'],
        ['phrase' => 'займ без отказа', 'url' => '/zajmy/type/bez-otkaza', 'title' => 'Займы без отказа'],
        ['phrase' => 'получить займ без отказа', 'url' => '/zajmy/type/bez-otkaza', 'title' => 'Займы без отказа'],
        ['phrase' => 'займы с плохой кредитной историей', 'url' => '/zajmy/type/s-plohoj-kreditnoj-istoriej', 'title' => 'Займы с плохой КИ'],
        ['phrase' => 'плохая кредитная история', 'url' => '/zajmy/type/s-plohoj-kreditnoj-istoriej', 'title' => 'Займы с плохой КИ'],
        ['phrase' => 'займы без процентов', 'url' => '/zajmy/type/bez-procentov', 'title' => 'Займы без процентов'],
        ['phrase' => 'займ без процентов', 'url' => '/zajmy/type/bez-procentov', 'title' => 'Займы без процентов'],
        ['phrase' => 'первый займ под 0%', 'url' => '/zajmy/type/bez-procentov', 'title' => 'Займы без процентов'],
        ['phrase' => 'первый займ бесплатно', 'url' => '/zajmy/type/bez-procentov', 'title' => 'Займы без процентов'],
        ['phrase' => 'беспроцентный займ', 'url' => '/zajmy/type/bez-procentov', 'title' => 'Займы без процентов'],
        ['phrase' => 'займы для пенсионеров', 'url' => '/zajmy/type/dlya-pensionerov', 'title' => 'Займы для пенсионеров'],
        ['phrase' => 'займы пенсионерам', 'url' => '/zajmy/type/dlya-pensionerov', 'title' => 'Займы пенсионерам'],
        ['phrase' => 'займы студентам', 'url' => '/zajmy/type/studentam', 'title' => 'Займы студентам'],
        ['phrase' => 'займ для студентов', 'url' => '/zajmy/type/studentam', 'title' => 'Займы для студентов'],
        ['phrase' => 'срочный займ', 'url' => '/zajmy/type/srochno', 'title' => 'Срочные займы'],
        ['phrase' => 'срочные займы', 'url' => '/zajmy/type/srochno', 'title' => 'Срочные займы'],
        ['phrase' => 'займ срочно', 'url' => '/zajmy/type/srochno', 'title' => 'Срочные займы'],
        ['phrase' => 'деньги срочно', 'url' => '/zajmy/type/srochno', 'title' => 'Срочные займы'],
        ['phrase' => 'микрозаймы онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн'],
        ['phrase' => 'микрозайм онлайн', 'url' => '/zajmy', 'title' => 'Микрозаймы онлайн'],
        ['phrase' => 'займы онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн'],
        ['phrase' => 'займ онлайн', 'url' => '/zajmy', 'title' => 'Займы онлайн'],
        ['phrase' => 'оформить займ', 'url' => '/zajmy', 'title' => 'Оформить займ'],
        ['phrase' => 'взять займ', 'url' => '/zajmy', 'title' => 'Взять займ'],
        ['phrase' => 'получить займ', 'url' => '/zajmy', 'title' => 'Получить займ'],
        ['phrase' => 'займы на карту', 'url' => '/zajmy', 'title' => 'Займы на карту'],
        ['phrase' => 'займ на карту', 'url' => '/zajmy', 'title' => 'Займы на карту'],
        ['phrase' => 'деньги на карту', 'url' => '/zajmy', 'title' => 'Деньги на карту'],
        ['phrase' => 'онлайн-займ', 'url' => '/zajmy', 'title' => 'Онлайн-займы'],
        ['phrase' => 'микрозаймы', 'url' => '/zajmy', 'title' => 'Микрозаймы'],
        ['phrase' => 'микрозайм', 'url' => '/zajmy', 'title' => 'Микрозаймы'],
        
        // === КРЕДИТЫ ===
        ['phrase' => 'потребительский кредит', 'url' => '/kredity', 'title' => 'Потребительские кредиты'],
        ['phrase' => 'потребительские кредиты', 'url' => '/kredity', 'title' => 'Потребительские кредиты'],
        ['phrase' => 'банковский кредит', 'url' => '/kredity', 'title' => 'Банковские кредиты'],
        ['phrase' => 'кредит наличными', 'url' => '/kredity', 'title' => 'Кредиты наличными'],
        ['phrase' => 'кредит онлайн', 'url' => '/kredity', 'title' => 'Кредиты онлайн'],
        ['phrase' => 'оформить кредит', 'url' => '/kredity', 'title' => 'Оформить кредит'],
        ['phrase' => 'взять кредит', 'url' => '/kredity', 'title' => 'Взять кредит'],
        ['phrase' => 'рефинансирование кредита', 'url' => '/kredity', 'title' => 'Рефинансирование'],
        ['phrase' => 'досрочное погашение', 'url' => '/kredity', 'title' => 'Досрочное погашение'],
        
        // === КАРТЫ ===
        ['phrase' => 'кредитные карты', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты'],
        ['phrase' => 'кредитная карта', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты'],
        ['phrase' => 'кредитка', 'url' => '/karty/kreditnye', 'title' => 'Кредитные карты'],
        ['phrase' => 'дебетовые карты', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],
        ['phrase' => 'дебетовая карта', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],
        ['phrase' => 'карта с кэшбеком', 'url' => '/karty/debetovye', 'title' => 'Дебетовые карты'],
        ['phrase' => 'банковские карты', 'url' => '/karty/kreditnye', 'title' => 'Банковские карты'],
        
        // === ИНСТРУМЕНТЫ ===
        ['phrase' => 'калькулятор займа', 'url' => '/calculator', 'title' => 'Калькулятор займа'],
        ['phrase' => 'калькулятор кредита', 'url' => '/calculator', 'title' => 'Калькулятор кредита'],
        ['phrase' => 'рассчитать займ', 'url' => '/calculator', 'title' => 'Калькулятор займа'],
        ['phrase' => 'сравнить предложения', 'url' => '/compare', 'title' => 'Сравнение предложений'],
        ['phrase' => 'сравнение предложений', 'url' => '/compare', 'title' => 'Сравнение предложений'],
        
        // === ГЛОССАРИЙ ===
        ['phrase' => 'полная стоимость кредита', 'url' => '/glossary/psk', 'title' => 'Что такое ПСК'],
        ['phrase' => 'процентная ставка', 'url' => '/glossary/procentnaya-stavka', 'title' => 'Процентная ставка'],
        ['phrase' => 'льготный период', 'url' => '/glossary/grejs-period', 'title' => 'Льготный период'],
        ['phrase' => 'грейс-период', 'url' => '/glossary/grejs-period', 'title' => 'Грейс-период'],
        ['phrase' => 'кредитная история', 'url' => '/glossary/kreditnaya-istoriya', 'title' => 'Кредитная история'],
        ['phrase' => 'микрофинансовая организация', 'url' => '/glossary/mfo', 'title' => 'Что такое МФО'],
        ['phrase' => 'микрофинансовые организации', 'url' => '/glossary/mfo', 'title' => 'Что такое МФО'],
        ['phrase' => 'скоринг', 'url' => '/glossary/skoring', 'title' => 'Что такое скоринг'],
        ['phrase' => 'рефинансирование', 'url' => '/glossary/refinansirovanie', 'title' => 'Рефинансирование'],
        ['phrase' => 'перекредитование', 'url' => '/glossary/refinansirovanie', 'title' => 'Рефинансирование'],
        ['phrase' => 'кэшбек', 'url' => '/glossary/keshbek', 'title' => 'Что такое кэшбек'],
        ['phrase' => 'аннуитетный платёж', 'url' => '/glossary/annuitetnyj-platezh', 'title' => 'Аннуитетный платёж'],
        ['phrase' => 'аннуитетный платеж', 'url' => '/glossary/annuitetnyj-platezh', 'title' => 'Аннуитетный платёж'],
        ['phrase' => 'бюро кредитных историй', 'url' => '/glossary/bki', 'title' => 'Что такое БКИ'],
        ['phrase' => 'просрочка', 'url' => '/glossary/prosrochka', 'title' => 'Просрочка по займу'],
        
        // === АББРЕВИАТУРЫ ===
        ['phrase' => 'ПСК', 'url' => '/glossary/psk', 'title' => 'Полная стоимость кредита'],
        ['phrase' => 'МФО', 'url' => '/glossary/mfo', 'title' => 'Микрофинансовая организация'],
        ['phrase' => 'МФК', 'url' => '/glossary/mfo', 'title' => 'Микрофинансовая компания'],
        ['phrase' => 'МКК', 'url' => '/glossary/mfo', 'title' => 'Микрокредитная компания'],
        ['phrase' => 'БКИ', 'url' => '/glossary/bki', 'title' => 'Бюро кредитных историй'],
        ['phrase' => 'ЦБ РФ', 'url' => '/glossary/reestr-cb', 'title' => 'Центральный банк'],
        
        // === САЙТ ===
        ['phrase' => 'сайте Космозайм', 'url' => '/', 'title' => 'Космозайм'],
        ['phrase' => 'Космозайм', 'url' => '/', 'title' => 'Космозайм'],
    ];

    // Добавляем офферы из БД (автоматически)
    $offerLinks = getOfferLinks();
    $linkMap = array_merge($offerLinks, $linkMap);

    // Сортируем по длине фразы (длинные сначала)
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

        $pattern = '/(?<![а-яёa-z">])(' . preg_quote($item['phrase'], '/') . ')(?![а-яёa-z"<])/ui';
        
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

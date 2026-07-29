<?php
/**
 * Генерация SEO-текстов для городов
 */

/**
 * Очистка ответа GPT от markdown-обёрток и мусора
 */
function cleanGptHtml(string $text): string {
    // Убираем ```html ... ``` и ```...```
    $text = preg_replace('/^```\s*html?\s*\n?/i', '', $text);
    $text = preg_replace('/\n?```\s*$/', '', $text);
    $text = preg_replace('/```/', '', $text);

    // Убираем markdown-заголовки если GPT проигнорировал промт
    // ## Заголовок → <h3>Заголовок</h3>
    $text = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^##\s+(.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^#\s+(.+)$/m', '<h3>$1</h3>', $text);

    // Убираем **жирный** → <strong>
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

    // Убираем *курсив* → <em>
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

    // Markdown списки → HTML
    // Строки начинающиеся с "- " или "* " → <li>
    if (preg_match('/^[\-\*]\s+/m', $text)) {
        $text = preg_replace('/(?:^[\-\*]\s+.+$\n?)+/m', function($m) {
            $items = preg_split('/\n/', trim($m[0]));
            $lis = '';
            foreach ($items as $item) {
                $item = preg_replace('/^[\-\*]\s+/', '', trim($item));
                if ($item) $lis .= '<li>' . $item . '</li>';
            }
            return '<ul>' . $lis . '</ul>';
        }, $text);
    }

    // Оборачиваем голые строки текста в <p> (если ещё нет тегов)
    $lines = explode("\n", $text);
    $result = '';
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!$trimmed) continue;
        // Уже HTML-тег — оставляем как есть
        if (preg_match('/^<(h[1-6]|p|ul|ol|li|div|table|strong|em|a|br)[\s>\/]/i', $trimmed)) {
            $result .= $trimmed . "\n";
        } else {
            $result .= '<p>' . $trimmed . '</p>' . "\n";
        }
    }

    return trim($result);
}

function getCitySeoText(array $city, string $category = 'microloans'): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM city_seo_texts WHERE city_slug = ? AND category = ? LIMIT 1");
    $stmt->execute([$city['slug'], $category]);
    return $stmt->fetch() ?: null;
}

function generateCitySeoTemplate(array $city, string $category = 'microloans'): array {
    $name = $city['name'];
    $prep = $city['prep'];
    $region = $city['region'];
    $year = date('Y');

    $catLabels = ['microloans'=>'займы','credits'=>'кредиты','credit_cards'=>'кредитные карты','debit_cards'=>'дебетовые карты'];
    $catLabel = $catLabels[$category] ?? 'займы';

    $templates = [
        'microloans' => [
            [
                'h1' => "Займы в {$prep} на карту — быстрое оформление онлайн",
                'text' => "<h3>Где взять займ в {$prep}?</h3>
<p>В {$year} году жители {$name} могут оформить микрозайм онлайн за несколько минут. Не нужно посещать офис — заявка подаётся через интернет, а деньги поступают на банковскую карту любого банка РФ.</p>
<h3>Условия получения займа в {$prep}</h3>
<p>Микрофинансовые организации, работающие в {$prep} ({$region}), предлагают займы от 1 000 до 100 000 рублей. Срок — от 1 дня до 12 месяцев. Многие компании одобряют заявки за 5-15 минут и переводят деньги мгновенно.</p>
<h3>Требования к заёмщикам</h3>
<ul><li>Возраст от 18 лет</li><li>Гражданство Российской Федерации</li><li>Регистрация или проживание в {$prep} ({$region})</li><li>Действующий паспорт РФ</li><li>Банковская карта на имя заёмщика</li></ul>
<h3>Первый займ под 0% в {$prep}</h3>
<p>Большинство МФО предлагают первый займ без процентов для новых клиентов в {$prep}. Условие — вернуть деньги в срок (обычно 7-30 дней). В этом случае вы платите ровно ту сумму, которую взяли.</p>
<h3>Популярные суммы займов</h3>
<p>По статистике, жители {$name} чаще всего берут займы на сумму от 5 000 до 30 000 рублей сроком до 30 дней. Для постоянных клиентов МФО доступны повышенные лимиты — до 100 000 рублей и более.</p>",
            ],
            [
                'h1' => "Микрозаймы в {$prep} — онлайн заявка, деньги за 15 минут",
                'text' => "<h3>Займы онлайн в {$prep}</h3>
<p>Жителям {$name} ({$region}) доступны онлайн-займы от проверенных микрофинансовых организаций. Оформление занимает 5-10 минут, деньги поступают на карту мгновенно — круглосуточно, включая выходные.</p>
<h3>Преимущества онлайн-займов</h3>
<ul><li>Не нужно ехать в офис — всё онлайн</li><li>Минимум документов — только паспорт</li><li>Решение за 5-15 минут</li><li>Деньги на карту любого банка РФ</li><li>Первый займ под 0%</li></ul>
<h3>Какую сумму можно взять?</h3>
<p>В {$year} году МФО в {$prep} выдают займы от 1 000 до 100 000 рублей. Новым клиентам доступно до 30 000 рублей, постоянным — до 100 000 и выше.</p>
<h3>Процентные ставки</h3>
<p>Средняя ставка по микрозаймам в {$prep} — от 0% до 1% в день. При первом обращении многие МФО предлагают беспроцентный период от 7 до 30 дней.</p>",
            ],
        ],
        'credits' => [
            [
                'h1' => "Кредиты в {$prep} — сравнение условий банков",
                'text' => "<h3>Банковские кредиты в {$prep}</h3>
<p>Банки, работающие в {$prep} ({$region}), предлагают потребительские кредиты на различные цели. Сравните условия и подберите кредит с минимальной ставкой.</p>
<h3>Как выбрать кредит</h3>
<ul><li>Сравните процентные ставки нескольких банков</li><li>Обратите внимание на полную стоимость кредита (ПСК)</li><li>Проверьте скрытые комиссии и обязательные страховки</li><li>Оцените условия досрочного погашения</li></ul>
<p>Жители {$name} могут оформить кредит онлайн — заявка рассматривается дистанционно, а деньги поступают на карту или счёт.</p>",
            ],
        ],
        'credit_cards' => [
            [
                'h1' => "Кредитные карты в {$prep} — сравнение",
                'text' => "<h3>Лучшие кредитные карты в {$prep}</h3>
<p>Сравните кредитные карты, доступные жителям {$name}. Выбирайте карту с длинным льготным периодом и выгодным кэшбеком.</p>
<h3>На что обратить внимание</h3>
<ul><li>Длительность грейс-периода — от 55 до 120 дней</li><li>Кэшбек — процент возврата с покупок</li><li>Стоимость годового обслуживания</li><li>Ставка после окончания льготного периода</li></ul>
<p>Оформление кредитной карты в {$prep} занимает от 1 до 5 рабочих дней. Многие банки доставляют карту на дом.</p>",
            ],
        ],
        'debit_cards' => [
            [
                'h1' => "Дебетовые карты в {$prep} — с кэшбеком",
                'text' => "<h3>Дебетовые карты для жителей {$name}</h3>
<p>Выберите дебетовую карту с лучшими условиями в {$prep}. Сравните процент на остаток, кэшбек и стоимость обслуживания.</p>
<h3>Критерии выбора</h3>
<ul><li>Кэшбек — до 10-30% в выбранных категориях</li><li>Процент на остаток — до 20% годовых</li><li>Бесплатное обслуживание</li><li>Бесплатные переводы и снятия</li></ul>
<p>Жители {$name} ({$region}) могут заказать дебетовую карту онлайн с доставкой на дом.</p>",
            ],
        ],
    ];

    $catTemplates = $templates[$category] ?? $templates['microloans'];
    $idx = crc32($city['slug'] . $category) % count($catTemplates);
    $tpl = $catTemplates[$idx];

    return [
        'meta_title' => ($tpl['h1'] . ' | ' . SITE_NAME),
        'seo_h1' => $tpl['h1'],
        'seo_text' => $tpl['text'],
        'meta_description' => "Оформите {$catLabel} в {$prep} онлайн. Быстрое одобрение, выгодные условия. Сравните предложения на Космозайм.",
        'generated_by' => 'template',
    ];
}

function generateCitySeoGPT(array $city, string $category = 'microloans'): ?array {
    if (!YANDEX_GPT_API_KEY || !YANDEX_FOLDER_ID) return null;

    $catLabels = ['microloans'=>'микрозаймы','credits'=>'банковские кредиты','credit_cards'=>'кредитные карты','debit_cards'=>'дебетовые карты'];
    $catLabel = $catLabels[$category] ?? 'финансовые продукты';
    $year = date('Y');

    $prompt = "Напиши SEO-текст для страницы \"{$catLabel} в {$city['prep']}\" для финансового сайта. "
        . "Город: {$city['name']}, регион: {$city['region']}. "
        . "Текст 300-500 слов. "
        . "Используй подзаголовки, списки и абзацы. "
        . "Упомяни особенности получения {$catLabel} в {$city['prep']}, требования к заёмщикам, преимущества онлайн-оформления. "
        . "ВАЖНО: не добавляй год в квадратных скобках и вообще не используй формат вроде [2026]. Пиши только чистый текст. НЕ оборачивай в блоки кода. Без тройных кавычек. Без слова html в начале. Просто текст с HTML-тегами.";

    $response = @file_get_contents('https://llm.api.cloud.yandex.net/foundationModels/v1/completion', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Api-Key " . YANDEX_GPT_API_KEY . "\r\nx-folder-id: " . YANDEX_FOLDER_ID,
            'content' => json_encode([
                'modelUri' => 'gpt://' . YANDEX_FOLDER_ID . '/yandexgpt/latest',
                'completionOptions' => ['stream' => false, 'temperature' => 0.5, 'maxTokens' => 4000],
                'messages' => [
                    ['role' => 'system', 'text' => "Ты SEO-копирайтер. Пиши на русском. Форматируй текст HTML-тегами: h3 для подзаголовков, p для абзацев, ul и li для списков. Никогда не оборачивай ответ в блоки кода (без тройных обратных кавычек). Не пиши слово html. Не добавляй год в квадратных скобках и не используй конструкции вида [2026]. Просто отдай готовый HTML-текст."],
                    ['role' => 'user', 'text' => $prompt],
                ],
            ]),
            'timeout' => 60,
        ],
    ]));

    if (!$response) return null;
    $data = json_decode($response, true);
    $text = $data['result']['alternatives'][0]['message']['text'] ?? null;
    if (!$text || mb_strlen($text) < 200) return null;

    // Чистим от markdown-мусора
    $text = cleanGptHtml($text);

    $catLabelUp = mb_strtoupper(mb_substr($catLabel, 0, 1)) . mb_substr($catLabel, 1);

    return [
        'meta_title' => "{$catLabelUp} в {$city['prep']} | " . SITE_NAME,
        'seo_h1' => "{$catLabelUp} в {$city['prep']} — онлайн оформление",
        'seo_text' => $text,
        'meta_description' => "{$catLabelUp} в {$city['prep']}. Сравните условия, оформите онлайн. Быстрое одобрение.",
        'generated_by' => 'yandexgpt',
    ];
}

function saveCitySeo(string $citySlug, string $category, array $seoData): void {
    $db = getDB();
    $db->prepare("INSERT INTO city_seo_texts (city_slug, category, meta_title, seo_h1, seo_text, meta_description, generated_by) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title), seo_h1=VALUES(seo_h1), seo_text=VALUES(seo_text), meta_description=VALUES(meta_description), generated_by=VALUES(generated_by)")
       ->execute([$citySlug, $category, $seoData['meta_title'] ?? null, $seoData['seo_h1'], $seoData['seo_text'], $seoData['meta_description'], $seoData['generated_by']]);
}

function getOrGenerateCitySeo(array $city, string $category = 'microloans'): array {
    $existing = getCitySeoText($city, $category);
    if ($existing) return $existing;
    $seo = generateCitySeoTemplate($city, $category);
    try { saveCitySeo($city['slug'], $category, $seo); } catch (Exception $e) {}
    return $seo;
}

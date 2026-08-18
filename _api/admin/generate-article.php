<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
require_once __DIR__ . "/../../includes/ai-providers.php";
require_once __DIR__ . '/../../includes/article-image.php';

$currentYear = date('Y');

// Базовый список тем
$baseTopics = [
    ['category' => 'займы', 'themes' => [
        'Как получить займ без отказа','Первый займ без процентов: условия и подводные камни',
        'Как улучшить шансы на одобрение займа','Что делать при просрочке микрозайма',
        'Рефинансирование займов: когда это выгодно','Займы для пенсионеров: особенности и условия',
        'Займы студентам: как получить деньги на учёбу','Безопасность при оформлении онлайн-займа',
        'Как выбрать надёжную МФО','Новые правила выдачи микрозаймов в России',
        'Займы на карту мгновенно: как это работает','Чем отличается займ от кредита',
        'Займ срочно: где получить деньги за 15 минут','Займы без проверки кредитной истории',
        'Займы на Qiwi кошелёк: как получить','Займы на Юмани: условия и особенности',
        'Займы с 18 лет: где оформить','Займы без отказа с плохой кредитной историей',
        'Топ-10 МФО с моментальным одобрением','Как избежать долговой ямы при микрозаймах',
        'Займы наличными: где ещё можно получить','Ночные займы: кто выдаёт круглосуточно',
        'Займы по паспорту без справок','Что такое пролонгация займа и когда она выгодна',
        'Займы самозанятым: как оформить без 2-НДФЛ','Как закрыть микрозайм досрочно без переплаты',
        'Можно ли взять займ без работы','Займы с автоматическим одобрением: миф или реальность',
        'Какие документы нужны для получения займа','Займы без звонков работодателю',
        'Почему отказывают в займе и что делать','Займы в выходные и праздники',
        'Сколько займов можно взять одновременно','Займы без фото и СНИЛС',
        'Как рассчитать переплату по займу','Правила безопасного оформления займа онлайн',
    ]],
    ['category' => 'кредиты', 'themes' => [
        'Потребительский кредит vs кредитная карта: что выбрать','Как снизить процентную ставку по кредиту',
        'Досрочное погашение кредита: плюсы и минусы','Кредитная история: как проверить и улучшить',
        'Кредит под залог недвижимости: условия и риски','Автокредит или потребительский кредит на авто',
        'Кредитные каникулы: кому положены и как оформить','Страхование кредита: обязательно или нет',
        'Рефинансирование кредита: пошаговая инструкция','Кредит для самозанятых: особенности получения',
        'Как получить кредит с плохой кредитной историей',"Ипотека в {$currentYear} году: ставки и программы",
        'Образовательный кредит: условия и господдержка','Кредит на ремонт: где выгоднее',
        'Кредит для ИП: какие банки одобряют','Как выбрать банк для кредита: чек-лист',
        'Кредит без обеспечения: условия и ограничения','Что такое кредитный рейтинг и как его повысить',
        'Семейный кредит: программы и льготы','Кредит на покупку земельного участка',
    ]],
    ['category' => 'карты', 'themes' => [
        'Лучшие кредитные карты с кэшбеком','Как пользоваться льготным периодом без процентов',
        'Дебетовые карты с процентом на остаток','Виртуальные карты: безопасность онлайн-покупок',
        'Карты с бесплатным обслуживанием: сравнение','Премиальные карты: стоят ли они своих денег',
        'Как защитить банковскую карту от мошенников','Кэшбек vs бонусы: что выгоднее',
        'Карты для путешественников: лучшие варианты','Детская банковская карта: с какого возраста',
        'Карты с доставкой на дом: обзор предложений','Как правильно закрыть кредитную карту',
        'Рассрочка по карте vs потребительский кредит','Мультивалютные карты: когда они нужны',
        'Цифровые карты: плюсы и минусы','Карты с повышенным кэшбеком на АЗС',
    ]],
];

$db = getDB();

// МФО — офферы из БД
$offers = $db->query("SELECT id, title FROM offers WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
$mfoThemes = [];
foreach ($offers as $o) $mfoThemes[] = $o['title'];
if ($mfoThemes) $baseTopics[] = ['category' => 'мфо', 'themes' => $mfoThemes];

// Категория БАНКИ — офферы credits/credit_cards/debit_cards + крупные банки
$bankOffers = $db->query("SELECT title FROM offers WHERE is_active = 1 AND category IN ('credits','credit_cards','debit_cards') ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_COLUMN);
$bankThemes = array_merge($bankOffers, [
    'Сбербанк','Тинькофф Банк','Альфа-Банк','ВТБ','Газпромбанк',
    'Совкомбанк','Россельхозбанк','Промсвязьбанк','Райффайзен Банк','Открытие',
    'Почта Банк','Хоум Кредит Банк','МТС Банк','Ренессанс Кредит','Банк Уралсиб',
    'Росбанк','Банк Санкт-Петербург','Ак Барс Банк','Синара Банк','Банк ДОМ.РФ',
]);
$bankThemes = array_unique($bankThemes);
$baseTopics[] = ['category' => 'банки', 'themes' => $bankThemes];

// Загружаем темы сгенерированные ранее через AI
$generatedFile = __DIR__ . '/../../data/generated-topics.json';
$generatedTopics = file_exists($generatedFile) ? json_decode(file_get_contents($generatedFile), true) : [];
if ($generatedTopics) {
    foreach ($baseTopics as &$group) {
        if (isset($generatedTopics[$group['category']])) {
            $group['themes'] = array_merge($group['themes'], $generatedTopics[$group['category']]);
            $group['themes'] = array_unique($group['themes']);
        }
    }
    unset($group);
}

// Фильтруем использованные
$existingTitles = $db->query("SELECT LOWER(title) as t FROM articles")->fetchAll(PDO::FETCH_COLUMN);

// Нормализация для сравнения — убираем пунктуацию и лишние пробелы
function normalizeForCompare(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{N}\s]/u', '', $s);
    return preg_replace('/\s+/', ' ', trim($s));
}

function isTopicDuplicate(string $theme, array $existingTitles): bool {
    $normTheme = normalizeForCompare($theme);
    if ($normTheme === '') return false;
    
    foreach ($existingTitles as $existing) {
        $normExisting = normalizeForCompare($existing);
        if ($normExisting === '') continue;
        
        // Точное совпадение после нормализации
        if ($normExisting === $normTheme) return true;
        
        // Один содержит другой
        if (str_contains((string)($normExisting), $normTheme) || str_contains((string)($normTheme), $normExisting)) return true;
        
        // Похожесть > 70% (ловит "Займы без отказа" vs "Займ без отказа на карту")
        $maxLen = max(mb_strlen($normExisting), mb_strlen($normTheme));
        if ($maxLen > 0) {
            similar_text($normExisting, $normTheme, $percent);
            if ($percent > 70) return true;
        }
        
        // Совпадение по ключевым словам (>60% слов совпадают)
        $wordsTheme = array_filter(explode(' ', $normTheme), fn($w) => mb_strlen($w) > 2);
        $wordsExisting = array_filter(explode(' ', $normExisting), fn($w) => mb_strlen($w) > 2);
        if (count($wordsTheme) >= 3 && count($wordsExisting) >= 3) {
            $common = count(array_intersect($wordsTheme, $wordsExisting));
            $ratio = $common / min(count($wordsTheme), count($wordsExisting));
            if ($ratio > 0.6) return true;
        }
    }
    return false;
}

function fallbackTopicsByCategory(string $category): array {
    $map = [
        'займы' => [
            "Как выбрать займ онлайн в {$currentYear} году",
            'На что обратить внимание перед оформлением микрозайма',
            'Как снизить риски при выборе МФО',
            'Первый займ под 0%: как это работает',
            'Как сравнивать условия онлайн-займов',
        ],
        'кредиты' => [
            'Как выбрать выгодный потребительский кредит',
            'Что важно знать перед подачей заявки на кредит',
            'Как сравнить условия банковских кредитов',
            'Как снизить переплату по кредиту',
            'На что смотреть при выборе банка для кредита',
        ],
        'карты' => [
            'Как выбрать банковскую карту под свои задачи',
            'Кэшбэк, бонусы и льготы: как сравнить карты',
            "На что смотреть при выборе карты в {$currentYear} году",
            'Как подобрать карту для повседневных расходов',
            'Как сравнить условия по банковским картам',
        ],
        'банки' => [
            'Как выбрать банк для повседневных финансовых задач',
            'На что обратить внимание при выборе банка',
            'Как сравнить банки по основным услугам',
            'Какие банковские продукты чаще всего выбирают клиенты',
            'Как оценивать надёжность банка',
        ],
        'мфо' => [
            'Как выбрать надёжную МФО',
            'На что смотреть при сравнении микрофинансовых организаций',
            'Как понять условия займа в МФО',
            'Чем отличаются МФО между собой',
            'Как выбрать МФО для первого займа',
        ],
    ];
    return $map[$category] ?? ['Как выбрать финансовый продукт онлайн'];
}


$topicsList = [];
foreach ($baseTopics as $group) {
    $available = [];
    foreach ($group['themes'] as $theme) {
        if (!isTopicDuplicate($theme, $existingTitles)) {
            $available[] = $theme;
        }
    }

    $topicsList[] = [
        'category' => $group['category'],
        'themes' => $available,
        'total' => count($group['themes']),
        'used' => count($group['themes']) - count($available),
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

// GET — список тем
if ($method === 'GET') {
    $provStatus = getAIProvidersStatus();
    $activeText = $provStatus['active']['text'] ?? null;
    $activeImage = $provStatus['active']['image'] ?? null;
    $textName = $activeText ? ($provStatus['text'][$activeText]['name'] ?? $activeText) : 'нет';
    $imageName = $activeImage ? ($provStatus['image'][$activeImage]['name'] ?? $activeImage) : 'нет';
    
    echo json_encode([
        'topics' => $topicsList,
        'aiStatus' => [
            'activeText' => $textName,
            'activeImage' => $imageName,
            'textProvider' => $activeText ?? '',
            'imageProvider' => $activeImage ?? '',
            // Legacy поля для совместимости
            'yandexGPT' => $activeText !== null,
            'articleImageProvider' => $imageName,
        ],
    ]);
    exit;
}

// POST
$data = json_decode(file_get_contents('php://input'), true);
$requestedTopic = $data['topic'] ?? '';
$requestedCategory = $data['category'] ?? '';

// === Генерация НОВЫХ тем через AI если все использованы ===
if ($data['action'] ?? '' === 'generate-topics') {
    $cat = $requestedCategory ?: 'займы';
    $newTopics = generateNewTopics($cat, $existingTitles);
    if ($newTopics) {
        // Сохраняем
        if (!isset($generatedTopics[$cat])) $generatedTopics[$cat] = [];
        $generatedTopics[$cat] = array_merge($generatedTopics[$cat], $newTopics);
        $generatedTopics[$cat] = array_unique($generatedTopics[$cat]);
        file_put_contents($generatedFile, json_encode($generatedTopics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'topics' => $newTopics]);
    } else {
        echo json_encode(['error' => 'Не удалось сгенерировать темы']);
    }
    exit;
}

// Выбор темы
if ($requestedTopic) {
    $selectedTopic = $requestedTopic;
    $selectedCategory = $requestedCategory ?: 'займы';
} elseif ($requestedCategory) {
    $catTopics = null;
    foreach ($topicsList as $tg) {
        if ($tg['category'] === $requestedCategory && !empty($tg['themes'])) {
            $catTopics = $tg['themes'];
            break;
        }
    }
    // Если темы кончились — генерируем новые на лету
    if (!$catTopics) {
        $newTopics = generateNewTopics($requestedCategory, $existingTitles);
        if ($newTopics) {
            if (!isset($generatedTopics[$requestedCategory])) $generatedTopics[$requestedCategory] = [];
            $generatedTopics[$requestedCategory] = array_merge($generatedTopics[$requestedCategory], $newTopics);
            file_put_contents($generatedFile, json_encode($generatedTopics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $catTopics = $newTopics;
        }
    }
    if (!$catTopics) {
        $catTopics = array_values(array_filter(fallbackTopicsByCategory($requestedCategory), fn($t) => !isTopicDuplicate($t, $existingTitles)));
        if (!$catTopics) {
            $catTopics = fallbackTopicsByCategory($requestedCategory);
        }
    }
    $selectedTopic = $catTopics[array_rand($catTopics)];
    $selectedCategory = $requestedCategory;
} else {
    $availableGroups = array_filter($topicsList, fn($g) => !empty($g['themes']));
    if (empty($availableGroups)) {
        // Генерируем для случайной категории
        $cats = ['займы', 'кредиты', 'карты'];
        $cat = $cats[array_rand($cats)];
        $newTopics = generateNewTopics($cat, $existingTitles);
        if ($newTopics) {
            $selectedTopic = $newTopics[array_rand($newTopics)];
            $selectedCategory = $cat;
        } else {
            $fallbackTopics = array_values(array_filter(fallbackTopicsByCategory($cat), fn($t) => !isTopicDuplicate($t, $existingTitles)));
            if (!$fallbackTopics) $fallbackTopics = fallbackTopicsByCategory($cat);
            $selectedTopic = $fallbackTopics[array_rand($fallbackTopics)];
            $selectedCategory = $cat;
        }
    } else {
        $group = $availableGroups[array_rand($availableGroups)];
        $selectedCategory = $group['category'];
        $selectedTopic = $group['themes'][array_rand($group['themes'])];
    }
}

$content = null;
$aiProvider = 'шаблон';
$coverImage = '';
$isMFO = ($selectedCategory === 'мфо');
$isBank = ($selectedCategory === 'банки');

if ($isBank) {
    $systemPrompt = "Ты — опытный финансовый журналист и банковский эксперт для сайта Космозайм (kosmozaim.ru).
Пиши развёрнутые обзорные статьи о конкретных банках минимум 1500 слов на русском.

Структура статьи:
1. Введение — что за банк, история, позиция на рынке
2. Общая информация — полное юридическое название, год основания, генеральная лицензия ЦБ РФ (номер), рейтинг надёжности
3. Юридический адрес головного офиса, телефон горячей линии, email
4. Основные продукты для физических лиц — вклады, кредиты, кредитные карты, дебетовые карты, ипотека
5. Условия кредитования — ставки, суммы, сроки, требования к заёмщикам
6. Кредитные и дебетовые карты — лучшие предложения, кэшбек, льготный период
7. Онлайн-банкинг и мобильное приложение — функционал, удобство
8. Сеть отделений и банкоматов — география присутствия
9. Преимущества и недостатки банка — честный обзор
10. Заключение — кому подойдёт этот банк

НЕ указывай ссылку на сайт банка. В конце упомяни сайт Космозайм для сравнения предложений.
Формат: HTML (используй <h2>, <h3>, <p>, <ul>, <li>, <strong>).
Без таблиц, без markdown, без звёздочек. Только HTML-теги.
Используй реальные данные если знаешь.";

    $userPrompt = "Напиши развёрнутую обзорную статью о банке \"$selectedTopic\". Минимум 1500 слов. Обязательно укажи: генеральную лицензию ЦБ, юридический адрес, телефон горячей линии, условия кредитования, лучшие карты, преимущества и недостатки. Без ссылки на сайт банка. Без таблиц, без markdown. Если упоминаешь год, используй только актуальный год: {$currentYear}.";

    // Промпт для картинки задаётся в generateArticleCoverImage(): 'нарисуй 16:9 [Заголовок статьи]' ;
} elseif ($isMFO) {
    $systemPrompt = "Ты — опытный финансовый журналист и эксперт по микрофинансовым организациям для сайта Космозайм (kosmozaim.ru).
Пиши развёрнутые обзорные статьи о конкретных МФО минимум 1500 слов на русском.

Структура статьи:
1. Заголовок и краткое введение — что за организация
2. Общая информация о компании — год основания, юридическое название, лицензия ЦБ РФ (номер из реестра), организационно-правовая форма (МФК или МКК)
3. Юридический адрес и контактные данные — адрес офиса, телефон горячей линии, email поддержки
4. Условия займов — суммы, сроки, ставки, ПСК, первый займ без процентов (если есть)
5. Как оформить займ — пошаговая инструкция
6. Требования к заёмщикам — возраст, документы, кредитная история
7. Способы получения и возврата денег — карта, электронный кошелёк, наличные
8. Преимущества и недостатки — честный обзор
9. Отзывы заёмщиков — общая тональность отзывов
10. Заключение — рекомендации, для кого подойдёт

НЕ указывай ссылку на сайт организации. В конце упомяни сайт Космозайм для сравнения предложений.
Формат: HTML (используй <h2>, <h3>, <p>, <ul>, <li>, <strong>).
Без таблиц, без markdown, без звёздочек. Только HTML-теги.";

    $userPrompt = "Напиши развёрнутую обзорную статью о микрофинансовой организации \"$selectedTopic\". Минимум 1500 слов. Обязательно укажи: лицензию ЦБ, юридический адрес, телефон горячей линии, условия займов, пошаговую инструкцию оформления, преимущества и недостатки. Без ссылки на сайт организации. Если упоминаешь год, используй только актуальный год: {$currentYear}.";
    // Промпт для картинки задаётся в generateArticleCoverImage(): 'нарисуй 16:9 [Заголовок статьи]' ;
} else {
    // Шаг 1: AI генерирует план статьи
    $planPrompt = "Предложи план статьи на тему \"$selectedTopic\" для финансового сайта. 5-7 пунктов. Ответь только JSON массивом строк, без пояснений: [\"пункт 1\", \"пункт 2\", ...]";
    $planResult = aiGenerateText($planPrompt, "Ты SEO-редактор финансового сайта. Отвечай только валидным JSON.");
    $outline = [];
    if (!empty($planResult['success']) && !empty($planResult['text'])) {
        $planText = trim($planResult['text']);
        $planText = preg_replace('/^```\s*json?\s*/i', '', $planText);
        $planText = preg_replace('/\s*```$/i', '', $planText);
        $parsed = json_decode($planText, true);
        if (is_array($parsed) && count($parsed) >= 3) {
            $outline = $parsed;
        }
    }

    // Шаг 2: AI пишет статью по плану
    $outlineText = '';
    if ($outline) {
        $outlineText = "\n\nПлан статьи:\n";
        foreach ($outline as $i => $point) {
            $outlineText .= ($i + 1) . ". " . $point . "\n";
        }
    }

    $systemPrompt = "Ты — опытный финансовый журналист для сайта Космозайм (kosmozaim.ru).
Пиши развёрнутые статьи минимум 1500 слов на русском.
Формат: HTML (используй <h2>, <h3>, <p>, <ul>, <li>, <strong>).
Добавляй факты и примеры. Упоминай законодательство РФ.
В конце упомяни сайт Космозайм для сравнения предложений.
Не используй markdown, звёздочки, решётки. Только HTML-теги.
Если нужен год, используй только актуальный год из промпта.";

    $userPrompt = "Напиши развёрнутую статью на тему \"$selectedTopic\".{$outlineText}
Минимум 1500 слов. Формат HTML. Практические советы для России. Актуальный год: {$currentYear}.";
    // Промпт для картинки задаётся в generateArticleCoverImage(): 'нарисуй 16:9 [Заголовок статьи]' ;
}

// Генерация текста через unified AI providers
$aiResult = aiGenerateText($userPrompt, $systemPrompt);
$aiProvider = 'fallback';
if ($aiResult['success']) {
    $content = $aiResult['text'];
    $aiProvider = ($aiResult['provider'] ?? 'unknown') . ' (' . ($aiResult['model'] ?? '') . ')';
}

if ($content) {
    // Убираем markdown обёртку если AI вернул ```html...```
    $content = preg_replace('/^```\s*html?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/i', '', $content);
    // Если AI вернул markdown вместо HTML — конвертируем заголовки
    $content = preg_replace('/^#{1,2}\s+(.+)$/m', '<h2>$1</h2>', $content);
    $content = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $content);
    $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
    // Если нет HTML-тегов вообще — оборачиваем абзацы
    if (!preg_match('/<(p|h[1-6]|ul|ol|li|div|strong)\b/i', $content)) {
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
        $content = '<p>' . implode('</p><p>', $paragraphs) . '</p>';
    }
    $content = trim($content);
}

if (!$content) {
    $content = $isBank
        ? "$selectedTopic — обзор банка\n\n$selectedTopic — один из банков России.\n\nНа сайте Космозайм сравните банковские продукты."
        : ($isMFO
        ? "$selectedTopic — обзор микрофинансовой организации\n\n$selectedTopic — МФО, предоставляющая займы онлайн.\n\nНа сайте Космозайм вы можете сравнить условия разных организаций."
        : "$selectedTopic\n\nОбращайте внимание на ставку и ПСК.\n\nНа сайте Космозайм сравните условия и выберите лучшее предложение.");
}

// Генерация картинки через unified AI providers
$imageResult = aiGenerateImage(buildArticleImagePrompt($selectedTopic));
$coverImage = $imageResult['path'] ?? '';
$imageProvider = ($imageResult['provider'] ?? '') . (isset($imageResult['model']) ? ' (' . $imageResult['model'] . ')' : '');
$imageRequestedProvider = '';
$imageFallback = !$imageResult['success'];
$imageError = $imageResult['error'] ?? null;

// Мета — генерируем через AI если возможно
$paragraphs = array_filter(explode("\n\n", $content));
$paragraphsList = array_values($paragraphs);

// Excerpt: первый содержательный абзац (не заголовок)
$excerpt = '';
foreach ($paragraphsList as $p) {
    $p = trim($p);
    if (mb_strlen($p) > 50 && !preg_match('/^[А-ЯA-Z\s\d:—–-]+$/u', $p)) {
        $excerpt = mb_substr($p, 0, 250);
        if (mb_strlen($p) > 250) $excerpt .= '...';
        break;
    }
}
if (!$excerpt) {
    $excerpt = mb_substr(strip_tags($content), 0, 250) . '...';
}

$slug = slugify($selectedTopic) . '-' . time();

// Meta title и description
if ($isBank) {
    $metaTitle = $selectedTopic . ' — обзор банка, кредиты, карты | ' . SITE_NAME;
    $metaDescription = 'Обзор банка ' . $selectedTopic . ': кредиты, карты, вклады, лицензия ЦБ, контакты.';
} elseif ($isMFO) {
    $metaTitle = $selectedTopic . ' — обзор, условия, отзывы | ' . SITE_NAME;
    $metaDescription = 'Обзор ' . $selectedTopic . ': условия займов, лицензия ЦБ, контакты, преимущества и недостатки.';
} else {
    $metaTitle = $selectedTopic . ' | ' . SITE_NAME;
    // Meta description из содержания
    $metaDescription = mb_substr(preg_replace('/\s+/', ' ', strip_tags($excerpt)), 0, 155);
    if (mb_strlen($metaDescription) < 50) {
        $metaDescription = $selectedTopic . '. ' . mb_substr(preg_replace('/\s+/', ' ', strip_tags($content)), 0, 140);
    }
}

// Если excerpt пустой — fallback
if (!trim($excerpt)) {
    $excerpt = mb_substr($metaDescription, 0, 200) . '...';
}

try {
    $db->prepare("INSERT INTO articles (title, slug, excerpt, content, meta_title, meta_description, cover_image, is_published) VALUES (?,?,?,?,?,?,?,0)")
       ->execute([$selectedTopic, $slug, $excerpt, $content, $metaTitle, $metaDescription, $coverImage]);
    $newArticle = $db->query("SELECT * FROM articles ORDER BY id DESC LIMIT 1")->fetch();
} catch (Exception $e) {
    echo json_encode(['error' => 'Не удалось сохранить статью: ' . $e->getMessage(), 'textError' => $aiResult['error'] ?? null, 'imageError' => $imageResult['error'] ?? null]);
    exit;
}

// Диагностика AI провайдеров
$aiDiag = getAIProvidersStatus();

echo json_encode([
    'success' => true,
    'article' => $newArticle,
    'aiProvider' => $aiProvider,
    'hasImage' => !empty($coverImage),
    'category' => $selectedCategory,
    'imageProvider' => $imageProvider,
    'imageFallback' => !$imageResult['success'],
    'imageError' => $imageResult['error'] ?? null,
    'textError' => $aiResult['error'] ?? null,
    'debug' => [
        'activeText' => $aiDiag['active']['text'],
        'activeImage' => $aiDiag['active']['image'],
        'textPriority' => $aiDiag['priority']['text'],
        'providers' => $aiDiag['text'],
    ],
]);

// ============================================================
// Функция генерации новых тем через YandexGPT
// ============================================================
function generateNewTopics(string $category, array $existingTitles): array {
    $catDescriptions = [
        'займы' => 'микрозаймы, МФО, займы онлайн, займы на карту в России',
        'кредиты' => 'банковские кредиты, ипотека, потребительские кредиты, автокредиты в России',
        'карты' => 'банковские карты, кредитные карты, дебетовые карты, кэшбек в России',
        'банки' => 'российские банки, обзоры банков, банковские продукты, вклады, кредиты, карты банков России',
    ];
    $catDesc = $catDescriptions[$category] ?? 'финансовые продукты в России';

    $existingList = implode("\n", array_slice($existingTitles, 0, 30));

    $systemPrompt = "Ты генератор тем для финансового блога. Предлагай уникальные, интересные темы для статей.";
    $userPrompt = "Придумай 10 новых уникальных тем для статей про $catDesc.\n\nЭти темы уже использованы, НЕ повторяй их:\n$existingList\n\nВыведи только список тем, по одной на строку, без нумерации, без пояснений.";

    $result = aiGenerateText($userPrompt, $systemPrompt);
    if (!$result['success'] || empty($result['text'])) {
        $fallback = array_values(array_filter(fallbackTopicsByCategory($category), fn($t) => !isTopicDuplicate($t, $existingTitles)));
        return array_slice($fallback, 0, 10);
    }

    $text = $result['text'];
    $lines = array_filter(array_map('trim', explode("\n", $text)));
    $topics = [];
    foreach ($lines as $line) {
        $line = preg_replace('/^\d+[\.\\)]\s*/', '', $line);
        $line = preg_replace('/^[-–—•]\s*/', '', $line);
        $line = trim($line, ' "«»');
        if (mb_strlen($line) > 10 && mb_strlen($line) < 150) {
            if (!isTopicDuplicate($line, $existingTitles)) $topics[] = $line;
        }
    }

    return array_slice($topics, 0, 10);
}

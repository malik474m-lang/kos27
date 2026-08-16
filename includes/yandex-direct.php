<?php
/**
 * Модуль Яндекс Директ
 * Генерация объявлений, UTM-меток, ключевых слов для эффективной рекламы
 */

/**
 * Генерация текста объявления для оффера
 */
function generateDirectAd(array $offer, string $template = 'default'): array {
    $title = $offer['title'] ?? 'Займ онлайн';
    $rate = $offer['rate'] ?? '0';
    $amountMax = formatMoney($offer['amount_max'] ?? 100000);
    $freeDays = (int)($offer['free_term_days'] ?? 0);
    $category = $offer['category'] ?? 'microloans';
    
    $templates = [
        'default' => [
            'title1' => mb_substr("{$title} — до {$amountMax}", 0, 56),
            'title2' => $freeDays > 0 ? "Первый займ 0% на {$freeDays} дней!" : "Ставка от {$rate}% в день",
            'text' => "Быстрое одобрение онлайн. Деньги на карту за 5 минут. Без справок и поручителей.",
        ],
        'urgent' => [
            'title1' => mb_substr("Срочно! {$title}", 0, 56),
            'title2' => "Деньги за 5 минут на карту",
            'text' => "Моментальное решение 24/7. До {$amountMax}. Без отказа для всех!",
        ],
        'free' => [
            'title1' => $freeDays > 0 ? "0% на {$freeDays} дней — {$title}" : mb_substr($title, 0, 56),
            'title2' => "Первый займ бесплатно!",
            'text' => "Без процентов для новых клиентов. Быстрое оформление онлайн. Деньги сразу на карту.",
        ],
        'trust' => [
            'title1' => mb_substr("{$title} — лицензия ЦБ РФ", 0, 56),
            'title2' => "Надёжно и безопасно",
            'text' => "Проверенная МФО в реестре Банка России. Прозрачные условия. До {$amountMax}.",
        ],
        'comparison' => [
            'title1' => "Сравните {$title} с другими",
            'title2' => "Лучшие условия на рынке",
            'text' => "Калькулятор займа. Сравнение ставок и условий. Выберите выгодное предложение!",
        ],
    ];
    
    // Шаблоны для разных категорий
    if ($category === 'credits') {
        $templates['default']['title2'] = "Ставка от {$rate}% годовых";
        $templates['default']['text'] = "Кредит наличными в надёжном банке. Быстрое решение. Выгодные условия.";
    } elseif ($category === 'credit_cards') {
        $templates['default']['title2'] = $freeDays > 0 ? "Грейс-период {$freeDays} дней" : "Кэшбэк и бонусы";
        $templates['default']['text'] = "Кредитная карта с выгодным лимитом. Бесплатное обслуживание. Кэшбэк на покупки.";
    } elseif ($category === 'debit_cards') {
        $templates['default']['title2'] = "Кэшбэк до 30%";
        $templates['default']['text'] = "Дебетовая карта с процентом на остаток. Бесплатные переводы. Выгодный кэшбэк.";
    }
    
    $ad = $templates[$template] ?? $templates['default'];
    
    // Быстрые ссылки
    $ad['sitelinks'] = [
        ['title' => 'Калькулятор', 'url' => '/calculator'],
        ['title' => 'Все предложения', 'url' => '/zajmy'],
        ['title' => 'Отзывы', 'url' => '/offer/' . ($offer['slug'] ?? '')],
        ['title' => 'Без отказа', 'url' => '/zajmy/type/bez-otkaza'],
    ];
    
    // Уточнения
    $ad['clarifications'] = ['Онлайн 24/7', 'Без справок', 'На карту', 'Быстро'];
    
    return $ad;
}

/**
 * Генерация UTM-меток
 */
function generateUTM(array $params): string {
    $defaults = [
        'utm_source' => 'yandex',
        'utm_medium' => 'cpc',
        'utm_campaign' => '{campaign_id}',
        'utm_content' => '{ad_id}',
        'utm_term' => '{keyword}',
    ];
    return http_build_query(array_merge($defaults, $params));
}

/**
 * Генерация полного URL с UTM
 */
function generateDirectUrl(string $path, array $utmParams = []): string {
    $baseUrl = SITE_URL . $path;
    $utm = generateUTM($utmParams);
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    return $baseUrl . $separator . $utm;
}

/**
 * Ключевые слова для финансовой тематики
 */
function getDirectKeywords(string $category = 'microloans'): array {
    $keywords = [
        'microloans' => [
            'high' => [
                'займ онлайн', 'займ на карту', 'микрозайм онлайн', 'быстрый займ',
                'займ без отказа', 'займ срочно', 'деньги в долг онлайн',
                'займ на карту срочно', 'микрокредит онлайн', 'займ без проверок',
            ],
            'medium' => [
                'займ первый бесплатно', 'займ 0 процентов', 'займ новым клиентам',
                'займ под 0', 'займ без процентов', 'мфо онлайн', 'взять займ',
                'оформить займ', 'получить займ', 'займ круглосуточно',
            ],
            'long_tail' => [
                'займ на карту без отказа онлайн', 'срочный займ на карту без проверок',
                'займ без звонков и проверок', 'микрозайм на карту мгновенно',
                'займ онлайн на карту сбербанка', 'займ с плохой кредитной историей',
                'займ безработным на карту', 'займ пенсионерам онлайн',
                'займ студентам без работы', 'займ 10000 на карту срочно',
            ],
        ],
        'credits' => [
            'high' => ['кредит наличными', 'потребительский кредит', 'взять кредит', 'кредит онлайн', 'кредит в банке'],
            'medium' => ['кредит без справок', 'кредит по паспорту', 'выгодный кредит', 'низкая ставка кредит', 'рефинансирование кредита'],
            'long_tail' => ['кредит наличными без справок и поручителей', 'потребительский кредит низкая ставка', 'кредит с плохой кредитной историей', 'кредит пенсионерам до 75 лет'],
        ],
        'credit_cards' => [
            'high' => ['кредитная карта', 'кредитка онлайн', 'карта с кредитным лимитом', 'оформить кредитную карту'],
            'medium' => ['кредитная карта без отказа', 'карта с грейс периодом', 'кредитка с кэшбэком', 'кредитная карта 0%'],
            'long_tail' => ['кредитная карта с бесплатным обслуживанием', 'кредитка с доставкой на дом', 'кредитная карта 100 дней без процентов'],
        ],
        'debit_cards' => [
            'high' => ['дебетовая карта', 'карта с кэшбэком', 'банковская карта онлайн'],
            'medium' => ['карта с процентом на остаток', 'дебетовая карта бесплатно', 'карта для переводов'],
            'long_tail' => ['дебетовая карта с кэшбэком на всё', 'карта с бесплатным обслуживанием навсегда'],
        ],
    ];
    return $keywords[$category] ?? $keywords['microloans'];
}

/**
 * Минус-слова для финансовой тематики
 */
function getDirectMinusWords(): array {
    return [
        // Негативные
        'мошенники', 'обман', 'развод', 'лохотрон', 'кидалово', 'отзывы негативные',
        'не платить', 'не возвращать', 'списать долг', 'банкротство',
        // Нерелевантные
        'работа', 'вакансии', 'устроиться', 'менеджер', 'оператор',
        'скачать', 'бесплатно скачать', 'торрент', 'crack',
        // Информационные
        'что такое', 'как работает', 'история', 'википедия', 'реферат', 'курсовая',
        'законодательство', 'закон', 'статья', 'гк рф',
        // Другие страны
        'украина', 'беларусь', 'казахстан', 'минск', 'киев', 'алматы',
    ];
}

/**
 * Экспорт объявлений в CSV для Директа
 */
function exportDirectAdsCSV(array $offers, string $campaignName = 'Займы'): string {
    $lines = [];
    $headers = ['Название группы', 'Фраза', 'Заголовок 1', 'Заголовок 2', 'Текст объявления', 'Ссылка', 'Отображаемая ссылка', 'БС 1', 'URL БС 1', 'БС 2', 'URL БС 2', 'Уточнение 1', 'Уточнение 2', 'Уточнение 3', 'Уточнение 4'];
    $lines[] = implode(';', $headers);
    
    foreach ($offers as $offer) {
        $ad = generateDirectAd($offer);
        $keywords = getDirectKeywords($offer['category'] ?? 'microloans');
        $slug = $offer['slug'] ?? '';
        
        foreach ($keywords['high'] as $keyword) {
            $utmParams = ['utm_source' => 'yandex', 'utm_medium' => 'cpc', 'utm_campaign' => slugify($campaignName), 'utm_content' => $slug, 'utm_term' => slugify($keyword)];
            $url = generateDirectUrl('/offer/' . $slug, $utmParams);
            
            $row = [$offer['title'], $keyword, $ad['title1'], $ad['title2'], $ad['text'], $url, 'kosmozaim.ru/' . $slug,
                    $ad['sitelinks'][0]['title'], SITE_URL . $ad['sitelinks'][0]['url'],
                    $ad['sitelinks'][1]['title'], SITE_URL . $ad['sitelinks'][1]['url'],
                    $ad['clarifications'][0], $ad['clarifications'][1], $ad['clarifications'][2], $ad['clarifications'][3]];
            
            $lines[] = implode(';', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row));
        }
    }
    return implode("\n", $lines);
}

/**
 * Анализ эффективности рекламного трафика
 */
function analyzeDirectTraffic(int $days = 30): array {
    $db = getDB();
    
    // Клики с utm_source = yandex
    $clicksStmt = $db->prepare("SELECT DATE(created_at) as date, utm_campaign, utm_content, utm_term, COUNT(*) as clicks FROM click_stats WHERE utm_source = 'yandex' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at), utm_campaign, utm_content, utm_term ORDER BY date DESC");
    $clicksStmt->execute([$days]);
    $clicks = $clicksStmt->fetchAll();
    
    // Конверсии
    $conversions = [];
    try {
        $convStmt = $db->prepare("SELECT DATE(p.created_at) as date, c.utm_campaign, c.utm_content, COUNT(*) as conversions, SUM(p.payout) as revenue FROM postback_log p JOIN click_stats c ON p.click_id = c.id WHERE c.utm_source = 'yandex' AND p.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(p.created_at), c.utm_campaign, c.utm_content");
        $convStmt->execute([$days]);
        $conversions = $convStmt->fetchAll();
    } catch (Exception $e) {}
    
    // Сводка
    $summaryStmt = $db->prepare("SELECT utm_campaign, COUNT(*) as total_clicks, COUNT(DISTINCT ip) as unique_visitors FROM click_stats WHERE utm_source = 'yandex' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY utm_campaign ORDER BY total_clicks DESC");
    $summaryStmt->execute([$days]);
    $summary = $summaryStmt->fetchAll();
    
    // Топ ключевых слов
    $keywordsStmt = $db->prepare("SELECT utm_term as keyword, COUNT(*) as clicks, COUNT(DISTINCT ip) as visitors FROM click_stats WHERE utm_source = 'yandex' AND utm_term IS NOT NULL AND utm_term != '' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY utm_term ORDER BY clicks DESC LIMIT 20");
    $keywordsStmt->execute([$days]);
    $topKeywords = $keywordsStmt->fetchAll();
    
    return ['clicks' => $clicks, 'conversions' => $conversions, 'summary' => $summary, 'top_keywords' => $topKeywords, 'period_days' => $days];
}

/**
 * Рекомендации по оптимизации
 */
function getDirectOptimizationTips(array $analytics): array {
    $tips = [];
    
    if (!empty($analytics['top_keywords'])) {
        $avgClicks = array_sum(array_column($analytics['top_keywords'], 'clicks')) / count($analytics['top_keywords']);
        foreach ($analytics['top_keywords'] as $kw) {
            if ($kw['clicks'] > $avgClicks * 2) {
                $tips[] = ['type' => 'success', 'title' => 'Эффективное ключевое слово', 'message' => "«{$kw['keyword']}» — {$kw['clicks']} кликов. Увеличьте ставку."];
            }
            if ($kw['clicks'] < 3) {
                $tips[] = ['type' => 'warning', 'title' => 'Низкая эффективность', 'message' => "«{$kw['keyword']}» — мало кликов. Проверьте релевантность."];
            }
        }
    }
    
    $tips[] = ['type' => 'info', 'title' => 'A/B тестирование', 'message' => 'Создайте варианты объявлений: срочность, выгода, доверие.'];
    $tips[] = ['type' => 'info', 'title' => 'Корректировки ставок', 'message' => 'Пик: 10-14, 18-22. Мобильные: +20%.'];
    
    return $tips;
}

/**
 * Генерация отчёта
 */
function generateDirectReport(int $days = 30): array {
    $analytics = analyzeDirectTraffic($days);
    $tips = getDirectOptimizationTips($analytics);
    
    $totalClicks = array_sum(array_column($analytics['summary'], 'total_clicks'));
    $totalConversions = array_sum(array_column($analytics['conversions'], 'conversions'));
    $totalRevenue = array_sum(array_column($analytics['conversions'], 'revenue'));
    $cr = $totalClicks > 0 ? round($totalConversions / $totalClicks * 100, 2) : 0;
    
    return [
        'period' => $days, 'total_clicks' => $totalClicks, 'total_conversions' => $totalConversions,
        'total_revenue' => $totalRevenue, 'conversion_rate' => $cr,
        'campaigns' => $analytics['summary'], 'top_keywords' => $analytics['top_keywords'], 'tips' => $tips,
    ];
}

<?php
/**
 * Подкатегории — дополнительные страницы с фильтрацией офферов
 */

function ensureSubcategoriesTable(): void {
    static $checked = false;
    if ($checked) return;
    try {
        getDB()->query("SELECT 1 FROM subcategories LIMIT 1");
        $checked = true;
    } catch (Exception $e) {
        $migration = __DIR__ . '/../database-subcategories-migration.sql';
        if (file_exists($migration)) {
            $sql = file_get_contents($migration);
            foreach (array_filter(explode(';', $sql)) as $stmt) {
                $stmt = trim($stmt);
                if ($stmt) getDB()->exec($stmt);
            }
        }
        $checked = true;
    }
}

function getSubcategoriesByCategory(string $category): array {
    ensureSubcategoriesTable();
    $stmt = getDB()->prepare("SELECT * FROM subcategories WHERE category = ? AND is_active = 1 ORDER BY sort_order ASC, title ASC");
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}

function getSubcategoryBySlug(string $slug, string $category): ?array {
    ensureSubcategoriesTable();
    $stmt = getDB()->prepare("SELECT * FROM subcategories WHERE slug = ? AND category = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug, $category]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getSubcategoryCitySeo(int $subcategoryId, string $citySlug): ?array {
    try {
        $stmt = getDB()->prepare("SELECT * FROM subcategory_city_seo WHERE subcategory_id = ? AND city_slug = ? LIMIT 1");
        $stmt->execute([$subcategoryId, $citySlug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Exception $e) { return null; }
}

function filterOffersBySubcategoryRules(array $offers, array $rules): array {
    return array_values(array_filter($offers, function($offer) use ($rules) {
        foreach ($rules as $key => $value) {
            switch ($key) {
                case 'term_max_days':
                    if ((int)($offer['term_max_days'] ?? 999) > (int)$value) return false;
                    break;
                case 'term_min_days_min':
                    if ((int)($offer['term_min_days'] ?? 0) < (int)$value) return false;
                    break;
                case 'term_max_days_min':
                    if ((int)($offer['term_max_days'] ?? 0) < (int)$value) return false;
                    break;
                case 'amount_max_min':
                    if ((int)($offer['amount_max'] ?? 0) < (int)$value) return false;
                    break;
                case 'amount_max_max':
                    if ((int)($offer['amount_max'] ?? 999999999) > (int)$value) return false;
                    break;
                case 'amount_min_max':
                    if ((int)($offer['amount_min'] ?? 0) > (int)$value) return false;
                    break;
                case 'free_term_days_min':
                    if ((int)($offer['free_term_days'] ?? 0) < (int)$value) return false;
                    break;
                case 'rate_max':
                    if ((float)($offer['rate'] ?? 999) > (float)$value) return false;
                    break;
                case 'borrower_category':
                    $bc = $offer['borrower_category'] ?? 'any';
                    if ($bc !== 'any' && $bc !== $value) return false;
                    break;
            }
        }
        return true;
    }));
}

function getSubcategoryBaseUrl(string $category): string {
    return match($category) {
        'microloans' => '/zajmy',
        'credits' => '/kredity',
        'credit_cards' => '/karty/kreditnye',
        'debit_cards' => '/karty/debetovye',
        default => '/zajmy',
    };
}

/**
 * Рендер блока с допзапросами
 * 
 * @param string $category Категория (microloans, credits, credit_cards, debit_cards)
 * @param string|null $citySlug Slug города для формирования URL с гео (опционально)
 * @param string|null $excludeSlug Slug допзапроса который нужно исключить (текущая страница)
 * @param string|null $blockTitle Заголовок блока (по умолчанию "Категория — популярные запросы")
 */
function renderSubcategoryLinks(string $category, ?string $citySlug = null, ?string $excludeSlug = null, ?string $blockTitle = null): string {
    $subcats = getSubcategoriesByCategory($category);
    if (!$subcats) return '';
    
    // Исключаем текущий допзапрос если указан
    if ($excludeSlug) {
        $subcats = array_filter($subcats, fn($sc) => $sc['slug'] !== $excludeSlug);
    }
    
    // Если после фильтрации ничего не осталось
    if (empty($subcats)) return '';
    
    $base = getSubcategoryBaseUrl($category);
    $catLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
    $label = $catLabels[$category] ?? 'Предложения';
    
    // Заголовок блока
    $title = $blockTitle ?? ($label . ' — популярные запросы');
    
    $html = '<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 mb-8">';
    $html .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">';
    $html .= '<h2 class="text-xl font-bold text-gray-900 mb-4">' . e($title) . '</h2>';
    $html .= '<div class="flex flex-wrap gap-2">';
    
    foreach ($subcats as $sc) {
        // Формируем URL: с городом или без
        if ($citySlug) {
            $url = $base . '/' . $citySlug . '/q/' . $sc['slug'];
        } else {
            $url = $base . '/q/' . $sc['slug'];
        }
        $icon = $sc['icon'] ?? '📋';
        $html .= '<a href="' . e($url) . '" class="inline-flex items-center gap-1.5 bg-gray-50 hover:bg-blue-50 border border-gray-200 hover:border-blue-300 text-gray-700 hover:text-blue-700 px-3 py-2 rounded-lg text-sm transition-colors">';
        $html .= '<span>' . $icon . '</span> ' . e($sc['title']);
        $html .= '</a>';
    }
    
    $html .= '</div></div></section>';
    return $html;
}

/**
 * Адаптирует SEO-текст допзапроса для конкретного города.
 * Если в subcategory_city_seo нет кастомного текста — берёт базовый и
 * вставляет упоминания города, чтобы контент был уникальным.
 *
 * @param string $baseSeoText  Базовый seo_text без города
 * @param string $baseDesc     Базовое описание без города
 * @param array  $cityData     Массив города (name, prep, slug)
 * @param array  $subcat       Данные подкатегории (title, category, ...)
 * @return array ['seo_text' => ..., 'description' => ..., 'h1' => ...]
 */
function adaptSubcatTextForCity(string $baseSeoText, string $baseDesc, array $cityData, array $subcat): array {
    $cityName = $cityData['name'] ?? '';
    $cityPrep = $cityData['prep'] ?? $cityName;
    $catLabels = ['microloans'=>'займы','credits'=>'кредиты','credit_cards'=>'кредитные карты','debit_cards'=>'дебетовые карты'];
    $catLabel = $catLabels[$subcat['category'] ?? ''] ?? 'финансовые продукты';
    $title = $subcat['title'] ?? '';

    // H1
    $h1 = ($subcat['h1'] ?: $title) . ' в ' . $cityPrep;

    // Описание — формируем уникальное с городом
    $desc = "Подборка предложений «{$title}» в {$cityPrep}. Сравните {$catLabel} от проверенных организаций и выберите лучшие условия онлайн.";

    // SEO-текст: адаптируем базовый
    $seoText = $baseSeoText;
    if ($seoText) {
        // Добавляем вводный абзац про город перед основным текстом
        $cityIntro = "<h3>{$title} в {$cityPrep}</h3>\n";
        $cityIntro .= "<p>Жители {$cityPrep} могут оформить {$catLabel} по запросу «{$title}» онлайн. ";
        $cityIntro .= "Ниже представлены актуальные предложения, доступные в {$cityPrep}. ";
        $cityIntro .= "Сравните условия, процентные ставки и сроки, чтобы выбрать подходящий вариант.</p>\n\n";

        // Добавляем заключительный абзац про город после основного текста
        $cityOutro = "\n<h3>Как оформить в {$cityPrep}</h3>\n";
        $cityOutro .= "<p>Для оформления заявки в {$cityPrep} достаточно выбрать подходящее предложение из списка выше, ";
        $cityOutro .= "перейти на сайт организации и заполнить онлайн-анкету. ";
        $cityOutro .= "Решение по заявке обычно принимается в течение нескольких минут.</p>";

        $seoText = $cityIntro . $seoText . $cityOutro;
    } else {
        // Если базового текста нет вообще — генерируем шаблонный
        $seoText = "<h3>{$title} в {$cityPrep}</h3>\n";
        $seoText .= "<p>В {$cityPrep} доступны {$catLabel} по запросу «{$title}». ";
        $seoText .= "На этой странице собраны актуальные предложения от проверенных организаций. ";
        $seoText .= "Сравните процентные ставки, суммы и сроки, чтобы подобрать оптимальный вариант.</p>\n";
        $seoText .= "<p>Для оформления заявки выберите предложение и перейдите на сайт организации. ";
        $seoText .= "Заполните онлайн-анкету — решение обычно принимается за несколько минут. ";
        $seoText .= "Обращайте внимание на полную стоимость кредита (ПСК) и внимательно изучайте договор перед подписанием.</p>";
    }

    return [
        'h1' => $h1,
        'description' => $desc,
        'seo_text' => $seoText,
    ];
}

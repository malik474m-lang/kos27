<?php
/**
 * A/B тестирование CTA по категориям
 */

function getDefaultCtaLabelByCategory(string $category): string {
    return match ($category) {
        'microloans' => 'Получить займ',
        'credits' => 'Оформить кредит',
        'credit_cards' => 'Оформить карту',
        'debit_cards' => 'Заказать карту',
        default => 'Оформить',
    };
}

function getDefaultCtaSecondaryLabelByCategory(string $category): string {
    return match ($category) {
        'microloans' => 'Оформить по этим условиям',
        'credits' => 'Подать заявку на кредит',
        'credit_cards' => 'Оформить карту по этим условиям',
        'debit_cards' => 'Заказать карту по этим условиям',
        default => 'Оформить по этим условиям',
    };
}


function normalizeCtaLabelByCategory(string $category, string $label): string {
    $label = trim($label);
    if ($label === '') {
        return getDefaultCtaLabelByCategory($category);
    }

    if ($category === 'debit_cards') {
        if (preg_match('/получ(ить|ите)\s+деньг/u', $label)
            || preg_match('/получ(ить|ите)\s+займ/u', $label)
            || preg_match('/займ/u', $label)
            || preg_match('/деньг/u', $label)
            || preg_match('/кредит/u', $label)) {
            return 'Заказать карту';
        }
        return $label;
    }

    if ($category === 'credit_cards') {
        if (preg_match('/получ(ить|ите)\s+деньг/u', $label)
            || preg_match('/деньг/u', $label)
            || preg_match('/займ/u', $label)
            || preg_match('/кредит/u', $label)) {
            return 'Оформить карту';
        }
        return $label;
    }

    if ($category === 'credits') {
        if (preg_match('/получ(ить|ите)\s+деньг/u', $label) || preg_match('/займ/u', $label)) {
            return 'Оформить кредит';
        }
        return $label;
    }

    return $label;
}

function getAbVariant(string $category = ''): ?array {
    static $variantsCache = [];
    $cacheKey = $category ?: 'all';
    if (array_key_exists($cacheKey, $variantsCache)) {
        return $variantsCache[$cacheKey] ?: null;
    }

    try {
        $db = getDB();

        // Ищем тест: сначала точный по категории, потом fallback на 'all'
        $test = null;
        if ($category) {
            // Точное совпадение по категории
            $testStmt = $db->prepare("SELECT id, category_scope FROM ab_tests WHERE is_active = 1 AND category_scope = ? ORDER BY id DESC LIMIT 1");
            $testStmt->execute([$category]);
            $test = $testStmt->fetch();
        }
        if (!$test) {
            // Fallback на 'all'
            $test = $db->query("SELECT id, category_scope FROM ab_tests WHERE is_active = 1 AND category_scope = 'all' ORDER BY id DESC LIMIT 1")->fetch();
        }
        if (!$test && !$category) {
            $test = $db->query("SELECT id, category_scope FROM ab_tests WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
        }

        if (!$test) { $variantsCache[$cacheKey] = false; return null; }

        // Для поисковых ботов — всегда контрольный (первый) вариант,
        // без куки и без записи показа в статистику A/B.
        if (function_exists('isSearchBot') && isSearchBot()) {
            $control = $all[0];
            $variantsCache[$cacheKey] = $control;
            return $control;
        }


        $variants = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY id ASC");
        $variants->execute([$test['id']]);
        $all = $variants->fetchAll();
        if (!$all) { $variantsCache[$cacheKey] = false; return null; }

        // Кука привязана к тесту + категории чтобы для разных категорий были разные варианты
        $cookieKey = 'ab_v_' . $test['id'] . ($category ? '_' . $category : '');
        if (isset($_COOKIE[$cookieKey])) {
            $vid = (int)$_COOKIE[$cookieKey];
            foreach ($all as $v) {
                if ((int)$v['id'] === $vid) {
                    $variantsCache[$cacheKey] = $v;
                    return $v;
                }
            }
        }

        $chosen = $all[array_rand($all)];
        if (!headers_sent()) {
            setcookie($cookieKey, $chosen['id'], time() + 86400 * 30, '/');
        }
        $_COOKIE[$cookieKey] = $chosen['id'];
        $db->prepare("UPDATE ab_variants SET impressions = impressions + 1 WHERE id = ?")->execute([$chosen['id']]);

        $variantsCache[$cacheKey] = $chosen;
        return $chosen;
    } catch (Exception $e) {
        $variantsCache[$cacheKey] = false;
        return null;
    }
}

function getAbVariantId(string $category = ''): ?int {
    $v = getAbVariant($category);
    return $v ? (int)$v['id'] : null;
}


function getCtaVariantData(string $category = ''): array {
    $abVar = getAbVariant($category);
    $label = normalizeCtaLabelByCategory($category, $abVar ? (string)($abVar['label'] ?? '') : getDefaultCtaLabelByCategory($category));
    $secondary = getDefaultCtaSecondaryLabelByCategory($category);
    $color = $abVar ? (string)($abVar['color'] ?? '#059669') : '#059669';
    $id = $abVar ? (int)($abVar['id'] ?? 0) : 0;
    return [
        'label' => $label,
        'secondary' => $secondary,
        'color' => $color,
        'id' => $id,
    ];
}

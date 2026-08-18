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
        if (preg_match('/получ(ить|ите)\s+деньг/u', $label) || preg_match('/займ/u', $label)) {
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

        if ($category) {
            $testStmt = $db->prepare("SELECT id, category_scope FROM ab_tests WHERE is_active = 1 AND category_scope IN (?, 'all') ORDER BY (category_scope = ?) DESC, id DESC LIMIT 1");
            $testStmt->execute([$category, $category]);
            $test = $testStmt->fetch();
        } else {
            $test = $db->query("SELECT id, category_scope FROM ab_tests WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
        }

        if (!$test) { $variantsCache[$cacheKey] = false; return null; }

        $variants = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY id ASC");
        $variants->execute([$test['id']]);
        $all = $variants->fetchAll();
        if (!$all) { $variantsCache[$cacheKey] = false; return null; }

        $cookieKey = 'ab_v_' . $test['id'];
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
        setcookie($cookieKey, $chosen['id'], time() + 86400 * 30, '/');
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

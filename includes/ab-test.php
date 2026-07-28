<?php
/**
 * A/B тестирование кнопки
 * Вариант выбирается один раз для сессии пользователя и сохраняется в куке
 */

function getAbVariant(): ?array {
    static $variant = null;
    if ($variant !== null) return $variant ?: null;

    try {
        $db = getDB();

        // Ищем активный тест
        $test = $db->query("SELECT id FROM ab_tests WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
        if (!$test) { $variant = false; return null; }

        // Все варианты теста
        $variants = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ?");
        $variants->execute([$test['id']]);
        $all = $variants->fetchAll();
        if (!$all) { $variant = false; return null; }

        // Проверяем куку
        $cookieKey = 'ab_v_' . $test['id'];
        if (isset($_COOKIE[$cookieKey])) {
            $vid = (int)$_COOKIE[$cookieKey];
            foreach ($all as $v) {
                if ((int)$v['id'] === $vid) { $variant = $v; return $v; }
            }
        }

        // Выбираем случайный вариант
        $chosen = $all[array_rand($all)];

        // Сохраняем в куку на 30 дней
        setcookie($cookieKey, $chosen['id'], time() + 86400 * 30, '/');
        $_COOKIE[$cookieKey] = $chosen['id'];

        // Считаем показ
        $db->prepare("UPDATE ab_variants SET impressions = impressions + 1 WHERE id = ?")->execute([$chosen['id']]);

        $variant = $chosen;
        return $chosen;
    } catch (Exception $e) {
        $variant = false;
        return null;
    }
}

function getAbVariantId(): ?int {
    $v = getAbVariant();
    return $v ? (int)$v['id'] : null;
}

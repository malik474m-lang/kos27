<?php
$db = getDB();

$categories = ['microloans', 'credits', 'credit_cards', 'debit_cards'];

$activeAll = $db->query("SELECT * FROM ab_tests WHERE is_active = 1 AND category_scope = 'all' ORDER BY id DESC LIMIT 1")->fetch();
if (!$activeAll) {
    echo json_encode(['success' => true, 'changed' => false, 'reason' => 'no_global_test']);
    exit;
}

$existingStmt = $db->query("SELECT category_scope, COUNT(*) as cnt FROM ab_tests WHERE is_active = 1 AND category_scope IN ('microloans','credits','credit_cards','debit_cards') GROUP BY category_scope");
$existing = [];
foreach ($existingStmt->fetchAll() as $row) {
    $existing[$row['category_scope']] = (int)$row['cnt'];
}

$missing = array_values(array_filter($categories, fn($c) => empty($existing[$c])));
if (!$missing) {
    echo json_encode(['success' => true, 'changed' => false, 'reason' => 'category_tests_exist']);
    exit;
}

$variantStmt = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY id ASC");
$variantStmt->execute([(int)$activeAll['id']]);
$allVariants = $variantStmt->fetchAll();
$colors = array_map(fn($v) => trim((string)($v['color'] ?? '#059669')) ?: '#059669', $allVariants);
if (!$colors) $colors = ['#059669', '#1a56db', '#7c3aed'];

$defaults = [
    'microloans' => ['Получить займ', 'Оформить займ', 'Оформить заявку'],
    'credits' => ['Оформить кредит', 'Получить кредит', 'Подать заявку'],
    'credit_cards' => ['Оформить карту', 'Получить карту', 'Оформить кредитку'],
    'debit_cards' => ['Заказать карту', 'Оформить карту', 'Выбрать карту'],
];
$names = [
    'microloans' => 'Тест кнопки — Займы',
    'credits' => 'Тест кнопки — Кредиты',
    'credit_cards' => 'Тест кнопки — Кредитные карты',
    'debit_cards' => 'Тест кнопки — Дебетовые карты',
];

try {
    $db->beginTransaction();
    foreach ($missing as $cat) {
        $db->prepare("INSERT INTO ab_tests (name, category_scope, is_active) VALUES (?, ?, 1)")
           ->execute([$names[$cat], $cat]);
        $newTestId = (int)$db->lastInsertId();
        $ins = $db->prepare("INSERT INTO ab_variants (test_id, label, color, impressions, clicks) VALUES (?, ?, ?, 0, 0)");
        foreach ($defaults[$cat] as $i => $label) {
            $color = $colors[$i] ?? ($colors[0] ?? '#059669');
            $ins->execute([$newTestId, $label, $color]);
        }
    }
    $db->prepare("UPDATE ab_tests SET is_active = 0 WHERE id = ?")->execute([(int)$activeAll['id']]);
    $db->commit();
    echo json_encode(['success' => true, 'changed' => true, 'created' => $missing, 'disabled_global_test_id' => (int)$activeAll['id']]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

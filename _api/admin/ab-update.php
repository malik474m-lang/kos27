<?php
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$db = getDB();

$name = trim((string)($data['name'] ?? 'Тест'));
$scope = trim((string)($data['categoryScope'] ?? 'all'));
$variants = $data['variants'] ?? [];

if (!in_array($scope, ['all', 'microloans', 'credits', 'credit_cards', 'debit_cards'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректная категория']);
    exit;
}

if (!is_array($variants) || count($variants) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Нужно минимум 2 варианта']);
    exit;
}

$normalizedVariants = [];
foreach ($variants as $v) {
    $label = trim((string)($v['label'] ?? ''));
    $color = trim((string)($v['color'] ?? '#059669'));
    if ($label === '') continue;
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#059669';
    $normalizedVariants[] = ['label' => $label, 'color' => $color];
}

if (count($normalizedVariants) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'После очистки осталось меньше 2 вариантов']);
    exit;
}

try {
    $db->beginTransaction();
    $db->prepare("UPDATE ab_tests SET name = ?, category_scope = ? WHERE id = ?")
       ->execute([$name, $scope, $itemId]);

    $db->prepare("DELETE FROM ab_variants WHERE test_id = ?")
       ->execute([$itemId]);

    $stmt = $db->prepare("INSERT INTO ab_variants (test_id, label, color, impressions, clicks) VALUES (?, ?, ?, 0, 0)");
    foreach ($normalizedVariants as $v) {
        $stmt->execute([$itemId, $v['label'], $v['color']]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'id' => $itemId]);
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

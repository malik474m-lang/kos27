<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$db->prepare("INSERT INTO ab_tests (name, is_active) VALUES (?, ?)")
   ->execute([trim($data['name'] ?? 'Тест'), $data['isActive'] ?? true ? 1 : 0]);
$testId = $db->lastInsertId();
if (!empty($data['variants']) && is_array($data['variants'])) {
    $stmt = $db->prepare("INSERT INTO ab_variants (test_id, label, color) VALUES (?, ?, ?)");
    foreach ($data['variants'] as $v) {
        $stmt->execute([$testId, trim($v['label'] ?? 'Оформить'), trim($v['color'] ?? '#059669')]);
    }
}
echo json_encode(['success' => true, 'id' => $testId]);

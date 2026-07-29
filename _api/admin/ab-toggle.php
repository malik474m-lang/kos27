<?php
$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
if (!empty($data['isActive'])) {
    $scopeStmt = $db->prepare("SELECT category_scope FROM ab_tests WHERE id = ? LIMIT 1");
    $scopeStmt->execute([$itemId]);
    $scope = $scopeStmt->fetchColumn() ?: 'all';
    if ($scope === 'all') {
        $db->exec("UPDATE ab_tests SET is_active = 0");
    } else {
        $stmt = $db->prepare("UPDATE ab_tests SET is_active = 0 WHERE category_scope = ?");
        $stmt->execute([$scope]);
    }
}
$db->prepare("UPDATE ab_tests SET is_active = ? WHERE id = ?")->execute([$data['isActive'] ? 1 : 0, $itemId]);
echo json_encode(['success' => true]);

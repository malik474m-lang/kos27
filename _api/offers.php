<?php
if (apiCacheStart('public_offers', 120)) exit;

$db = getDB();
$ids = $_GET['ids'] ?? '';
if ($ids) {
    $idList = array_filter(array_map('intval', explode(',', $ids)));
    if (!$idList) { apiCacheEnd([]); exit; }
    $placeholders = implode(',', array_fill(0, count($idList), '?'));
    $stmt = $db->prepare("SELECT * FROM offers WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($idList);
} else {
    $category = $_GET['category'] ?? '';
    if ($category) {
        $stmt = $db->prepare("SELECT * FROM offers WHERE is_active = 1 AND category = ? ORDER BY sort_order ASC");
        $stmt->execute([$category]);
    } else {
        $stmt = $db->query("SELECT * FROM offers WHERE is_active = 1 ORDER BY sort_order ASC");
    }
}
apiCacheEnd($stmt->fetchAll());

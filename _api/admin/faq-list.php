<?php
$db = getDB();
try { $db->query("SELECT 1 FROM offer_faqs LIMIT 1"); } catch (Exception $e) {
    echo json_encode([]); exit;
}
$offerId = $_GET['offer_id'] ?? null;
if ($offerId) {
    $stmt = $db->prepare("SELECT f.*, o.title as offer_title FROM offer_faqs f LEFT JOIN offers o ON f.offer_id = o.id WHERE f.offer_id = ? ORDER BY f.sort_order ASC");
    $stmt->execute([(int)$offerId]);
} else {
    $stmt = $db->query("SELECT f.*, o.title as offer_title FROM offer_faqs f LEFT JOIN offers o ON f.offer_id = o.id ORDER BY f.offer_id, f.sort_order ASC");
}
echo json_encode($stmt->fetchAll());

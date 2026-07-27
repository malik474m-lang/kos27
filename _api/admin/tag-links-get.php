<?php
// Получить связи: ?offerId=1 или ?tagId=1
$db = getDB();

if (isset($_GET['offerId'])) {
    $stmt = $db->prepare("SELECT tag_id FROM offer_tag_links WHERE offer_id = ?");
    $stmt->execute([(int)$_GET['offerId']]);
    echo json_encode(array_column($stmt->fetchAll(), 'tag_id'));
} elseif (isset($_GET['tagId'])) {
    $stmt = $db->prepare("SELECT offer_id FROM offer_tag_links WHERE tag_id = ?");
    $stmt->execute([(int)$_GET['tagId']]);
    echo json_encode(array_column($stmt->fetchAll(), 'offer_id'));
} else {
    // Все связи
    echo json_encode($db->query("SELECT * FROM offer_tag_links")->fetchAll());
}

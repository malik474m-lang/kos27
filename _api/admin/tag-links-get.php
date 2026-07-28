<?php
if (apiCacheStart('admin_taglinks', 30)) exit;

$db = getDB();
if (isset($_GET['offerId'])) {
    $stmt = $db->prepare("SELECT tag_id FROM offer_tag_links WHERE offer_id = ?");
    $stmt->execute([(int)$_GET['offerId']]);
    apiCacheEnd(array_column($stmt->fetchAll(), 'tag_id'));
} elseif (isset($_GET['tagId'])) {
    $stmt = $db->prepare("SELECT offer_id FROM offer_tag_links WHERE tag_id = ?");
    $stmt->execute([(int)$_GET['tagId']]);
    apiCacheEnd(array_column($stmt->fetchAll(), 'offer_id'));
} else {
    apiCacheEnd($db->query("SELECT * FROM offer_tag_links")->fetchAll());
}

<?php
$db = getDB();
$db->prepare("DELETE FROM city_seo_texts WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

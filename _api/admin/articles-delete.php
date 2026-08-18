<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
$db = getDB();
$db->prepare("DELETE FROM articles WHERE id = ?")->execute([$itemId]);
echo json_encode(['success' => true]);

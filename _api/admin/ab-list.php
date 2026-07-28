<?php
$db = getDB();
$tests = $db->query("SELECT * FROM ab_tests ORDER BY created_at DESC")->fetchAll();
foreach ($tests as &$t) {
    $vars = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY id ASC");
    $vars->execute([$t['id']]);
    $t['variants'] = $vars->fetchAll();
}
echo json_encode($tests);

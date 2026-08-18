<?php
if (apiCacheStart('admin_abtests', 30)) exit;
$db = getDB();
$tests = $db->query("SELECT * FROM ab_tests ORDER BY created_at DESC")->fetchAll();

// Клики по вариантам и категориям
$clickRows = $db->query("SELECT cs.ab_variant_id, o.category, COUNT(*) AS cnt FROM click_stats cs JOIN offers o ON o.id = cs.offer_id WHERE cs.ab_variant_id IS NOT NULL GROUP BY cs.ab_variant_id, o.category")->fetchAll();
$clickMap = [];
foreach ($clickRows as $r) {
    $vid = (int)($r['ab_variant_id'] ?? 0);
    $cat = (string)($r['category'] ?? 'unknown');
    if ($vid <= 0) continue;
    if (!isset($clickMap[$vid])) $clickMap[$vid] = [];
    $clickMap[$vid][$cat] = (int)($r['cnt'] ?? 0);
}

foreach ($tests as &$t) {
    $vars = $db->prepare("SELECT * FROM ab_variants WHERE test_id = ? ORDER BY id ASC");
    $vars->execute([$t['id']]);
    $variants = $vars->fetchAll();
    foreach ($variants as &$v) {
        $v['category_clicks'] = $clickMap[(int)$v['id']] ?? [];
    }
    unset($v);
    $t['variants'] = $variants;
}
unset($t);
apiCacheEnd($tests);

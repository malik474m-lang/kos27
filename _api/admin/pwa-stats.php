<?php
requireAdmin();
$db = getDB();
$period = (int)($_GET['period'] ?? 30);
$dateFrom = date('Y-m-d', strtotime("-{$period} days"));

try { $db->query("SELECT 1 FROM pwa_stats LIMIT 1"); } 
catch(Exception $e) { 
    echo json_encode(['message'=>'Таблица pwa_stats не создана. Откройте сайт с телефона для автосоздания.','total'=>['installs'=>0,'visits'=>0,'standalone_visits'=>0,'prompts_shown'=>0],'platforms'=>[],'devices'=>[],'browsers'=>[],'daily'=>[],'recent_installs'=>[]]);
    exit;
}

$t = $db->prepare("SELECT SUM(event_type='install') as installs, SUM(event_type='visit') as visits, SUM(event_type='visit' AND is_standalone=1) as standalone_visits, SUM(event_type='prompt_shown') as prompts_shown FROM pwa_stats WHERE created_at>=?")->execute([$dateFrom]) ? $db->prepare("SELECT SUM(event_type='install') as installs, SUM(event_type='visit') as visits, SUM(event_type='visit' AND is_standalone=1) as standalone_visits, SUM(event_type='prompt_shown') as prompts_shown FROM pwa_stats WHERE created_at>=?") : null;
$totals = $db->prepare("SELECT SUM(event_type='install') as installs, SUM(event_type='visit') as visits, SUM(event_type='visit' AND is_standalone=1) as standalone_visits, SUM(event_type='prompt_shown') as prompts_shown FROM pwa_stats WHERE created_at>=?");
$totals->execute([$dateFrom]); $total = $totals->fetch();

$plat = $db->prepare("SELECT platform, COUNT(*) as total, SUM(event_type='install') as installs, SUM(event_type='visit') as visits, SUM(is_standalone=1) as standalone FROM pwa_stats WHERE created_at>=? AND platform IS NOT NULL GROUP BY platform ORDER BY total DESC");
$plat->execute([$dateFrom]);

$devs = $db->prepare("SELECT device_model, platform, COUNT(*) as count, SUM(event_type='install') as installs FROM pwa_stats WHERE created_at>=? AND device_model IS NOT NULL AND device_model!='' GROUP BY device_model, platform ORDER BY count DESC LIMIT 15");
$devs->execute([$dateFrom]);

$brows = $db->prepare("SELECT browser, COUNT(*) as count, SUM(event_type='install') as installs FROM pwa_stats WHERE created_at>=? GROUP BY browser ORDER BY count DESC");
$brows->execute([$dateFrom]);

$daily = $db->prepare("SELECT DATE(created_at) as date, SUM(event_type='install') as installs, SUM(event_type='visit') as visits, SUM(event_type='visit' AND is_standalone=1) as standalone FROM pwa_stats WHERE created_at>=? GROUP BY DATE(created_at) ORDER BY date");
$daily->execute([$dateFrom]);

$recent = $db->prepare("SELECT platform,device_model,browser,created_at FROM pwa_stats WHERE event_type='install' ORDER BY created_at DESC LIMIT 15");
$recent->execute();

echo json_encode(['total'=>['installs'=>(int)($total['installs']??0),'visits'=>(int)($total['visits']??0),'standalone_visits'=>(int)($total['standalone_visits']??0),'prompts_shown'=>(int)($total['prompts_shown']??0)],'platforms'=>$plat->fetchAll(),'devices'=>$devs->fetchAll(),'browsers'=>$brows->fetchAll(),'daily'=>$daily->fetchAll(),'recent_installs'=>$recent->fetchAll()]);

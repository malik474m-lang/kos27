<?php
/**
 * Финансовая аналитика (v2 — без декартова произведения)
 * GET /api/admin/analytics?period=30
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    
    $period = max(1, min(365, (int)($_GET['period'] ?? 30)));
    $compareWith = $_GET['compare'] ?? '';
    
    // ========== 1. ДОХОД ПО ОФФЕРАМ ==========
    // Клики и конверсии считаем ОТДЕЛЬНО, потом объединяем
    $byOffer = $db->prepare("
        SELECT 
            o.id, o.title, o.category, o.logo_url,
            COALESCE(cs.clicks, 0) as clicks,
            COALESCE(pc.approved, 0) as approved,
            COALESCE(pc.rejected, 0) as rejected,
            COALESCE(pc.pending, 0) as pending,
            COALESCE(pc.revenue, 0) as revenue,
            CASE WHEN COALESCE(cs.clicks, 0) > 0 
                THEN ROUND(COALESCE(pc.revenue, 0) / cs.clicks, 2) ELSE 0 END as epc,
            CASE WHEN (COALESCE(pc.approved, 0) + COALESCE(pc.rejected, 0)) > 0
                THEN ROUND(pc.approved * 100.0 / (pc.approved + pc.rejected), 1) ELSE 0 END as approval_rate
        FROM offers o
        LEFT JOIN (
            SELECT offer_id, COUNT(*) as clicks
            FROM click_stats
            WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY offer_id
        ) cs ON cs.offer_id = o.id
        LEFT JOIN (
            SELECT offer_id,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(status = 'pending') as pending,
                SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) as revenue
            FROM postback_conversions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY offer_id
        ) pc ON pc.offer_id = o.id
        WHERE o.is_active = 1
        HAVING clicks > 0 OR approved > 0
        ORDER BY revenue DESC
        LIMIT 50
    ");
    $byOffer->execute();
    $offerStats = $byOffer->fetchAll();
    
    // ========== 2. ДОХОД ПО КАТЕГОРИЯМ ==========
    $byCategory = $db->prepare("
        SELECT 
            o.category,
            COALESCE(SUM(cs.clicks), 0) as clicks,
            COALESCE(SUM(pc.approved), 0) as approved,
            COALESCE(SUM(pc.rejected), 0) as rejected,
            COALESCE(SUM(pc.revenue), 0) as revenue,
            CASE WHEN COALESCE(SUM(cs.clicks), 0) > 0
                THEN ROUND(SUM(COALESCE(pc.revenue, 0)) / SUM(cs.clicks), 2) ELSE 0 END as epc,
            CASE WHEN (COALESCE(SUM(pc.approved), 0) + COALESCE(SUM(pc.rejected), 0)) > 0
                THEN ROUND(SUM(pc.approved) * 100.0 / (SUM(pc.approved) + SUM(pc.rejected)), 1) ELSE 0 END as approval_rate
        FROM offers o
        LEFT JOIN (
            SELECT offer_id, COUNT(*) as clicks
            FROM click_stats
            WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY offer_id
        ) cs ON cs.offer_id = o.id
        LEFT JOIN (
            SELECT offer_id,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) as revenue
            FROM postback_conversions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY offer_id
        ) pc ON pc.offer_id = o.id
        GROUP BY o.category
        ORDER BY revenue DESC
    ");
    $byCategory->execute();
    $categoryStats = $byCategory->fetchAll();
    
    $categoryLabels = ['microloans'=>'Займы','credits'=>'Кредиты','credit_cards'=>'Кредитные карты','debit_cards'=>'Дебетовые карты'];
    foreach ($categoryStats as &$row) {
        $row['category_label'] = $categoryLabels[$row['category']] ?? $row['category'];
    }
    unset($row);
    
    // ========== 3. ДОХОД ПО ПАРТНЁРКАМ ==========
    $byPartner = [];
    try {
        $byPartnerStmt = $db->prepare("
            SELECT 
                COALESCE(pp.name, pc.profile_slug, 'Без профиля') as partner_name,
                pc.profile_slug as partner_slug,
                COUNT(*) as conversions,
                SUM(pc.status = 'approved') as approved,
                SUM(pc.status = 'rejected') as rejected,
                SUM(pc.status = 'pending') as pending,
                SUM(CASE WHEN pc.status = 'approved' THEN pc.payout ELSE 0 END) as revenue,
                CASE WHEN (SUM(pc.status = 'approved') + SUM(pc.status = 'rejected')) > 0
                    THEN ROUND(SUM(pc.status = 'approved') * 100.0 / (SUM(pc.status = 'approved') + SUM(pc.status = 'rejected')), 1)
                    ELSE 0 END as approval_rate
            FROM postback_conversions pc
            LEFT JOIN postback_profiles pp ON pc.profile_slug = pp.slug
            WHERE pc.created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY pc.profile_slug
            ORDER BY revenue DESC
        ");
        $byPartnerStmt->execute();
        $byPartner = $byPartnerStmt->fetchAll();
    } catch (Exception $e) {}
    
    // ========== 4. EPC ПО ИСТОЧНИКАМ ==========
    $bySource = $db->prepare("
        SELECT 
            COALESCE(NULLIF(c.utm_source, ''), 'direct') as source,
            COUNT(*) as clicks,
            COALESCE(pc.approved, 0) as approved,
            COALESCE(pc.revenue, 0) as revenue,
            CASE WHEN COUNT(*) > 0 
                THEN ROUND(COALESCE(pc.revenue, 0) / COUNT(*), 2)
                ELSE 0 END as epc
        FROM click_stats c
        LEFT JOIN (
            SELECT click_id,
                SUM(status = 'approved') as approved,
                SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) as revenue
            FROM postback_conversions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY click_id
        ) pc ON pc.click_id = c.id
        WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        GROUP BY COALESCE(NULLIF(c.utm_source, ''), 'direct')
        HAVING clicks >= 3
        ORDER BY revenue DESC
        LIMIT 20
    ");
    $bySource->execute();
    $sourceStats = $bySource->fetchAll();
    
    // ========== 5. ДИНАМИКА ПО ДНЯМ ==========
    $timeline = $db->prepare("
        SELECT 
            d.date,
            COALESCE(cs.clicks, 0) as clicks,
            COALESCE(pc.approved, 0) as approved,
            COALESCE(pc.rejected, 0) as rejected,
            COALESCE(pc.revenue, 0) as revenue
        FROM (
            SELECT DATE_SUB(CURDATE(), INTERVAL n DAY) as date
            FROM (
                SELECT a.N + b.N * 10 + c.N * 100 AS n
                FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                     (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b,
                     (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3) c
            ) numbers
            WHERE n < $period
        ) d
        LEFT JOIN (
            SELECT DATE(clicked_at) as dt, COUNT(*) as clicks
            FROM click_stats
            WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY DATE(clicked_at)
        ) cs ON cs.dt = d.date
        LEFT JOIN (
            SELECT DATE(created_at) as dt,
                SUM(status = 'approved') as approved,
                SUM(status = 'rejected') as rejected,
                SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) as revenue
            FROM postback_conversions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
            GROUP BY DATE(created_at)
        ) pc ON pc.dt = d.date
        ORDER BY d.date ASC
    ");
    $timeline->execute();
    $timelineData = $timeline->fetchAll();
    
    // ========== 6. СРАВНЕНИЕ ==========
    $comparison = [];
    if ($compareWith === 'prev') {
        $period2 = $period * 2;
        
        $cur = $db->query("
            SELECT 
                (SELECT COUNT(*) FROM click_stats WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as clicks,
                (SELECT SUM(status = 'approved') FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as approved,
                (SELECT SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as revenue
        ")->fetch();
        
        $prev = $db->query("
            SELECT 
                (SELECT COUNT(*) FROM click_stats WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period2 DAY) AND clicked_at < DATE_SUB(NOW(), INTERVAL $period DAY)) as clicks,
                (SELECT SUM(status = 'approved') FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period2 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL $period DAY)) as approved,
                (SELECT SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period2 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL $period DAY)) as revenue
        ")->fetch();
        
        $cur['clicks'] = (int)($cur['clicks'] ?? 0);
        $cur['approved'] = (int)($cur['approved'] ?? 0);
        $cur['revenue'] = (float)($cur['revenue'] ?? 0);
        $prev['clicks'] = (int)($prev['clicks'] ?? 0);
        $prev['approved'] = (int)($prev['approved'] ?? 0);
        $prev['revenue'] = (float)($prev['revenue'] ?? 0);
        
        $comparison = [
            'current' => $cur,
            'previous' => $prev,
            'changes' => [
                'clicks' => $prev['clicks'] > 0 ? round(($cur['clicks'] - $prev['clicks']) / $prev['clicks'] * 100, 1) : 0,
                'approved' => $prev['approved'] > 0 ? round(($cur['approved'] - $prev['approved']) / $prev['approved'] * 100, 1) : 0,
                'revenue' => $prev['revenue'] > 0 ? round(($cur['revenue'] - $prev['revenue']) / $prev['revenue'] * 100, 1) : 0,
            ]
        ];
    }
    
    // ========== 7. ИТОГО ==========
    $totals = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM click_stats WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as total_clicks,
            (SELECT SUM(status = 'approved') FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as total_approved,
            (SELECT SUM(status = 'rejected') FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as total_rejected,
            (SELECT SUM(status = 'pending') FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as total_pending,
            (SELECT SUM(CASE WHEN status = 'approved' THEN payout ELSE 0 END) FROM postback_conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)) as total_revenue
    ")->fetch();
    
    $totals['total_clicks'] = (int)($totals['total_clicks'] ?? 0);
    $totals['total_approved'] = (int)($totals['total_approved'] ?? 0);
    $totals['total_rejected'] = (int)($totals['total_rejected'] ?? 0);
    $totals['total_pending'] = (int)($totals['total_pending'] ?? 0);
    $totals['total_revenue'] = (float)($totals['total_revenue'] ?? 0);
    $totals['avg_epc'] = $totals['total_clicks'] > 0 
        ? round($totals['total_revenue'] / $totals['total_clicks'], 2) : 0;
    
    echo json_encode([
        'period' => $period,
        'totals' => $totals,
        'by_offer' => $offerStats,
        'by_category' => $categoryStats,
        'by_partner' => $byPartner,
        'by_source' => $sourceStats,
        'timeline' => $timelineData,
        'comparison' => $comparison
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

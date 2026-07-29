<?php
/**
 * Финансовая аналитика
 * GET /api/admin/analytics?period=30&group=day
 */
header('Content-Type: application/json; charset=UTF-8');

try {
    $db = getDB();
    
    $period = max(1, min(365, (int)($_GET['period'] ?? 30)));
    $group = $_GET['group'] ?? 'day';
    $compareWith = $_GET['compare'] ?? '';
    
    $groupFormat = match($group) {
        'week' => '%Y-%u',
        'month' => '%Y-%m',
        default => '%Y-%m-%d'
    };
    
    // ========== 1. ДОХОД ПО ОФФЕРАМ ==========
    $byOffer = $db->prepare("
        SELECT 
            o.id, o.title, o.category, o.logo_url,
            COUNT(DISTINCT c.id) as clicks,
            COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
            COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END) as rejected,
            COUNT(DISTINCT CASE WHEN pc.status = 'pending' THEN pc.id END) as pending,
            COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue,
            CASE WHEN COUNT(DISTINCT c.id) > 0 
                THEN ROUND(COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) / COUNT(DISTINCT c.id), 2)
                ELSE 0 END as epc,
            CASE WHEN (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)) > 0
                THEN ROUND(COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) * 100.0 / 
                     (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)), 1)
                ELSE 0 END as approval_rate
        FROM offers o
        LEFT JOIN click_stats c ON c.offer_id = o.id AND c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        LEFT JOIN postback_conversions pc ON pc.offer_id = o.id AND pc.created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        WHERE o.is_active = 1
        GROUP BY o.id
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
            COUNT(DISTINCT c.id) as clicks,
            COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
            COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END) as rejected,
            COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue,
            CASE WHEN COUNT(DISTINCT c.id) > 0 
                THEN ROUND(COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) / COUNT(DISTINCT c.id), 2)
                ELSE 0 END as epc,
            CASE WHEN (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)) > 0
                THEN ROUND(COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) * 100.0 / 
                     (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)), 1)
                ELSE 0 END as approval_rate
        FROM offers o
        LEFT JOIN click_stats c ON c.offer_id = o.id AND c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        LEFT JOIN postback_conversions pc ON pc.offer_id = o.id AND pc.created_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
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
                pp.slug as partner_slug,
                COUNT(DISTINCT pc.id) as conversions,
                COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
                COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END) as rejected,
                COUNT(DISTINCT CASE WHEN pc.status = 'pending' THEN pc.id END) as pending,
                COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue,
                CASE WHEN (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)) > 0
                    THEN ROUND(COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) * 100.0 / 
                         (COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) + COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END)), 1)
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
            COUNT(DISTINCT c.id) as clicks,
            COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
            COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue,
            CASE WHEN COUNT(DISTINCT c.id) > 0 
                THEN ROUND(COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) / COUNT(DISTINCT c.id), 2)
                ELSE 0 END as epc
        FROM click_stats c
        LEFT JOIN postback_conversions pc ON pc.click_id = c.id
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
            DATE(c.clicked_at) as date,
            COUNT(DISTINCT c.id) as clicks,
            COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
            COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END) as rejected,
            COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue
        FROM click_stats c
        LEFT JOIN postback_conversions pc ON pc.click_id = c.id
        WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        GROUP BY DATE(c.clicked_at)
        ORDER BY date ASC
    ");
    $timeline->execute();
    $timelineData = $timeline->fetchAll();
    
    // ========== 6. СРАВНЕНИЕ С ПРЕДЫДУЩИМ ПЕРИОДОМ ==========
    $comparison = [];
    if ($compareWith === 'prev') {
        $currentStmt = $db->prepare("
            SELECT COUNT(DISTINCT c.id) as clicks,
                   COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
                   COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue
            FROM click_stats c
            LEFT JOIN postback_conversions pc ON pc.click_id = c.id
            WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
        ");
        $currentStmt->execute();
        $current = $currentStmt->fetch();
        
        $period2 = $period * 2;
        $prevStmt = $db->prepare("
            SELECT COUNT(DISTINCT c.id) as clicks,
                   COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as approved,
                   COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as revenue
            FROM click_stats c
            LEFT JOIN postback_conversions pc ON pc.click_id = c.id
            WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period2 DAY)
              AND c.clicked_at < DATE_SUB(NOW(), INTERVAL $period DAY)
        ");
        $prevStmt->execute();
        $prev = $prevStmt->fetch();
        
        $comparison = [
            'current' => $current,
            'previous' => $prev,
            'changes' => [
                'clicks' => $prev['clicks'] > 0 ? round(($current['clicks'] - $prev['clicks']) / $prev['clicks'] * 100, 1) : 0,
                'approved' => $prev['approved'] > 0 ? round(($current['approved'] - $prev['approved']) / $prev['approved'] * 100, 1) : 0,
                'revenue' => $prev['revenue'] > 0 ? round(($current['revenue'] - $prev['revenue']) / $prev['revenue'] * 100, 1) : 0,
            ]
        ];
    }
    
    // ========== 7. ИТОГО ==========
    $totals = $db->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_clicks,
            COUNT(DISTINCT CASE WHEN pc.status = 'approved' THEN pc.id END) as total_approved,
            COUNT(DISTINCT CASE WHEN pc.status = 'rejected' THEN pc.id END) as total_rejected,
            COUNT(DISTINCT CASE WHEN pc.status = 'pending' THEN pc.id END) as total_pending,
            COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) as total_revenue,
            CASE WHEN COUNT(DISTINCT c.id) > 0 
                THEN ROUND(COALESCE(SUM(CASE WHEN pc.status = 'approved' THEN pc.payout END), 0) / COUNT(DISTINCT c.id), 2)
                ELSE 0 END as avg_epc
        FROM click_stats c
        LEFT JOIN postback_conversions pc ON pc.click_id = c.id
        WHERE c.clicked_at >= DATE_SUB(NOW(), INTERVAL $period DAY)
    ");
    $totals->execute();
    $totalsData = $totals->fetch();
    
    echo json_encode([
        'period' => $period,
        'group' => $group,
        'totals' => $totalsData,
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

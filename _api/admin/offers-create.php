<?php
require_once __DIR__ . '/../../includes/audit-log.php';

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

$slug = slugify($data['title'] ?? 'offer') . '-' . time();

try {
    $db->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, term_min_days, term_max_days, psk, rate, rate_unit, free_term_days, logo_url, affiliate_url, borrower_category, description, seo_keywords, regions, is_active, sort_order, extra_fields, display_fields) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
    ->execute([
        $data['title'] ?? '', $slug, $data['category'] ?? 'microloans',
        $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
        $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
        $data['psk'] ?? '0', $data['rate'] ?? '0', $data['rateUnit'] ?? 'day', $data['freeTermDays'] ?? 0,
        $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
        $data['borrowerCategory'] ?? 'any', $data['description'] ?? '',
        $data['seoKeywords'] ?? '', $data['regions'] ?? '',
        $data['isActive'] ?? true, $data['sortOrder'] ?? 0,
        $data['extraFields'] ?? null,
        $data['displayFields'] ?? null,
    ]);
    $newId = $db->lastInsertId();
    
    // Аудит
    auditLog('create', 'offer', (int)$newId, $data['title'] ?? 'Новый оффер');
    
    echo json_encode(['success' => true, 'id' => $newId]);
} catch (Exception $e) {
    try {
        $db->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, term_min_days, term_max_days, psk, rate, free_term_days, logo_url, affiliate_url, borrower_category, description, seo_keywords, regions, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $data['title'] ?? '', $slug, $data['category'] ?? 'microloans',
            $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
            $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
            $data['psk'] ?? '0', $data['rate'] ?? '0', $data['freeTermDays'] ?? 0,
            $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
            $data['borrowerCategory'] ?? 'any', $data['description'] ?? '',
            $data['seoKeywords'] ?? '', $data['regions'] ?? '',
            $data['isActive'] ?? true, $data['sortOrder'] ?? 0,
        ]);
        $newId = $db->lastInsertId();
        
        // Аудит
        auditLog('create', 'offer', (int)$newId, $data['title'] ?? 'Новый оффер');
        
        echo json_encode(['success' => true, 'id' => $newId, 'warning' => 'Сохранено без новых полей. Выполните SQL-миграции.']);
    } catch (Exception $e2) {
        http_response_code(500);
        echo json_encode(['error' => $e2->getMessage()]);
    }
}

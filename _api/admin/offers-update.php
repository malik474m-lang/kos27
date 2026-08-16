<?php
require_once __DIR__ . '/../../includes/audit-log.php';

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();

// Проверяем наличие contact полей
$hasContactFields = false;
try {
    $db->query("SELECT phone FROM offers LIMIT 1");
    $hasContactFields = true;
} catch (Exception $e) {
    try {
        $db->exec("ALTER TABLE offers 
            ADD COLUMN phone VARCHAR(50) DEFAULT NULL AFTER affiliate_url,
            ADD COLUMN address VARCHAR(500) DEFAULT NULL AFTER phone,
            ADD COLUMN trademark VARCHAR(255) DEFAULT NULL AFTER address,
            ADD COLUMN license VARCHAR(255) DEFAULT NULL AFTER trademark");
        $hasContactFields = true;
    } catch (Exception $e2) {}
}

$oldStmt = $db->prepare("SELECT * FROM offers WHERE id = ?");
$oldStmt->execute([$itemId]);
$oldData = $oldStmt->fetch();

try {
    if ($hasContactFields) {
        $db->prepare("UPDATE offers SET title=?, category=?, amount_min=?, amount_max=?, term_min_days=?, term_max_days=?, psk=?, rate=?, rate_unit=?, free_term_days=?, logo_url=?, affiliate_url=?, phone=?, kosmobonus_enabled=?, kosmobonus_amount=?, kosmobonus_conditions=?, address=?, trademark=?, license=?, borrower_category=?, description=?, seo_keywords=?, regions=?, is_active=?, sort_order=?, extra_fields=?, display_fields=?, meta_title=?, meta_description=? WHERE id=?")
        ->execute([
            $data['title'] ?? '', $data['category'] ?? 'microloans',
            $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
            $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
            $data['psk'] ?? '0', $data['rate'] ?? '0', $data['rateUnit'] ?? 'day', $data['freeTermDays'] ?? 0,
            $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
            $data['phone'] ?? null, $data['kosmobonusEnabled'] ?? false, (int)($data['kosmobonusAmount'] ?? 0), $data['kosmobonusConditions'] ?? null, $data['address'] ?? null,
            $data['trademark'] ?? null, $data['license'] ?? null,
            $data['borrowerCategory'] ?? 'any', $data['description'] ?? '',
            $data['seoKeywords'] ?? '', $data['regions'] ?? '',
            $data['isActive'] ?? true, $data['sortOrder'] ?? 0,
            $data['extraFields'] ?? null, $data['displayFields'] ?? null,
            $data['metaTitle'] ?? null, $data['metaDescription'] ?? null,
            $itemId,
        ]);
    } else {
        $db->prepare("UPDATE offers SET title=?, category=?, amount_min=?, amount_max=?, term_min_days=?, term_max_days=?, psk=?, rate=?, rate_unit=?, free_term_days=?, logo_url=?, affiliate_url=?, borrower_category=?, description=?, seo_keywords=?, regions=?, is_active=?, sort_order=?, extra_fields=?, display_fields=?, meta_title=?, meta_description=? WHERE id=?")
        ->execute([
            $data['title'] ?? '', $data['category'] ?? 'microloans',
            $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
            $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
            $data['psk'] ?? '0', $data['rate'] ?? '0', $data['rateUnit'] ?? 'day', $data['freeTermDays'] ?? 0,
            $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
            $data['borrowerCategory'] ?? 'any', $data['description'] ?? '',
            $data['seoKeywords'] ?? '', $data['regions'] ?? '',
            $data['isActive'] ?? true, $data['sortOrder'] ?? 0,
            $data['extraFields'] ?? null, $data['displayFields'] ?? null,
            $data['metaTitle'] ?? null, $data['metaDescription'] ?? null,
            $itemId,
        ]);
    }
    
    $newData = [
        'title' => $data['title'] ?? '',
        'category' => $data['category'] ?? 'microloans',
        'is_active' => $data['isActive'] ?? true,
        'rate' => $data['rate'] ?? '0',
        'amount_max' => $data['amountMax'] ?? 100000,
    ];
    $changes = $oldData ? auditDiff($oldData, $newData, ['title', 'category', 'is_active', 'rate', 'amount_max']) : null;
    auditLog('update', 'offer', $itemId, $data['title'] ?? $oldData['title'] ?? '', $changes);
    
    try {
        require_once __DIR__ . '/../../includes/auto-indexing.php';
        $slugRow = $db->prepare('SELECT slug FROM offers WHERE id = ?');
        $slugRow->execute([$itemId]);
        $sl = $slugRow->fetchColumn();
        if ($sl) autoSubmitUrl('/offer/' . $sl);
    } catch (Exception $e) {}
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

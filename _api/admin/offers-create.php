<?php
require_once __DIR__ . "/../../includes/page-cache.php";
register_shutdown_function("pageCacheClear");
require_once __DIR__ . '/../../includes/audit-log.php';
require_once __DIR__ . '/../../includes/kosmobonus.php';

$data = json_decode(file_get_contents('php://input'), true);
$db = getDB();
$slug = slugify($data['title'] ?? 'offer') . '-' . time();

ensureKosmoBonusTables();

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

try {
    if ($hasContactFields) {
        $db->prepare("INSERT INTO offers (
            title, slug, category, amount_min, amount_max, term_min_days, term_max_days,
            psk, rate, rate_unit, free_term_days, logo_url, affiliate_url,
            phone, kosmobonus_enabled, kosmobonus_amount, kosmobonus_conditions,
            address, trademark, license,
            borrower_category, description, seo_keywords, regions,
            is_active, sort_order, extra_fields, display_fields
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $data['title'] ?? '', $slug, $data['category'] ?? 'microloans',
            $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
            $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
            $data['psk'] ?? '0', $data['rate'] ?? '0', $data['rateUnit'] ?? 'day', $data['freeTermDays'] ?? 0,
            $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
            $data['phone'] ?? null,
            !empty($data['kosmobonusEnabled']) ? 1 : 0,
            (int)($data['kosmobonusAmount'] ?? 0),
            $data['kosmobonusConditions'] ?? null,
            $data['address'] ?? null,
            $data['trademark'] ?? null,
            $data['license'] ?? null,
            $data['borrowerCategory'] ?? 'any',
            $data['description'] ?? '',
            $data['seoKeywords'] ?? '',
            $data['regions'] ?? '',
            !empty($data['isActive']) ? 1 : 0,
            $data['sortOrder'] ?? 0,
            $data['extraFields'] ?? null,
            $data['displayFields'] ?? null,
        ]);
    } else {
        $db->prepare("INSERT INTO offers (
            title, slug, category, amount_min, amount_max, term_min_days, term_max_days,
            psk, rate, rate_unit, free_term_days, logo_url, affiliate_url,
            borrower_category, description, seo_keywords, regions,
            is_active, sort_order, extra_fields, display_fields
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $data['title'] ?? '', $slug, $data['category'] ?? 'microloans',
            $data['amountMin'] ?? 1000, $data['amountMax'] ?? 100000,
            $data['termMinDays'] ?? 1, $data['termMaxDays'] ?? 365,
            $data['psk'] ?? '0', $data['rate'] ?? '0', $data['rateUnit'] ?? 'day', $data['freeTermDays'] ?? 0,
            $data['logoUrl'] ?? '', $data['affiliateUrl'] ?? '',
            $data['borrowerCategory'] ?? 'any', $data['description'] ?? '',
            $data['seoKeywords'] ?? '', $data['regions'] ?? '',
            !empty($data['isActive']) ? 1 : 0, $data['sortOrder'] ?? 0,
            $data['extraFields'] ?? null,
            $data['displayFields'] ?? null,
        ]);
    }

    $newId = $db->lastInsertId();
    auditLog('create', 'offer', (int)$newId, $data['title'] ?? 'Новый оффер');

    require_once __DIR__ . '/../../includes/auto-indexing.php';
    autoSubmitUrl('/offer/' . $slug);
    echo json_encode(['success' => true, 'id' => $newId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

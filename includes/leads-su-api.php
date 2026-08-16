<?php
/**
 * Интеграция с API leads.su
 * Base URL: https://api.leads.su/webmaster/
 */

define('LEADS_SU_API_URL', 'https://api.leads.su/webmaster/');

function getLeadsSuToken(): string {
    $settings = getSiteSettings();
    return trim((string)($settings['leads_su_api_token'] ?? ''));
}

function leadsSuRequest(string $action, array $params = []): array {
    $token = getLeadsSuToken();
    if ($token === '') return ['ok' => false, 'error' => 'API токен leads.su не настроен'];

    $params['token'] = $token;
    $url = LEADS_SU_API_URL . ltrim($action, '/') . '?' . http_build_query($params);

    $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => "Accept: application/json\r\n", 'timeout' => 30, 'ignore_errors' => true]]);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        $err = error_get_last();
        return ['ok' => false, 'error' => 'Ошибка запроса: ' . ($err['message'] ?? 'connection failed')];
    }

    $response = preg_replace('/"id":(\d+)/', '"id":"$1"', $response);
    $data = json_decode($response, true);

    if (!$data || !empty($data['error'])) return ['ok' => false, 'error' => $data['error']['message'] ?? 'Unknown API error'];
    if (($data['code'] ?? 0) !== 200) return ['ok' => false, 'error' => 'API code: ' . ($data['code'] ?? 'unknown')];

    return ['ok' => true, 'data' => $data['data'] ?? [], 'count' => $data['count'] ?? 0];
}

function leadsSuGetOffers(int $offerId = 0, int $platformId = 0): array {
    $params = ['geo' => 1, 'limit' => 500, 'offset' => 0];
    $action = $platformId > 0 ? 'offers/connectedPlatforms' : 'offers';
    if ($offerId > 0) $params['id'] = $offerId;
    if ($platformId > 0) $params['platform_id'] = $platformId;

    $allOffers = [];
    do {
        $result = leadsSuRequest($action, $params);
        if (!$result['ok']) return $result;
        if (is_array($result['data'])) $allOffers = array_merge($allOffers, $result['data']);
        $params['offset'] += $params['limit'];
    } while ($params['offset'] < ($result['count'] ?? 0));

    return ['ok' => true, 'offers' => $allOffers, 'count' => count($allOffers)];
}

function leadsSuGetCategories(): array { return leadsSuRequest('dictionary/categories'); }

function leadsSuGetPlatforms(): array {
    $result = leadsSuRequest('platforms');
    if (!$result['ok']) return $result;
    $platforms = [];
    if (is_array($result['data'])) foreach ($result['data'] as $p) if (($p['status'] ?? '') === 'active') $platforms[] = ['id' => (int)$p['id'], 'name' => $p['name'] ?? ''];
    return ['ok' => true, 'platforms' => $platforms];
}

function leadsSuGetOfferLink(int $offerId, int $platformId): string {
    return 'https://pxl.leads.su/aff_c?offer_id=' . $offerId . '&pltfm_id=' . $platformId;
}

function leadsSuMapCategory(array $offer): string {
    $combined = mb_strtolower(trim(($offer['name'] ?? '') . ' ' . ($offer['category'] ?? '') . ' ' . ($offer['vertical'] ?? '')));
    if (preg_match('/кредитн(ая|ые)\s+карт/u', $combined)) return 'credit_cards';
    if (preg_match('/дебетов(ая|ые)\s+карт/u', $combined)) return 'debit_cards';
    if (preg_match('/\b(кредит|кредиты|потребительский)\b/u', $combined)) return 'credits';
    return 'microloans';
}

function leadsSuImportOffer(array $apiOffer, int $platformId, bool $activate = false): array {
    $db = getDB();
    $name = trim((string)($apiOffer['name'] ?? 'Без названия'));
    $description = trim(strip_tags((string)($apiOffer['description'] ?? '')));
    $category = leadsSuMapCategory($apiOffer);
    $slug = slugify($name) . '-' . time();
    $affiliateUrl = leadsSuGetOfferLink((int)($apiOffer['id'] ?? 0), $platformId);
    $logo = $apiOffer['logo'] ?? $apiOffer['image'] ?? '';

    $existing = $db->prepare("SELECT id FROM offers WHERE title = ? LIMIT 1");
    $existing->execute([$name]);
    if ($existing->fetch()) return ['ok' => false, 'error' => 'Уже существует: ' . $name, 'skipped' => true];

    try {
        $db->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, term_min_days, term_max_days, psk, rate, rate_unit, free_term_days, logo_url, affiliate_url, borrower_category, description, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([$name, $slug, $category, 1000, 100000, 1, 365, '0', '0', 'day', 0, $logo, $affiliateUrl, 'any', mb_substr($description, 0, 500), $activate ? 1 : 0, 999]);
        return ['ok' => true, 'id' => $db->lastInsertId(), 'title' => $name, 'category' => $category];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

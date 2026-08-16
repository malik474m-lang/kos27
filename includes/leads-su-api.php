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
    return 'https://pxl.leads.su/aff_c?offer_id=' . $offerId . '&pltfm_id=' . $platformId . '&source=kosmozaim';
}

function leadsSuMapCategory(array $offer): string {
    $combined = mb_strtolower(trim(($offer['name'] ?? '') . ' ' . ($offer['category'] ?? '') . ' ' . ($offer['vertical'] ?? '')));
    if (preg_match('/кредитн(ая|ые)\s+карт/u', $combined)) return 'credit_cards';
    if (preg_match('/дебетов(ая|ые)\s+карт/u', $combined)) return 'debit_cards';
    if (preg_match('/\b(кредит|кредиты|потребительский)\b/u', $combined)) return 'credits';
    return 'microloans';
}

function leadsSuWalkScalars(mixed $value, array &$bucket): void {
    if (is_array($value)) {
        foreach ($value as $v) leadsSuWalkScalars($v, $bucket);
        return;
    }
    if (is_scalar($value)) {
        $str = trim((string)$value);
        if ($str !== '') $bucket[] = $str;
    }
}

function leadsSuCollectTexts(array $offer): array {
    $bucket = [];
    leadsSuWalkScalars($offer, $bucket);
    $bucket = array_values(array_unique($bucket));
    return array_slice($bucket, 0, 400);
}

function leadsSuFindValueByKeys(array $data, array $keys): mixed {
    foreach ($keys as $key) {
        if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
            return $data[$key];
        }
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            $found = leadsSuFindValueByKeys($value, $keys);
            if ($found !== null && $found !== '') return $found;
        }
    }
    return null;
}

function leadsSuExtractLogo(array $offer): string {
    $logo = leadsSuFindValueByKeys($offer, ['logo', 'image', 'image_url', 'logo_url', 'icon', 'preview', 'picture']);
    return is_string($logo) ? trim($logo) : '';
}

function leadsSuExtractDescription(array $offer): string {
    $desc = leadsSuFindValueByKeys($offer, ['description', 'short_description', 'about', 'text', 'conditions']);
    if (is_string($desc) && trim($desc) !== '') {
        return mb_substr(trim(strip_tags($desc)), 0, 500);
    }
    $texts = leadsSuCollectTexts($offer);
    $chunks = [];
    foreach ($texts as $text) {
        if (mb_strlen($text) >= 40 && !preg_match('/^https?:\/\//i', $text)) {
            $chunks[] = trim(strip_tags($text));
        }
        if (count($chunks) >= 2) break;
    }
    return mb_substr(trim(implode(' ', $chunks)), 0, 500);
}

function leadsSuParseMoneyString(string $raw): ?int {
    $raw = preg_replace('/[^\d]/u', '', $raw);
    if ($raw === '') return null;
    $num = (int)$raw;
    if ($num <= 0) return null;
    return $num;
}

function leadsSuExtractAmountRange(array $offer, string $category): array {
    $min = leadsSuFindValueByKeys($offer, ['amount_min', 'min_amount', 'loan_amount_min', 'sum_from', 'credit_from']);
    $max = leadsSuFindValueByKeys($offer, ['amount_max', 'max_amount', 'loan_amount_max', 'sum_to', 'credit_to']);
    $minVal = is_numeric($min) ? (int)$min : null;
    $maxVal = is_numeric($max) ? (int)$max : null;

    $text = mb_strtolower(implode(' ', leadsSuCollectTexts($offer)));
    if (!$maxVal && preg_match('/до\s*([\d\s]{3,})\s*(₽|руб|рублей)?/u', $text, $m)) {
        $maxVal = leadsSuParseMoneyString($m[1]);
    }
    if ((!$minVal || !$maxVal) && preg_match('/от\s*([\d\s]{3,})\s*до\s*([\d\s]{3,})\s*(₽|руб|рублей)?/u', $text, $m)) {
        $minVal = $minVal ?: leadsSuParseMoneyString($m[1]);
        $maxVal = $maxVal ?: leadsSuParseMoneyString($m[2]);
    }

    if (!$minVal || !$maxVal) {
        $defaults = match ($category) {
            'credits' => [10000, 500000],
            'credit_cards' => [10000, 300000],
            'debit_cards' => [0, 0],
            default => [1000, 100000],
        };
        $minVal = $minVal ?: $defaults[0];
        $maxVal = $maxVal ?: $defaults[1];
    }

    if ($maxVal < $minVal) [$minVal, $maxVal] = [$maxVal, $minVal];
    return [$minVal, $maxVal];
}

function leadsSuExtractTermRange(array $offer, string $category): array {
    $min = leadsSuFindValueByKeys($offer, ['term_min_days', 'min_term', 'days_min', 'period_min']);
    $max = leadsSuFindValueByKeys($offer, ['term_max_days', 'max_term', 'days_max', 'period_max']);
    $minVal = is_numeric($min) ? (int)$min : null;
    $maxVal = is_numeric($max) ? (int)$max : null;

    $text = mb_strtolower(implode(' ', leadsSuCollectTexts($offer)));
    if ((!$minVal || !$maxVal) && preg_match('/от\s*(\d{1,3})\s*до\s*(\d{1,3})\s*(дн|дней|дня|сут|суток)/u', $text, $m)) {
        $minVal = $minVal ?: (int)$m[1];
        $maxVal = $maxVal ?: (int)$m[2];
    }
    if (!$maxVal && preg_match('/до\s*(\d{1,3})\s*(дн|дней|дня|сут|суток)/u', $text, $m)) {
        $maxVal = (int)$m[1];
    }
    if (!$maxVal && preg_match('/до\s*(\d{1,2})\s*(мес|месяц|месяцев)/u', $text, $m)) {
        $maxVal = (int)$m[1] * 30;
    }

    if (!$minVal || !$maxVal) {
        $defaults = match ($category) {
            'credits' => [30, 365 * 5],
            'credit_cards' => [0, 0],
            'debit_cards' => [0, 0],
            default => [1, 365],
        };
        $minVal = $minVal ?? $defaults[0];
        $maxVal = $maxVal ?? $defaults[1];
    }

    if ($maxVal < $minVal) [$minVal, $maxVal] = [$maxVal, $minVal];
    return [$minVal, $maxVal];
}

function leadsSuExtractRateData(array $offer, string $category): array {
    $rate = leadsSuFindValueByKeys($offer, ['rate', 'percent', 'interest_rate', 'stavka']);
    $psk = leadsSuFindValueByKeys($offer, ['psk', 'apr', 'full_cost']);
    $rateVal = is_numeric($rate) ? (string)$rate : '0';
    $pskVal = is_numeric($psk) ? (string)$psk : '0';
    $rateUnit = $category === 'microloans' ? 'day' : 'year';
    $freeTermDays = 0;

    $text = mb_strtolower(implode(' ', leadsSuCollectTexts($offer)));
    if ($rateVal === '0' && preg_match('/(от\s*)?(\d+[\.,]?\d*)\s*%\s*(в\s*день|дневн)/u', $text, $m)) {
        $rateVal = str_replace(',', '.', $m[2]);
        $rateUnit = 'day';
    } elseif ($rateVal === '0' && preg_match('/(от\s*)?(\d+[\.,]?\d*)\s*%\s*(годовых|в\s*год|год)/u', $text, $m)) {
        $rateVal = str_replace(',', '.', $m[2]);
        $rateUnit = 'year';
    } elseif ($rateVal === '0' && preg_match('/(от\s*)?(\d+[\.,]?\d*)\s*%/u', $text, $m)) {
        $rateVal = str_replace(',', '.', $m[2]);
    }

    if ($pskVal === '0' && preg_match('/пск[^\d]{0,20}(\d+[\.,]?\d*)\s*%/u', $text, $m)) {
        $pskVal = str_replace(',', '.', $m[1]);
    }

    if (preg_match('/0\s*%\s*на\s*(\d{1,3})\s*(дн|дней|дня)/u', $text, $m)) {
        $freeTermDays = (int)$m[1];
    }

    return [$rateVal, $pskVal, $rateUnit, $freeTermDays];
}

function leadsSuCleanOfferName(string $name): string {
    $name = trim($name);
    if ($name === '') return 'Без названия';

    // Убираем все дополнения в квадратных скобках: [CPA], [RU], [Акция] и т.п.
    $name = preg_replace('/\s*\[[^\]]*\]/u', '', $name);

    // Убираем хвостовые разделители, которые могли остаться перед скобками.
    $name = preg_replace('/\s*[-–—|:,;]+\s*$/u', '', $name);

    // Нормализуем пробелы.
    $name = preg_replace('/\s{2,}/u', ' ', $name);
    $name = trim($name);

    return $name !== '' ? $name : 'Без названия';
}

function leadsSuNormalizeOffer(array $apiOffer, int $platformId, string $categoryOverride = ''): array {
    $name = leadsSuCleanOfferName((string)($apiOffer['name'] ?? 'Без названия'));
    $category = $categoryOverride !== '' ? $categoryOverride : leadsSuMapCategory($apiOffer);
    [$amountMin, $amountMax] = leadsSuExtractAmountRange($apiOffer, $category);
    [$termMin, $termMax] = leadsSuExtractTermRange($apiOffer, $category);
    [$rate, $psk, $rateUnit, $freeTermDays] = leadsSuExtractRateData($apiOffer, $category);

    return [
        'external_id' => (string)($apiOffer['id'] ?? ''),
        'title' => $name,
        'slug' => slugify($name) . '-' . time(),
        'category' => $category,
        'amount_min' => $amountMin,
        'amount_max' => $amountMax,
        'term_min_days' => $termMin,
        'term_max_days' => $termMax,
        'rate' => $rate,
        'psk' => $psk,
        'rate_unit' => $rateUnit,
        'free_term_days' => $freeTermDays,
        'logo_url' => leadsSuExtractLogo($apiOffer),
        'description' => leadsSuExtractDescription($apiOffer),
        'affiliate_url' => leadsSuGetOfferLink((int)($apiOffer['id'] ?? 0), $platformId),
        'borrower_category' => 'any',
    ];
}

function leadsSuImportOffer(array $apiOffer, int $platformId, bool $activate = false, string $categoryOverride = ''): array {
    $db = getDB();
    $prepared = leadsSuNormalizeOffer($apiOffer, $platformId, $categoryOverride);
    $name = $prepared['title'];

    $existing = $db->prepare("SELECT id FROM offers WHERE title = ? LIMIT 1");
    $existing->execute([$name]);
    if ($existing->fetch()) return ['ok' => false, 'error' => 'Уже существует: ' . $name, 'skipped' => true];

    try {
        $db->prepare("INSERT INTO offers (title, slug, category, amount_min, amount_max, term_min_days, term_max_days, psk, rate, rate_unit, free_term_days, logo_url, affiliate_url, borrower_category, description, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $prepared['title'],
            $prepared['slug'],
            $prepared['category'],
            $prepared['amount_min'],
            $prepared['amount_max'],
            $prepared['term_min_days'],
            $prepared['term_max_days'],
            $prepared['psk'],
            $prepared['rate'],
            $prepared['rate_unit'],
            $prepared['free_term_days'],
            $prepared['logo_url'],
            $prepared['affiliate_url'],
            $prepared['borrower_category'],
            mb_substr($prepared['description'], 0, 500),
            $activate ? 1 : 0,
            999
        ]);
        return ['ok' => true, 'id' => $db->lastInsertId(), 'title' => $name, 'category' => $prepared['category']];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

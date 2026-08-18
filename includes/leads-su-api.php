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


function leadsSuDetectLogoExtension(string $url, string $contentType = '', string $body = ''): string {
    $contentType = mb_strtolower(trim(explode(';', $contentType)[0] ?? ''));
    if ($contentType === 'image/svg+xml' || str_contains((string)$body, '<svg')) return 'svg';
    if ($contentType === 'image/png') return 'png';
    if ($contentType === 'image/webp') return 'webp';
    if ($contentType === 'image/gif') return 'gif';
    if ($contentType === 'image/jpeg' || $contentType === 'image/jpg') return 'jpg';

    $path = parse_url($url, PHP_URL_PATH) ?: '';
    $ext = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['svg','png','webp','gif','jpg','jpeg'], true)) {
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }
    return 'png';
}

function leadsSuDownloadLogo(string $url, string $offerTitle = ''): string {
    $url = trim($url);
    if ($url === '') return '';
    if (!preg_match('#^https?://#i', $url)) return $url;

    $dirFs = __DIR__ . '/../images/offer';
    $dirWeb = '/images/offer';
    if (!is_dir($dirFs)) @mkdir($dirFs, 0755, true);

    $baseName = slugify($offerTitle !== '' ? $offerTitle : ('logo-' . substr(md5($url), 0, 8)));
    if ($baseName === '') $baseName = 'offer-logo';

    $body = '';
    $contentType = '';
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Kosmozaim/1.0 Logo Importer',
            CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
        ]);
        $body = (string)curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 20, 'header' => "User-Agent: Kosmozaim/1.0 Logo Importer
Accept: image/*,*/*;q=0.8
"]]);
        $body = (string)@file_get_contents($url, false, $ctx);
        $headers = $http_response_header ?? [];
        foreach ($headers as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d+)#i', $h, $m)) $httpCode = (int)$m[1];
            if (stripos($h, 'Content-Type:') === 0) $contentType = trim(substr($h, 13));
        }
    }

    if ($httpCode < 200 || $httpCode >= 300 || $body === '') {
        return $url;
    }

    if (strlen($body) > 3 * 1024 * 1024) {
        return $url;
    }

    $ext = leadsSuDetectLogoExtension($url, $contentType, $body);
    $fileName = $baseName . '-' . substr(md5($url), 0, 8) . '.' . $ext;
    $fullPath = $dirFs . '/' . $fileName;

    if (!file_exists($fullPath)) {
        if (@file_put_contents($fullPath, $body) === false) {
            return $url;
        }
    }

    return $dirWeb . '/' . $fileName;
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
        'logo_url' => leadsSuDownloadLogo(leadsSuExtractLogo($apiOffer), $name),
        'description' => leadsSuExtractDescription($apiOffer),
        'affiliate_url' => leadsSuGetOfferLink((int)($apiOffer['id'] ?? 0), $platformId),
        'borrower_category' => 'any',
    ];
}

function leadsSuImportOffer(array $apiOffer, int $platformId, bool $activate = false, string $categoryOverride = '', bool $updateExisting = false): array {
    $db = getDB();
    $prepared = leadsSuNormalizeOffer($apiOffer, $platformId, $categoryOverride);
    $name = $prepared['title'];

    $existing = $db->prepare("SELECT id, slug, is_active, logo_url FROM offers WHERE title = ? LIMIT 1");
    $existing->execute([$name]);
    $row = $existing->fetch();

    if ($row && !$updateExisting) {
        return ['ok' => false, 'error' => 'Уже существует: ' . $name, 'skipped' => true];
    }

    try {
        if ($row) {
            $db->prepare("UPDATE offers SET category=?, amount_min=?, amount_max=?, term_min_days=?, term_max_days=?, psk=?, rate=?, rate_unit=?, free_term_days=?, logo_url=?, affiliate_url=?, borrower_category=?, description=?, is_active=? WHERE id = ?")
            ->execute([
                $prepared['category'],
                $prepared['amount_min'],
                $prepared['amount_max'],
                $prepared['term_min_days'],
                $prepared['term_max_days'],
                $prepared['psk'],
                $prepared['rate'],
                $prepared['rate_unit'],
                $prepared['free_term_days'],
                $prepared['logo_url'] ?: ($row['logo_url'] ?? ''),
                $prepared['affiliate_url'],
                $prepared['borrower_category'],
                mb_substr($prepared['description'], 0, 500),
                $activate ? 1 : (int)($row['is_active'] ?? 0),
                (int)$row['id'],
            ]);
            return ['ok' => true, 'id' => (int)$row['id'], 'title' => $name, 'category' => $prepared['category'], 'updated' => true];
        }

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
        return ['ok' => true, 'id' => $db->lastInsertId(), 'title' => $name, 'category' => $prepared['category'], 'updated' => false];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function leadsSuRefreshExistingLogos(?array $offerIds = null): array {
    $db = getDB();
    $sql = "SELECT id, title, logo_url FROM offers WHERE logo_url IS NOT NULL AND TRIM(logo_url) <> ''";
    $params = [];
    if ($offerIds) {
        $offerIds = array_values(array_filter(array_map('intval', $offerIds), fn($v) => $v > 0));
        if (!$offerIds) return ['ok' => true, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $placeholders = implode(',', array_fill(0, count($offerIds), '?'));
        $sql .= " AND id IN ($placeholders)";
        $params = $offerIds;
    }
    $rows = $params ? $db->prepare($sql) : null;
    if ($rows) {
        $rows->execute($params);
        $offers = $rows->fetchAll();
    } else {
        $offers = $db->query($sql)->fetchAll();
    }

    $updated = 0;
    $skipped = 0;
    $errors = [];
    foreach ($offers as $offer) {
        $current = trim((string)($offer['logo_url'] ?? ''));
        if ($current === '' || !preg_match('#^https?://#i', $current)) {
            $skipped++;
            continue;
        }
        $local = leadsSuDownloadLogo($current, (string)($offer['title'] ?? 'offer-' . $offer['id']));
        if ($local === '' || preg_match('#^https?://#i', $local)) {
            $errors[] = ($offer['title'] ?? ('ID ' . $offer['id'])) . ': не удалось скачать';
            continue;
        }
        $db->prepare("UPDATE offers SET logo_url = ? WHERE id = ?")->execute([$local, (int)$offer['id']]);
        $updated++;
    }
    return ['ok' => true, 'updated' => $updated, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 20)];
}

<?php
/**
 * Каноникализация дублей URL:
 * - единый домен из SITE_URL
 * - без завершающего слеша (кроме /)
 * - legacy URL карт -> актуальные /karty/...
 * - удаление tracking query params (utm, gclid, fbclid, и т.д.)
 * - сохранение рабочих query params (search, filters, unsubscribe token)
 */

function currentRequestScheme(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    return 'http';
}

function canonicalizeRequest(): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $queryString = parse_url($uri, PHP_URL_QUERY) ?: '';

    // Не трогаем admin/api/click
    if (str_starts_with($path, '/api/') || str_starts_with($path, '/admin') || str_starts_with($path, '/click/')) {
        return;
    }

    // Legacy пути карт -> новые пути
    $legacyMap = [
        '/karty-kreditnye' => '/karty/kreditnye',
        '/karty-debetovye' => '/karty/debetovye',
    ];
    if (isset($legacyMap[$path])) {
        redirectCanonical($legacyMap[$path], $queryString);
    }
    if (preg_match('#^/karty-kreditnye/type/(.+)$#', $path, $m)) {
        redirectCanonical('/karty/kreditnye/type/' . $m[1], $queryString);
    }
    if (preg_match('#^/karty-debetovye/type/(.+)$#', $path, $m)) {
        redirectCanonical('/karty/debetovye/type/' . $m[1], $queryString);
    }

    // Убираем лишние слеши и trailing slash
    $normalizedPath = preg_replace('#/+#', '/', $path);
    $normalizedPath = rtrim($normalizedPath, '/') ?: '/';

    // Фильтруем query params: выкидываем только мусорные tracking-параметры
    parse_str($queryString, $params);
    $dropParams = [
        'utm_source','utm_medium','utm_campaign','utm_content','utm_term',
        'gclid','yclid','fbclid','_openstat','openstat','from','src'
    ];
    foreach ($dropParams as $p) unset($params[$p]);

    // debug-параметр показа popup сохраняем
    // search q, unsubscribe token, filters — сохраняем автоматически
    $normalizedQuery = http_build_query($params);

    $targetBase = rtrim(SITE_URL, '/');
    $targetUrl = $targetBase . $normalizedPath . ($normalizedQuery ? '?' . $normalizedQuery : '');

    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    $siteHost = parse_url(SITE_URL, PHP_URL_HOST) ?: $currentHost;
    $currentScheme = currentRequestScheme();
    $siteScheme = parse_url(SITE_URL, PHP_URL_SCHEME) ?: $currentScheme;
    $currentUrl = $currentScheme . '://' . $currentHost . $path . ($queryString ? '?' . $queryString : '');

    if ($currentHost !== $siteHost || $currentScheme !== $siteScheme || $path !== $normalizedPath || $queryString !== $normalizedQuery) {
        header('Location: ' . $targetUrl, true, 301);
        exit;
    }
}

function redirectCanonical(string $path, string $queryString = ''): void {
    parse_str($queryString, $params);
    $dropParams = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term','gclid','yclid','fbclid','_openstat','openstat','from','src'];
    foreach ($dropParams as $p) unset($params[$p]);
    $query = http_build_query($params);
    $target = rtrim(SITE_URL, '/') . $path . ($query ? '?' . $query : '');
    header('Location: ' . $target, true, 301);
    exit;
}

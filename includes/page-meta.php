<?php
/**
 * Единые helper-функции для title/canonical/meta.
 */

function pageMetaTitle(string $title, bool $appendSite = true, string $separator = ' — '): string {
    $title = trim($title);
    if ($title === '') return SITE_NAME;
    if (!$appendSite) return $title;
    if (mb_stripos($title, SITE_NAME) !== false) return $title;
    return $title . $separator . SITE_NAME;
}

function pageCanonical(string $path = '/'): string {
    $path = trim($path);
    if ($path === '' || $path === SITE_URL) return SITE_URL . '/';
    if (str_starts_with((string)($path), 'http://') || str_starts_with((string)($path), 'https://')) return $path;
    if ($path[0] !== '/') $path = '/' . $path;
    return SITE_URL . $path;
}

function pageJsonLdBreadcrumbs(array $breadcrumbs): array {
    return $breadcrumbs ? [jsonLdBreadcrumb($breadcrumbs)] : [];
}

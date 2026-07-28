<?php
function getCategories(bool $activeOnly = true): array {
    static $cache = null;
    if ($cache !== null) return $activeOnly ? array_filter($cache, fn($c) => $c['is_active']) : $cache;
    try {
        $db = getDB();
        $cache = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Exception $e) { $cache = []; }
    return $activeOnly ? array_filter($cache, fn($c) => $c['is_active']) : $cache;
}

function getHeaderCategories(): array {
    return array_values(array_filter(getCategories(), fn($c) => $c['show_in_header'] && !$c['parent_id']));
}

function getFooterCategories(): array {
    return array_values(array_filter(getCategories(), fn($c) => $c['show_in_footer'] && !$c['parent_id']));
}

function getSubcategories(int $parentId): array {
    return array_values(array_filter(getCategories(), fn($c) => (int)$c['parent_id'] === $parentId));
}

function findCategoryBySlug(string $slug): ?array {
    foreach (getCategories(false) as $c) {
        if ($c['slug'] === $slug) return $c;
    }
    return null;
}

function getCategoryRoutePathBySlug(string $slug): string {
    return match ($slug) {
        'zajmy' => '/zajmy',
        'kredity' => '/kredity',
        'karty-kreditnye' => '/karty/kreditnye',
        'karty-debetovye' => '/karty/debetovye',
        default => '/' . ltrim($slug, '/'),
    };
}

function getCategoryOfferKeyBySlug(string $slug): string {
    return match ($slug) {
        'zajmy' => 'microloans',
        'kredity' => 'credits',
        'karty-kreditnye' => 'credit_cards',
        'karty-debetovye' => 'debit_cards',
        default => $slug,
    };
}

function getCategoryUrl(array $cat): string {
    return getCategoryRoutePathBySlug($cat['slug']);
}

function getFooterCategoriesBySection(string $section = 'products'): array {
    return array_values(array_filter(getCategories(), function($c) use ($section) {
        return $c['show_in_footer'] && !$c['parent_id'] && (($c['footer_section'] ?? 'products') === $section);
    }));
}

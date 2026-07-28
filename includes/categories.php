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
    foreach (getCategories(false) as $c) { if ($c['slug'] === $slug) return $c; }
    return null;
}

function getCategoryUrl(array $cat): string {
    return '/' . $cat['slug'];
}

<?php
/**
 * Единый helper для хлебных крошек.
 *
 * Использование:
 * $breadcrumbs = [
 *   ['name' => 'Главная', 'url' => '/'],
 *   ['name' => 'Статьи', 'url' => '/articles'],
 *   ['name' => 'Текущая статья'],
 * ];
 * echo renderBreadcrumbs($breadcrumbs);
 * $jsonLdSchemas[] = jsonLdBreadcrumb($breadcrumbs);
 */

function breadcrumbItem(string $name, ?string $url = null): array {
    $item = ['name' => $name];
    if ($url !== null && $url !== '') {
        $item['url'] = $url;
    }
    return $item;
}

function breadcrumbAbsoluteUrl(string $url): string {
    return str_starts_with((string)($url), 'http') ? $url : SITE_URL . $url;
}

function renderBreadcrumbs(?array $items, string $className = 'text-sm text-gray-500 mb-6'): string {
    if (!$items || !is_array($items)) return '';

    $normalized = [];
    foreach ($items as $item) {
        if (is_string($item)) {
            $normalized[] = ['name' => $item];
            continue;
        }
        if (is_array($item) && !empty($item['name'])) {
            $normalized[] = $item;
        }
    }

    if (!$normalized) return '';

    ob_start();
    ?>
    <nav class="<?= e($className) ?>" aria-label="Хлебные крошки">
        <ol class="flex flex-wrap items-center gap-1" itemscope itemtype="https://schema.org/BreadcrumbList">
            <?php foreach ($normalized as $index => $item):
                $isLast = $index === count($normalized) - 1;
                $name = (string)$item['name'];
                $url = $item['url'] ?? null;
            ?>
            <li class="inline-flex items-center gap-1" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <?php if (!$isLast && $url): ?>
                    <a href="<?= e($url) ?>" class="hover:text-primary" itemprop="item">
                        <span itemprop="name"><?= e($name) ?></span>
                    </a>
                <?php elseif ($url): ?>
                    <span itemprop="name" class="text-gray-700"><?= e($name) ?></span>
                    <meta itemprop="item" content="<?= e(breadcrumbAbsoluteUrl($url)) ?>">
                <?php else: ?>
                    <span itemprop="name" class="<?= $isLast ? 'text-gray-700' : '' ?>"><?= e($name) ?></span>
                <?php endif; ?>
                <meta itemprop="position" content="<?= $index + 1 ?>">
                <?php if (!$isLast): ?>
                    <span aria-hidden="true">→</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
    return ob_get_clean();
}

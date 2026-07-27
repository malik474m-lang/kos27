<?php
// Главный шаблон сайта
// Переменные: $pageTitle, $metaDescription, $metaKeywords, $content, $jsonLdSchemas, $canonicalUrl, $ogImage
require_once __DIR__ . '/seo.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$metaDescription = $metaDescription ?? 'Сравните лучшие предложения по займам, кредитам, кредитным и дебетовым картам.';
$metaKeywords = $metaKeywords ?? 'займы онлайн, кредиты, микрозаймы, кредитные карты, дебетовые карты';
$canonicalUrl = $canonicalUrl ?? '';
$ogImage = $ogImage ?? '';
$jsonLdSchemas = $jsonLdSchemas ?? [];

// Всегда добавляем Organization и Website
array_unshift($jsonLdSchemas, jsonLdOrganization(), jsonLdWebsite());
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($metaKeywords): ?>
    <meta name="keywords" content="<?= e($metaKeywords) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_RU">
    <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= e(str_starts_with($ogImage, 'http') ? $ogImage : SITE_URL . $ogImage) ?>">
    <?php endif; ?>
    <?php if ($canonicalUrl): ?>
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= e(str_starts_with($ogImage, 'http') ? $ogImage : SITE_URL . $ogImage) ?>">
    <?php endif; ?>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1a56db',
                    'primary-dark': '#1244af',
                    'primary-light': '#e1effe',
                    accent: '#059669',
                    'accent-dark': '#047857',
                    danger: '#dc2626',
                    warning: '#f59e0b',
                }
            }
        }
    }
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .gradient-hero { background: linear-gradient(135deg, #1a56db 0%, #7e3af2 100%); }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    </style>

    <!-- JSON-LD SEO разметка -->
    <?= renderJsonLd(...$jsonLdSchemas) ?>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col">
    <?php require __DIR__ . '/header.php'; ?>

    <main class="flex-1">
        <?= $content ?>
    </main>

    <?php require __DIR__ . '/footer.php'; ?>
    <?php require __DIR__ . '/analytics.php'; ?>
</body>
</html>
<?php pageCacheEnd(); ?>

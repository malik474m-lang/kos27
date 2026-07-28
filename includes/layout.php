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
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
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
    <link rel="stylesheet" href="/assets/site.min.css?v=20260728">

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

    <!-- Cookie consent -->
    <div id="cookie-consent" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:0;margin:0">
        <div style="max-width:600px;margin:0 auto 16px;background:#1f2937;color:#f3f4f6;border-radius:16px;padding:20px 24px;box-shadow:0 -4px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:14px;line-height:1.5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif">
            <span style="font-size:24px;flex-shrink:0">🍪</span>
            <p style="flex:1;min-width:200px;margin:0">Мы используем cookie для корректной работы сайта и аналитики. Продолжая пользоваться сайтом, вы соглашаетесь с <a href="/privacy" style="color:#93c5fd;text-decoration:underline">политикой конфиденциальности</a>.</p>
            <button onclick="acceptCookies()" style="background:#3b82f6;color:#fff;border:none;padding:10px 24px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;white-space:nowrap;flex-shrink:0">Принять</button>
        </div>
    </div>
    <script src="/assets/site.min.js?v=20260728" defer></script>
</body>
</html>
<?php pageCacheEnd(); ?>

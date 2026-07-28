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

// Лучшее предложение для popup (только для публичных страниц)
$bestOfferPopup = null;
try {
    $popupUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!str_contains($popupUri, '/admin') && !str_contains($popupUri, '/api/') && !str_contains($popupUri, '/click/')) {
        $popupDb = getDB();
        $popupStmt = $popupDb->query("SELECT id, title, slug, rate, amount_max, free_term_days, logo_url, category FROM offers WHERE is_active = 1 ORDER BY rating DESC, review_count DESC, sort_order ASC LIMIT 1");
        $bestOfferPopup = $popupStmt->fetch();
    }
} catch (Exception $e) {}

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

    <!-- Popup: Лучшее предложение -->
    <?php if (!empty($bestOfferPopup)): ?>
    <?php $popupLogo = normalizeMediaUrl($bestOfferPopup['logo_url'] ?? ''); ?>
    <div id="best-offer-popup-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:9997"></div>
    <div id="best-offer-popup" style="display:none;position:fixed;right:20px;bottom:20px;z-index:9998;width:min(420px,calc(100vw - 32px));background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(15,23,42,.25);overflow:hidden;border:1px solid #e5e7eb">
        <div style="background:linear-gradient(135deg,#1a56db 0%,#7e3af2 100%);padding:14px 18px;color:#fff;display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div>
                <div style="font-size:12px;opacity:.9;letter-spacing:.08em;text-transform:uppercase">Рекомендуем</div>
                <div style="font-size:18px;font-weight:700;line-height:1.2">Лучшее предложение</div>
            </div>
            <button type="button" onclick="hideBestOfferPopup(true)" aria-label="Закрыть" style="border:none;background:rgba(255,255,255,.15);color:#fff;width:32px;height:32px;border-radius:999px;font-size:18px;cursor:pointer">×</button>
        </div>
        <div style="padding:18px">
            <div style="display:flex;gap:14px;align-items:flex-start">
                <div style="width:60px;height:60px;border-radius:14px;background:#f8fafc;border:1px solid #eef2f7;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                    <?php if ($popupLogo): ?>
                    <img src="<?= e($popupLogo) ?>" alt="<?= e($bestOfferPopup['title']) ?>" style="width:100%;height:100%;object-fit:contain;padding:6px" loading="lazy">
                    <?php else: ?>
                    <span style="font-size:30px">🏦</span>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;flex:1">
                    <a href="/offer/<?= e($bestOfferPopup['slug']) ?>" style="display:block;color:#111827;font-size:18px;font-weight:700;line-height:1.25;text-decoration:none"><?= e($bestOfferPopup['title']) ?></a>
                    <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:8px">
                        <span style="background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:600;padding:4px 8px;border-radius:999px">от <?= e($bestOfferPopup['rate']) ?>%</span>
                        <span style="background:#f3f4f6;color:#374151;font-size:12px;font-weight:600;padding:4px 8px;border-radius:999px">до <?= number_format((int)$bestOfferPopup['amount_max'], 0, '', ' ') ?> ₽</span>
                        <?php if ((int)$bestOfferPopup['free_term_days'] > 0): ?>
                        <span style="background:#ecfdf5;color:#047857;font-size:12px;font-weight:600;padding:4px 8px;border-radius:999px">0% на <?= (int)$bestOfferPopup['free_term_days'] ?> дн.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:10px;margin-top:18px">
                <a href="/offer/<?= e($bestOfferPopup['slug']) ?>" style="flex:1;text-align:center;border:1px solid #dbeafe;color:#1d4ed8;background:#eff6ff;padding:12px 14px;border-radius:12px;font-weight:600;text-decoration:none">Подробнее</a>
                <a href="/click/<?= (int)$bestOfferPopup['id'] ?>?src=best-popup" target="_blank" rel="noopener noreferrer nofollow sponsored" style="flex:1;text-align:center;background:#059669;color:#fff;padding:12px 14px;border-radius:12px;font-weight:700;text-decoration:none">Оформить</a>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var popup = document.getElementById('best-offer-popup');
        var overlay = document.getElementById('best-offer-popup-overlay');
        if(!popup || !overlay) return;

        var closeKey = 'best_offer_popup_closed_v2';
        var shownKey = 'best_offer_popup_shown_session_v2';
        var forceShow = location.search.indexOf('showBestOffer=1') !== -1;

        function isTemporarilyClosed(){
            if(forceShow) return false;
            try {
                var until = parseInt(localStorage.getItem(closeKey) || '0', 10);
                return until && until > Date.now();
            } catch(e){ return false; }
        }

        function adjustPopupOffset(){
            var cookie = document.getElementById('cookie-consent');
            if(cookie && cookie.style.display !== 'none') {
                popup.style.bottom = '120px';
            } else {
                popup.style.bottom = '20px';
            }
        }

        if(isTemporarilyClosed()) return;
        if(!forceShow && sessionStorage.getItem(shownKey)==='1') return;

        function showBestOfferPopup(){
            adjustPopupOffset();
            popup.style.display='block';
            overlay.style.display='block';
            sessionStorage.setItem(shownKey,'1');
        }

        window.hideBestOfferPopup = function(persist){
            popup.style.display='none';
            overlay.style.display='none';
            if(persist) {
                try { localStorage.setItem(closeKey, String(Date.now() + 24*60*60*1000)); } catch(e){}
            }
        };

        overlay.addEventListener('click', function(){ hideBestOfferPopup(false); });
        window.addEventListener('resize', adjustPopupOffset);
        window.addEventListener('storage', adjustPopupOffset);

        if(forceShow) {
            showBestOfferPopup();
        } else {
            setTimeout(showBestOfferPopup, 30000);
        }
    })();
    </script>
    <?php endif; ?>


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

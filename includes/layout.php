<?php
// Главный шаблон сайта
// Переменные: $pageTitle, $metaDescription, $metaKeywords, $content, $jsonLdSchemas, $canonicalUrl, $ogImage
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/giveaway-banner.php';
require_once __DIR__ . '/pwa.php';
require_once __DIR__ . '/ab-test.php';

$pageTitle = $pageTitle ?? SITE_NAME;
$metaDescription = $metaDescription ?? 'Сравните лучшие предложения по займам, кредитам, кредитным и дебетовым картам.';
$metaKeywords = $metaKeywords ?? 'займы онлайн, кредиты, микрозаймы, кредитные карты, дебетовые карты';
// Автоматический canonical — если страница не задала, формируем из SITE_URL + текущий путь
if (empty($canonicalUrl)) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $currentPath = rtrim($currentPath, '/') ?: '/';
    // Не ставим canonical для admin/api/click
    if (!str_starts_with((string)($currentPath), '/api/') && !str_starts_with((string)($currentPath), '/admin') && !str_starts_with((string)($currentPath), '/click/')) {
        $canonicalUrl = SITE_URL . $currentPath;
    }
}
// Дефолтная картинка для соцсетей (если страница не задала свою — например, обложку статьи)
$ogImage = $ogImage ?? '';
if (trim((string)$ogImage) === '') {
    $defaultOgImage = '/images/kosmozaim01.jpg';
    $ogImage = $defaultOgImage;
}
$jsonLdSchemas = $jsonLdSchemas ?? [];
$pageHeadHtml = $pageHeadHtml ?? '';

// Лучшее предложение для popup (только для публичных страниц)
$bestOfferPopup = null;
try {
    $popupUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!str_contains((string)($popupUri), '/admin') && !str_contains((string)($popupUri), '/api/') && !str_contains((string)($popupUri), '/click/')) {
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
    <meta property="og:image" content="<?= e(str_starts_with((string)($ogImage), 'http') ? $ogImage : SITE_URL . $ogImage) ?>">
    <meta property="og:image:secure_url" content="<?= e(str_starts_with((string)($ogImage), 'http') ? $ogImage : SITE_URL . $ogImage) ?>">
    <?php endif; ?>
    <?php if ($canonicalUrl): ?>
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@kosmozaim">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= e(str_starts_with((string)($ogImage), 'http') ? $ogImage : SITE_URL . $ogImage) ?>">
    <?php endif; ?>

    <?php
    $siteFavicon = $_siteSettings['site_favicon'] ?? '';
    if ($siteFavicon && file_exists(__DIR__ . '/..' . $siteFavicon)): ?>
    <link rel="icon" href="<?= e($siteFavicon) ?>" type="<?= str_ends_with($siteFavicon, '.svg') ? 'image/svg+xml' : (str_ends_with($siteFavicon, '.png') ? 'image/png' : 'image/x-icon') ?>">
    <?php else: ?>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <?= getPwaHeadTags() ?>
    <?php endif; ?>
    <!-- Preconnect для метрик -->
    <?php if (YANDEX_METRIKA_ID): ?><link rel="dns-prefetch" href="//mc.yandex.ru"><?php endif; ?>
    <!-- Preload критических ресурсов -->
    <link rel="preload" href="/assets/tailwind.css?v=20260801" as="style">
    <link rel="stylesheet" href="/assets/tailwind.css?v=20260801">
    <link rel="stylesheet" href="/assets/site.min.css?v=20260728">
    <style>
        html, body { max-width:100%; overflow-x:hidden; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        main { max-width:100%; overflow-x:hidden; }
        img, svg, video, canvas, iframe { max-width:100%; height:auto; }
        .prose table { display:block; max-width:100%; overflow-x:auto; white-space:nowrap; }
        #exit-popup { max-width:calc(100vw - 24px); max-height:calc(100vh - 24px); overflow-y:auto; }
        #exit-popup-form-row { display:flex; gap:10px; margin-bottom:12px; }
        #exit-popup-features { display:flex; gap:12px; margin-top:16px; }
        @media (max-width: 640px) {
            #exit-popup { width:calc(100vw - 24px) !important; border-radius:18px !important; top:50% !important; left:50% !important; transform:translate(-50%,-50%) !important; }
            #exit-popup-form-row { flex-direction:column; gap:12px !important; }
            #exit-popup-email { width:100% !important; min-width:0 !important; }
            #exit-popup-btn { width:100% !important; display:flex !important; justify-content:center !important; }
            #exit-popup-features { gap:8px !important; }
            #exit-popup-features > div { padding:10px 8px !important; }
            #geo-switch-prompt { top:74px !important; width:calc(100vw - 16px) !important; }
        }
        .gradient-hero { background: linear-gradient(135deg, #1a56db 0%, #7e3af2 100%); }
        .card-hover { transition: transform .2s, box-shadow .2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,.1); }
        .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .line-clamp-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
        .prose h2 { font-size:1.5rem; font-weight:700; color:#111827; margin-top:1.75rem; margin-bottom:.75rem; line-height:1.3; }
        .prose h3 { font-size:1.25rem; font-weight:600; color:#111827; margin-top:1.5rem; margin-bottom:.5rem; line-height:1.4; }
        .prose h4 { font-size:1.1rem; font-weight:600; color:#1f2937; margin-top:1.25rem; margin-bottom:.5rem; }
        .prose p { margin-bottom:1rem; line-height:1.7; }
        .prose ul, .prose ol { margin-bottom:1rem; padding-left:1.5rem; }
        .prose ul { list-style-type:disc; }
        .prose ol { list-style-type:decimal; }
        .prose li { margin-bottom:.35rem; line-height:1.6; }
        .prose li::marker { color:#6b7280; }
        .prose a { color:#1a56db; text-decoration:underline; }
        .prose a:hover { color:#1244af; }
        .prose strong { font-weight:600; color:#111827; }
        .prose blockquote { border-left:4px solid #e5e7eb; padding-left:1rem; margin:1rem 0; color:#6b7280; font-style:italic; }
        .prose table { width:100%; border-collapse:collapse; margin:1rem 0; }
        .prose th, .prose td { border:1px solid #e5e7eb; padding:.5rem .75rem; text-align:left; }
        .prose th { background:#f9fafb; font-weight:600; }
        .prose img { border-radius:.5rem; margin:1rem 0; }
        .prose-sm h2 { font-size:1.3rem; }
        .prose-sm h3 { font-size:1.15rem; }
        .prose-sm p, .prose-sm li { font-size:.938rem; }
        .prose-lg h2 { font-size:1.75rem; }
        .prose-lg h3 { font-size:1.4rem; }
        .prose-lg p { font-size:1.1rem; }
        .offer-card-box{background:#fff;border:1px solid #f1f5f9;border-radius:1rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
        .offer-card-logo{background:#f8fafc;border:1px solid #eef2f7}
        .offer-card-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;min-width:0}
        .offer-card-head,.offer-card-actions{min-width:0}
        .offer-card-box *{min-width:0}
        .offer-card-grid p{word-break:break-word}
        .offer-card-desc{line-height:1.6}
        .offer-card-actions a,.offer-card-actions button{min-height:44px}
        @media(min-width:640px){.offer-card-grid{grid-template-columns:repeat(4,minmax(0,1fr))}}
        @media(max-width:639px){.offer-card-box{padding:0.9rem;border-radius:0.9rem}.offer-card-head{gap:.75rem}.offer-card-logo{width:3.5rem;height:3.5rem}.offer-card-grid{gap:.625rem;grid-template-columns:repeat(2,minmax(0,1fr))}.offer-card-actions{gap:.625rem;flex-direction:column}.offer-card-actions a,.offer-card-actions button{width:100%;justify-content:center}.offer-card-box .text-2xl{font-size:1.1rem}.offer-card-box .text-sm{font-size:.875rem}.offer-card-box .text-xs{font-size:.75rem}.offer-card-box .line-clamp-2{display:block;overflow:visible}}

        /* Глобальный fallback для страницы оффера */
        .offer-page-wrap{max-width:80rem;margin:0 auto;padding:2rem 1rem}
        .offer-main-card,.offer-calc-card,.offer-review-card,.offer-form-card,.offer-related-card{background:#fff;border:1px solid #f1f5f9;border-radius:1.5rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
        .offer-main-card{padding:2rem}.offer-calc-card{padding:1.5rem 2rem}.offer-form-card{padding:1.5rem}.offer-related-card{padding:1.5rem}
        .offer-top{display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem}.offer-logo{width:5rem;height:5rem;background:#f3f4f6;border-radius:.75rem;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
        .offer-main-grid,.offer-main-grid-4{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;margin-bottom:1.5rem}
        .offer-metric{background:#f9fafb;border-radius:.75rem;padding:1rem}.offer-metric-label{font-size:.75rem;text-transform:uppercase;color:#6b7280}.offer-metric-value{font-size:1.125rem;font-weight:700;color:#111827;margin-top:.25rem}
        .offer-cta{display:inline-flex;align-items:center;gap:.5rem;background:#059669;color:#fff;padding:1rem 2rem;border-radius:.75rem;font-weight:700;text-decoration:none}.offer-cta:hover{opacity:.92}
        .offer-title-2{font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:1.5rem}
        .offer-calc-grid{display:grid;grid-template-columns:1.2fr .8fr;gap:2rem;align-items:start}.offer-calc-side{background:#f9fafb;border:1px solid #f1f5f9;border-radius:1rem;padding:1.5rem}
        @media (min-width:768px){.offer-main-grid-4{grid-template-columns:repeat(4,minmax(0,1fr))}}
        @media (max-width:1023px){.offer-calc-grid{grid-template-columns:1fr}}
        @media (max-width:639px){.offer-page-wrap{padding:1.5rem 1rem}.offer-main-card{padding:1.25rem}.offer-calc-card{padding:1.25rem}.offer-top{gap:1rem;align-items:flex-start}.offer-logo{width:4.25rem;height:4.25rem}.offer-main-grid,.offer-main-grid-4{grid-template-columns:1fr 1fr;gap:.75rem}.offer-metric-value{font-size:1rem}.offer-cta{width:100%;justify-content:center}}
    </style>
    <?= $pageHeadHtml ?>

    <!-- JSON-LD SEO разметка -->
    <?= renderJsonLd(...$jsonLdSchemas) ?>

    <!-- Dark mode: FOUC prevention -->
    <script>
    (function(){try{if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}})();
    </script>
    <style>
    /* Dark mode */
    .dark body,.dark main{background:#0f172a;color:#e2e8f0;}
    .dark header{background:#1e293b !important;border-color:#334155 !important;}
    .dark footer{background:#020617 !important;}
    .dark .bg-white,.dark .offer-card-box,.dark .bg-gray-50,.dark .offer-main-card,.dark .offer-calc-card,.dark .offer-review-card,.dark .offer-form-card,.dark .offer-related-card{background:#1e293b !important;color:#e2e8f0 !important;}
    .dark .bg-gray-100{background:#1e293b !important;}
    .dark .border-gray-100,.dark .border-gray-200{border-color:#334155 !important;}
    .dark .text-gray-900,.dark .text-gray-800{color:#f1f5f9 !important;}
    .dark .text-gray-700,.dark .text-gray-600{color:#cbd5e1 !important;}
    .dark .text-gray-500,.dark .text-gray-400{color:#94a3b8 !important;}
    .dark .text-gray-300{color:#64748b !important;}
    .dark .shadow-sm{box-shadow:0 1px 2px rgba(0,0,0,.3) !important;}
    .dark .prose h2,.dark .prose h3,.dark .prose h4{color:#f1f5f9 !important;}
    .dark .prose p,.dark .prose li{color:#cbd5e1 !important;}
    .dark .prose a{color:#60a5fa !important;}
    .dark .prose th{background:#334155 !important;color:#e2e8f0 !important;}
    .dark .prose td,.dark .prose th{border-color:#475569 !important;}
    .dark input,.dark textarea,.dark select{background:#334155 !important;color:#e2e8f0 !important;border-color:#475569 !important;}
    .dark .card-hover:hover{box-shadow:0 10px 25px -5px rgba(0,0,0,.3) !important;}
    .dark .bg-blue-50,.dark .bg-green-50,.dark .bg-emerald-50,.dark .bg-purple-50,.dark .bg-indigo-50,.dark .bg-orange-50,.dark .bg-amber-50,.dark .bg-yellow-50,.dark .bg-red-50{background:#1e293b !important;}
    .dark .border-blue-100,.dark .border-green-100,.dark .border-emerald-100,.dark .border-purple-100,.dark .border-amber-200{border-color:#334155 !important;}
    .dark .offer-card-logo,.dark .offer-logo{background:#334155 !important;border-color:#475569 !important;}
    .dark .offer-metric{background:#334155 !important;}
    .dark .offer-metric-label{color:#94a3b8 !important;}
    .dark .offer-metric-value{color:#f1f5f9 !important;}
    .dark details{border-color:#475569 !important;}
    .dark details summary{color:#e2e8f0 !important;}
    .dark details summary:hover{background:#334155 !important;}
    .dark .not-prose{border-color:#475569 !important;}
    .dark #social-proof-widget .bg-white{background:#1e293b !important;border-color:#334155 !important;}
    .dark #social-proof-widget #sp-text{color:#e2e8f0 !important;}
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex flex-col">
<?php $giveawayBanner = renderGiveawayBanner(); echo $giveawayBanner; ?>
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
                    <img src="<?= e($popupLogo) ?>" alt="<?= e($bestOfferPopup['title']) ?>" style="width:100%;height:100%;object-fit:contain;padding:6px" loading="lazy" decoding="async">
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
                <?php $popupCta = getCtaVariantData((string)($bestOfferPopup['category'] ?? '')); ?>
                <a href="/click/<?= (int)$bestOfferPopup['id'] ?>?src=best-popup&ab=<?= (int)$popupCta['id'] ?>" target="_blank" rel="noopener noreferrer nofollow sponsored" style="flex:1;text-align:center;background:<?= e($popupCta['color']) ?>;color:#fff;padding:12px 14px;border-radius:12px;font-weight:700;text-decoration:none"><?= e($popupCta['label']) ?></a>
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



    <!-- Exit Intent Popup: Подписка -->
    <div id="exit-popup-overlay" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.6);z-index:10001;backdrop-filter:blur(4px)"></div>
    <div id="exit-popup" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10002;width:min(460px,calc(100vw - 32px));background:#fff;border-radius:24px;box-shadow:0 25px 80px rgba(15,23,42,.3);overflow:hidden">
        <div style="background:linear-gradient(135deg,#059669 0%,#0d9488 50%,#0891b2 100%);padding:28px 24px 20px;text-align:center;position:relative">
            <button type="button" onclick="hideExitPopup(true)" aria-label="Закрыть" style="position:absolute;top:12px;right:12px;border:none;background:rgba(255,255,255,.2);color:#fff;width:32px;height:32px;border-radius:999px;font-size:18px;cursor:pointer;line-height:30px">×</button>
            <div style="font-size:48px;margin-bottom:8px">🚀</div>
            <h3 style="color:#fff;font-size:22px;font-weight:800;margin:0 0 6px;line-height:1.3">Подождите!</h3>
            <p style="color:rgba(255,255,255,.9);font-size:14px;margin:0;line-height:1.5">Получите персональную подборку лучших<br>предложений прямо на почту</p>
        </div>
        <div style="padding:24px">
            <form id="exit-popup-form" onsubmit="return exitPopupSubmit(event)">
                <div id="exit-popup-form-row" style="display:flex;gap:10px;margin-bottom:12px">
                    <input type="email" id="exit-popup-email" placeholder="Ваш email" required style="flex:1;border:2px solid #e5e7eb;border-radius:12px;padding:14px 16px;font-size:15px;outline:none;transition:border .2s" onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#e5e7eb'">
                    <button type="submit" id="exit-popup-btn" style="background:#059669;color:#fff;border:none;padding:14px 24px;border-radius:12px;font-weight:700;font-size:15px;cursor:pointer;white-space:nowrap">Получить</button>
                </div>
                <div id="exit-popup-msg" style="display:none;text-align:center;padding:8px;border-radius:8px;font-size:13px;margin-bottom:8px"></div>
            </form>
            <div id="exit-popup-features" style="display:flex;gap:12px;margin-top:16px">
                <div style="flex:1;background:#f0fdf4;border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:20px">💰</div>
                    <div style="font-size:11px;color:#166534;font-weight:600;margin-top:4px">Лучшие ставки</div>
                </div>
                <div style="flex:1;background:#eff6ff;border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:20px">⚡</div>
                    <div style="font-size:11px;color:#1e40af;font-weight:600;margin-top:4px">Быстрое одобрение</div>
                </div>
                <div style="flex:1;background:#fdf4ff;border-radius:12px;padding:12px;text-align:center">
                    <div style="font-size:20px">🎁</div>
                    <div style="font-size:11px;color:#7e22ce;font-weight:600;margin-top:4px">Спецпредложения</div>
                </div>
            </div>
            <p style="text-align:center;font-size:11px;color:#9ca3af;margin:14px 0 0">Без спама. Отписка в один клик.</p>
        </div>
    </div>
    <script>
    (function(){
        var popup=document.getElementById('exit-popup');
        var overlay=document.getElementById('exit-popup-overlay');
        if(!popup||!overlay)return;

        var storageKey='exit_popup_closed_v1';
        var shownThisSession=false;

        function isClosed(){
            try{
                var until=parseInt(localStorage.getItem(storageKey)||'0',10);
                return until&&until>Date.now();
            }catch(e){return false;}
        }

        function showExitPopup(){
            if(shownThisSession||isClosed())return;
            // Не показывать на админке, в кабинете, на странице отписки
            var p=location.pathname;
            if(p.indexOf('/admin')===0||p.indexOf('/cabinet')===0||p.indexOf('/unsubscribe')===0||p.indexOf('/login')===0||p.indexOf('/register')===0)return;
            // Не показывать если уже подписан (localStorage)
            if(localStorage.getItem('subscribed')==='1')return;
            shownThisSession=true;
            popup.style.display='block';
            overlay.style.display='block';
        }

        window.hideExitPopup=function(persist){
            popup.style.display='none';
            overlay.style.display='none';
            if(persist){
                try{localStorage.setItem(storageKey,String(Date.now()+3*24*60*60*1000));}catch(e){}
            }
        };

        overlay.addEventListener('click',function(){hideExitPopup(true);});

        // Exit intent: курсор уходит вверх за пределы окна
        document.addEventListener('mouseout',function(e){
            if(e.clientY<=0&&e.relatedTarget===null){
                showExitPopup();
            }
        });

        // Мобильные: при нажатии "назад" / закрытии — через задержку + скролл вверх
        var lastScroll=window.scrollY;
        var scrollUpCount=0;
        window.addEventListener('scroll',function(){
            var cur=window.scrollY;
            if(cur<lastScroll&&lastScroll-cur>100){
                scrollUpCount++;
                if(scrollUpCount>=3){showExitPopup();}
            }else{
                scrollUpCount=0;
            }
            lastScroll=cur;
        });
    })();

    function exitPopupSubmit(ev){
        ev.preventDefault();
        var email=document.getElementById('exit-popup-email').value.trim();
        var btn=document.getElementById('exit-popup-btn');
        var msg=document.getElementById('exit-popup-msg');
        if(!email)return false;

        btn.disabled=true;btn.textContent='⏳';

        fetch('/api/subscribe',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({email:email,source:'exit_popup'})
        }).then(function(r){return r.json();}).then(function(d){
            btn.disabled=false;btn.textContent='Получить';
            if(d.success||d.message){
                msg.style.display='block';
                msg.style.background='#ecfdf5';
                msg.style.color='#166534';
                msg.textContent='✅ '+(d.message||'Подписка оформлена! Проверьте почту.');
                try{localStorage.setItem('subscribed','1');}catch(e){}
                if(typeof window.kzTrackGoal==='function')window.kzTrackGoal('newsletter_subscribed',{form:'exit_popup'});
                setTimeout(function(){hideExitPopup(true);},2500);
            }else{
                msg.style.display='block';
                msg.style.background='#fef2f2';
                msg.style.color='#991b1b';
                msg.textContent='❌ '+(d.error||'Ошибка');
            }
        }).catch(function(){
            btn.disabled=false;btn.textContent='Получить';
            msg.style.display='block';
            msg.style.background='#fef2f2';
            msg.style.color='#991b1b';
            msg.textContent='❌ Ошибка соединения';
        });
        return false;
    }
    </script>

    <!-- Cookie consent -->
    <div id="cookie-consent" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;padding:0;margin:0">
        <div style="max-width:600px;margin:0 auto 16px;background:#1f2937;color:#f3f4f6;border-radius:16px;padding:20px 24px;box-shadow:0 -4px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:16px;flex-wrap:wrap;font-size:14px;line-height:1.5;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif">
            <span style="font-size:24px;flex-shrink:0">🍪</span>
            <p style="flex:1;min-width:0;margin:0">Мы используем cookie для корректной работы сайта и аналитики. Продолжая пользоваться сайтом, вы соглашаетесь с <a href="/privacy" style="color:#93c5fd;text-decoration:underline">политикой конфиденциальности</a>.</p>
            <button onclick="acceptCookies()" style="background:#3b82f6;color:#fff;border:none;padding:10px 24px;border-radius:10px;font-weight:600;font-size:14px;cursor:pointer;white-space:nowrap;flex-shrink:0;width:100%;max-width:180px">Принять</button>
        </div>
    </div>
    <script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('img:not([loading])').forEach(function(img){
    var inHeader = !!img.closest('header');
    var inHero = !!img.closest('.gradient-hero');
    if(!inHeader && !inHero){ img.setAttribute('loading','lazy'); }
    if(!img.hasAttribute('decoding')) img.setAttribute('decoding','async');
  });
});
</script>
<script src="/assets/site.min.js?v=20260728" defer></script>
<?= getPwaInstallBanner() ?>
<?= getPwaScripts() ?>
<?php require_once __DIR__ . "/social-proof.php"; echo renderSocialProofWidget(); ?>

<script>
function toggleTheme(){
    var html=document.documentElement;
    var isDark=html.classList.toggle('dark');
    try{localStorage.setItem('theme',isDark?'dark':'light');}catch(e){}
    updateThemeIcons(isDark);
}
function updateThemeIcons(isDark){
    var icon=isDark?'☀️':'🌙';
    var el=document.getElementById('theme-icon');if(el)el.textContent=icon;
    var el2=document.getElementById('theme-icon-mobile');if(el2)el2.textContent=icon;
}
(function(){
    try{var isDark=document.documentElement.classList.contains('dark');updateThemeIcons(isDark);}catch(e){}
})();
</script>
<?php require_once __DIR__ . "/chat-widget.php"; echo renderChatWidget(); ?>
</body>
</html>
<?php pageCacheEnd(); ?>

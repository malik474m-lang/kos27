<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <a href="/" class="flex items-center space-x-2">
                    <?php if (defined('SITE_LOGO') && SITE_LOGO): ?>
                    <img src="<?= e(SITE_LOGO) ?>" alt="<?= e(SITE_NAME) ?>" class="h-10 max-w-[160px] object-contain" decoding="async" fetchpriority="high">
                    <?php else: ?>
                    <span class="text-2xl">🚀</span>
                    <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?= e(SITE_NAME) ?></span>
                    <?php endif; ?>
                </a>
                <span id="geo-city" class="text-xs text-gray-400">📍 ...</span>
            </div>

            <nav class="hidden lg:flex items-center space-x-6">
                <a href="/zajmy" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Займы</a>
                <a href="/kredity" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Кредиты</a>
                <div class="relative group">
                    <button class="text-gray-700 hover:text-blue-600 font-medium transition-colors flex items-center">
                        Банковские карты
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute top-full left-0 mt-1 bg-white shadow-lg rounded-lg py-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <a href="/karty/kreditnye" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Кредитные карты</a>
                        <a href="/karty/debetovye" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">Дебетовые карты</a>
                    </div>
                </div>
                <a href="/compare" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Сравнение</a>
                <a href="/calculator" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Калькулятор</a>
                <a href="/articles" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Статьи</a>
                <a href="/faq" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">FAQ</a>
                <a href="/favorites" class="text-gray-700 hover:text-blue-600 font-medium transition-colors" title="Избранное">❤️</a>
                <?php require_once __DIR__ . '/user-auth.php'; if (isLoggedIn()): ?>
                <a href="/cabinet" class="text-gray-700 hover:text-blue-600 font-medium transition-colors" title="Кабинет">👤</a>
                <?php else: ?>
                <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition-colors text-sm">Войти</a>
                <?php endif; ?>
            </nav>

            <div class="flex items-center space-x-2">
                <a href="/search" class="p-2 text-gray-500 hover:text-blue-600 transition-colors" aria-label="Поиск">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </a>
                <button class="lg:hidden p-2" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" aria-label="Меню">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" class="lg:hidden hidden pb-4 space-y-2">
            <a href="/zajmy" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Займы</a>
            <a href="/kredity" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Кредиты</a>
            <a href="/karty/kreditnye" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Кредитные карты</a>
            <a href="/karty/debetovye" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Дебетовые карты</a>
            <a href="/compare" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Сравнение</a>
            <a href="/calculator" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Калькулятор</a>
            <a href="/articles" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">Статьи</a>
            <a href="/faq" class="block py-2 text-gray-700 hover:text-blue-600 font-medium">FAQ</a>
        </nav>
    </div>
</header>

<div id="geo-switch-prompt" class="hidden fixed left-1/2 z-[9996] w-[min(92vw,560px)] -translate-x-1/2 rounded-2xl border border-blue-100 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.18)]" style="top:88px;">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 text-2xl">📍</div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-900">Похоже, вы из города <span id="geo-switch-city-name"></span>?</p>
            <p class="mt-1 text-sm text-gray-500">Показать актуальную страницу для вашего региона.</p>
        </div>
        <button type="button" onclick="hideGeoSwitchPrompt(true)" class="text-xl leading-none text-gray-300 hover:text-gray-500">×</button>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <a id="geo-switch-link" href="#" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">Да, показать мой город</a>
        <button type="button" onclick="hideGeoSwitchPrompt(true)" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">Нет, остаться здесь</button>
    </div>
</div>

<script>
(function(){
    var cityLabel = document.getElementById('geo-city');
    var promptEl = document.getElementById('geo-switch-prompt');
    var promptCityName = document.getElementById('geo-switch-city-name');
    var promptLink = document.getElementById('geo-switch-link');
    if(!cityLabel) return;

    var path = (location.pathname || '/').replace(/\/+$/,'') || '/';
    var dismissKeyPrefix = 'geo_switch_dismiss_';

    function buildGeoTarget(slug){
        if(!slug) return null;
        if (path === '/zajmy' || /^\/zajmy\/[a-z0-9-]+$/i.test(path)) return '/zajmy/' + slug;
        if (path === '/kredity' || /^\/kredity\/[a-z0-9-]+$/i.test(path)) return '/kredity/' + slug;
        if (path === '/karty/kreditnye' || path === '/karty/debetovye' || /^\/karty\/[a-z0-9-]+$/i.test(path)) return '/karty/' + slug;
        return null;
    }

    window.hideGeoSwitchPrompt = function(persist){
        if (promptEl) promptEl.classList.add('hidden');
        if (persist && promptLink && promptLink.href) {
            try { localStorage.setItem(dismissKeyPrefix + promptLink.getAttribute('href'), String(Date.now() + 24*60*60*1000)); } catch(e){}
        }
    };

    function isDismissed(target){
        try {
            var until = parseInt(localStorage.getItem(dismissKeyPrefix + target) || '0', 10);
            return until && until > Date.now();
        } catch(e) { return false; }
    }

    fetch('/api/geo')
        .then(function(r){ return r.json(); })
        .then(function(d){
            if(d.city) cityLabel.textContent = '📍 ' + d.city;
            if (!d.slug) return;
            var target = buildGeoTarget(d.slug);
            if (!target || target === path) return;
            if (isDismissed(target)) return;
            if (!promptEl || !promptLink || !promptCityName) return;
            promptCityName.textContent = d.city;
            promptLink.setAttribute('href', target);
            promptEl.classList.remove('hidden');
        })
        .catch(function(){});
})();
</script>

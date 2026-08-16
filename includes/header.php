<?php
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/user-auth.php';
require_once __DIR__ . '/../data/cities.php';
$headerCats = getHeaderCategories();
?>
<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between min-h-[64px] py-2 gap-3">
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <a href="/" class="flex items-center space-x-2 min-w-0 max-w-full">
                    <?php if (defined('SITE_LOGO') && SITE_LOGO): ?>
                    <img src="<?= e(SITE_LOGO) ?>" alt="<?= e(SITE_NAME) ?>" class="h-9 sm:h-10 max-w-[120px] sm:max-w-[160px] object-contain" decoding="async" fetchpriority="high">
                    <?php else: ?>
                    <span class="text-2xl">🚀</span>
                    <span class="text-lg sm:text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent truncate max-w-[48vw] sm:max-w-none"><?= e(SITE_NAME) ?></span>
                    <?php endif; ?>
                </a>
                <button type="button" id="geo-city" onclick="openCityPicker()" class="hidden md:inline text-xs text-gray-400 truncate max-w-[160px] hover:text-blue-600 transition-colors text-left">📍 Выбрать город</button>
            </div>

            <nav class="hidden lg:flex items-center space-x-6">
                <?php foreach ($headerCats as $hc):
                    $subs = getSubcategories((int)$hc['id']);
                    if ($subs): ?>
                <div class="relative group">
                    <button class="text-gray-700 hover:text-blue-600 font-medium transition-colors flex items-center">
                        <?= e($hc['name']) ?>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute top-full left-0 mt-1 bg-white shadow-lg rounded-lg py-2 w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all">
                        <?php foreach ($subs as $sub): ?>
                        <a href="<?= getCategoryUrl($sub) ?>" class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600"><?= e($sub['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                    <?php else: ?>
                <a href="<?= getCategoryUrl($hc) ?>" class="text-gray-700 hover:text-blue-600 font-medium transition-colors"><?= e($hc['name']) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>

                <button type="button" onclick="toggleTheme()" id="theme-toggle-btn" class="text-gray-500 hover:text-blue-600 transition-colors" title="Тема" aria-label="Переключить тему"><span id="theme-icon">🌙</span></button>
                <a href="/favorites" class="text-gray-700 hover:text-blue-600 font-medium transition-colors" title="Избранное">❤️</a>
                <?php if (isLoggedIn()): ?>
                <a href="/cabinet" class="text-gray-700 hover:text-blue-600 font-medium transition-colors" title="Кабинет">👤</a>
                <?php else: ?>
                <a href="/login" class="text-gray-700 hover:text-blue-600 font-medium transition-colors text-sm">Войти</a>
                <?php endif; ?>
            </nav>

            <div class="flex items-center space-x-1 sm:space-x-2 flex-shrink-0">
                <a href="/search" class="p-2 text-gray-500 hover:text-blue-600 transition-colors" aria-label="Поиск">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </a>
                <button class="lg:hidden p-2 rounded-lg hover:bg-gray-100" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" aria-label="Меню">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>

        <nav id="mobile-menu" class="lg:hidden hidden pb-4 space-y-1 border-t border-gray-100 pt-3 mt-2 bg-white">
            <?php foreach ($headerCats as $hc): ?>
            <a href="<?= getCategoryUrl($hc) ?>" class="block py-2.5 px-1 text-gray-700 hover:text-blue-600 font-medium rounded-lg"><?= e($hc['name']) ?></a>
            <?php foreach (getSubcategories((int)$hc['id']) as $sub): ?>
            <a href="<?= getCategoryUrl($sub) ?>" class="block py-2 pl-4 text-gray-600 hover:text-blue-600 text-sm rounded-lg"><?= e($sub['name']) ?></a>
            <?php endforeach; endforeach; ?>
            <div class="pt-2 mt-2 border-t border-gray-100 flex flex-wrap items-center gap-3">
                <button type="button" onclick="openCityPicker();document.getElementById('mobile-menu').classList.add('hidden')" class="text-sm text-gray-600 hover:text-blue-600">📍 Выбрать город</button>
                <button type="button" onclick="toggleTheme()" class="text-sm text-gray-600 hover:text-blue-600"><span id="theme-icon-mobile">🌙</span> Тема</button>
                <a href="/favorites" class="text-sm text-gray-600 hover:text-blue-600">❤️ Избранное</a>
                <?php if (isLoggedIn()): ?>
                <a href="/cabinet" class="text-sm text-gray-600 hover:text-blue-600">👤 Кабинет</a>
                <?php else: ?>
                <a href="/login" class="text-sm text-gray-600 hover:text-blue-600">Войти</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<div id="city-picker-overlay" class="hidden fixed inset-0 z-[9995] bg-slate-900/50 backdrop-blur-sm" onclick="closeCityPicker()"></div>
<div id="city-picker" class="hidden fixed inset-x-3 top-20 z-[9996] mx-auto w-full max-w-lg rounded-2xl border border-gray-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.22)]">
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5">
        <div>
            <h3 class="text-base font-bold text-gray-900">Выберите город</h3>
            <p class="text-xs text-gray-500">Переключение страницы под ваш регион</p>
        </div>
        <button type="button" onclick="closeCityPicker()" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700">×</button>
    </div>
    <div class="p-4 sm:p-5">
        <input type="text" id="city-picker-search" placeholder="Найти город..." class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm outline-none focus:border-blue-500" oninput="renderCityPickerList(this.value)">
        <div id="city-picker-list" class="mt-4 grid max-h-[60vh] gap-2 overflow-y-auto sm:grid-cols-2"></div>
    </div>
</div>

<div id="geo-switch-prompt" class="hidden fixed left-1/2 z-[9996] w-[min(92vw,560px)] -translate-x-1/2 rounded-2xl border border-blue-100 bg-white p-4 shadow-[0_20px_50px_rgba(15,23,42,0.18)]" style="top:88px;max-width:calc(100vw - 16px);">
    <div class="flex items-start gap-3"><div class="mt-0.5 text-2xl">📍</div><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-gray-900">Похоже, вы из города <span id="geo-switch-city-name"></span>?</p><p class="mt-1 text-sm text-gray-500">Показать актуальную страницу для вашего региона.</p></div><button type="button" onclick="hideGeoSwitchPrompt(true)" class="text-xl leading-none text-gray-300 hover:text-gray-500 flex-shrink-0">×</button></div>
    <div class="mt-4 flex flex-col sm:flex-row sm:flex-wrap gap-2"><a id="geo-switch-link" href="#" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Да, показать мой город</a><button type="button" onclick="hideGeoSwitchPrompt(true)" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Нет, остаться здесь</button></div>
</div>

<script>
(function(){
    var cityLabel=document.getElementById('geo-city'),promptEl=document.getElementById('geo-switch-prompt'),promptCityName=document.getElementById('geo-switch-city-name'),promptLink=document.getElementById('geo-switch-link');
    var picker=document.getElementById('city-picker'),pickerOverlay=document.getElementById('city-picker-overlay'),pickerList=document.getElementById('city-picker-list');
    var citiesData=<?= json_encode(array_values(getCities()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    if(!cityLabel)return;
    var path=(location.pathname||'/').replace(/\/+$/,'')||'/';
    var dismissKeyPrefix='geo_switch_dismiss_';

    function buildGeoTarget(slug){
        if(!slug)return null;
        var m;
        if(path==='/'||path==='/zajmy') return '/zajmy/'+slug;
        if(path==='/kredity') return '/kredity/'+slug;
        if(path==='/karty/kreditnye') return '/karty/kreditnye/'+slug;
        if(path==='/karty/debetovye') return '/karty/debetovye/'+slug;
        if((m=path.match(/^\/zajmy\/([a-z0-9-]+)\/type\/([a-z0-9-]+)$/i))) return '/zajmy/'+slug+'/type/'+m[2];
        if((m=path.match(/^\/kredity\/([a-z0-9-]+)\/type\/([a-z0-9-]+)$/i))) return '/kredity/'+slug+'/type/'+m[2];
        if((m=path.match(/^\/karty\/kreditnye\/([a-z0-9-]+)\/type\/([a-z0-9-]+)$/i))) return '/karty/kreditnye/'+slug+'/type/'+m[2];
        if((m=path.match(/^\/karty\/debetovye\/([a-z0-9-]+)\/type\/([a-z0-9-]+)$/i))) return '/karty/debetovye/'+slug+'/type/'+m[2];
        if((m=path.match(/^\/zajmy\/([a-z0-9-]+)$/i))) return '/zajmy/'+slug;
        if((m=path.match(/^\/kredity\/([a-z0-9-]+)$/i))) return '/kredity/'+slug;
        if((m=path.match(/^\/karty\/([a-z0-9-]+)$/i))) return '/karty/'+slug;
        if((m=path.match(/^\/karty\/kreditnye\/([a-z0-9-]+)$/i))) return '/karty/kreditnye/'+slug;
        if((m=path.match(/^\/karty\/debetovye\/([a-z0-9-]+)$/i))) return '/karty/debetovye/'+slug;
        return '/zajmy/'+slug;
    }

    function cityCard(city){
        var target=buildGeoTarget(city.slug)||('/zajmy/'+city.slug);
        return '<a href="'+target+'" class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-3 text-sm text-gray-700 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700 transition-colors"><div class="font-semibold">'+city.name+'</div><div class="mt-0.5 text-xs text-gray-400">'+(city.region||'')+'</div></a>';
    }

    window.renderCityPickerList=function(q){
        if(!pickerList)return;
        q=(q||'').toLowerCase().trim();
        var filtered=citiesData.filter(function(c){
            return !q || c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q) || (c.region||'').toLowerCase().includes(q);
        });
        if(!filtered.length){
            pickerList.innerHTML='<div class="sm:col-span-2 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">Ничего не найдено. Попробуйте другой город.</div>';
            return;
        }
        pickerList.innerHTML=filtered.slice(0,60).map(cityCard).join('');
    };

    window.openCityPicker=function(){
        if(picker) picker.classList.remove('hidden');
        if(pickerOverlay) pickerOverlay.classList.remove('hidden');
        renderCityPickerList('');
        var inp=document.getElementById('city-picker-search');
        if(inp){
            inp.value='';
            inp.onkeydown=function(ev){
                if(ev.key==='Enter'){
                    ev.preventDefault();
                    var q=(inp.value||'').toLowerCase().trim();
                    var first=citiesData.find(function(c){
                        return !q || c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q) || (c.region||'').toLowerCase().includes(q);
                    });
                    if(first){ location.href = buildGeoTarget(first.slug)||('/zajmy/'+first.slug); }
                }
            };
            setTimeout(function(){inp.focus();},50);
        }
    };

    window.closeCityPicker=function(){
        if(picker) picker.classList.add('hidden');
        if(pickerOverlay) pickerOverlay.classList.add('hidden');
    };

    function isDismissed(t){
        try{var u=parseInt(localStorage.getItem(dismissKeyPrefix+t)||'0',10);return u&&u>Date.now();}catch(e){return false;}
    }

    window.hideGeoSwitchPrompt=function(persist){
        if(promptEl)promptEl.classList.add('hidden');
        if(persist&&promptLink)try{localStorage.setItem(dismissKeyPrefix+promptLink.getAttribute('href'),String(Date.now()+86400000));}catch(e){}
    };

    fetch('/api/geo').then(function(r){return r.json();}).then(function(d){
        if(d.city)cityLabel.textContent='📍 '+d.city;
        if(!d.slug)return;
        var target=buildGeoTarget(d.slug);
        if(!target||target===path||isDismissed(target)||!promptEl||!promptLink||!promptCityName)return;
        promptCityName.textContent=d.city;
        promptLink.setAttribute('href',target);
        promptEl.classList.remove('hidden');
    }).catch(function(){});
})();
</script>

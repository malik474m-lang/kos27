<?php
require_once __DIR__ . '/../includes/minify.php';
require_once __DIR__ . '/../includes/license.php';
require_once __DIR__ . '/../data/cities.php';
$adminLicenseInfo = getLicenseInfo();
$adminLicensePlan = $adminLicenseInfo['plan'] ?? 'unknown';
$adminLicenseExpires = !empty($adminLicenseInfo['expires_at']) ? date('d.m.Y', strtotime($adminLicenseInfo['expires_at'])) : '—';
$adminLicenseActive = !empty($adminLicenseInfo['active']);
ob_start('minifyHtmlOutput');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Админ-панель — <?= e(SITE_NAME) ?></title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/tailwind.css?v=20260801">
<style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto;}
.input-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;}
.input-f:focus{outline:none;ring:2px solid #1a56db;}
.sel-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;background:white;font-size:0.875rem;}
.btn-p{background:#1a56db;color:white;padding:0.5rem 1.5rem;border-radius:0.5rem;font-weight:600;font-size:0.875rem;cursor:pointer;}
.btn-p:hover{background:#1244af;}
.btn-p:disabled{opacity:0.5;}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<div class="bg-gray-900 text-white"><div class="max-w-7xl mx-auto px-4 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"><div class="flex items-center gap-4 flex-wrap"><div class="flex items-center space-x-3"><span class="text-2xl">⚙️</span><h1 class="text-lg font-bold">Админ-панель <?= e(SITE_NAME) ?></h1></div><div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium <?= $adminLicenseActive ? 'bg-green-500/15 text-green-300 border border-green-400/20' : 'bg-red-500/15 text-red-300 border border-red-400/20' ?>"><span><?= $adminLicenseActive ? '✅' : '⚠️' ?></span><span>Тариф: <strong><?= e((string)$adminLicensePlan) ?></strong></span><span class="opacity-70">•</span><span>До: <strong><?= e($adminLicenseExpires) ?></strong></span></div></div><div class="flex items-center flex-wrap gap-3"><button onclick="show2FA()" class="text-gray-300 hover:text-white text-sm">🔐 2FA</button><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm">🔑 Пароль</button><button onclick="clearCache()" class="text-gray-300 hover:text-white text-sm">🗑 Сбросить кэш</button><button onclick="clearApiCache()" class="text-gray-300 hover:text-white text-sm">⚡ API-кэш</button><button id="bonus-withdraw-alert-btn" onclick="sw(\'users\')" class="hidden rounded-full border border-yellow-400/30 bg-yellow-500/15 px-3 py-1 text-xs font-bold text-yellow-300 hover:bg-yellow-500/25">💸 0 заявок на вывод</button><button onclick="resetOpcache()" class="text-gray-300 hover:text-white text-sm">♻️ OPcache</button><a href="/admin/about" class="text-gray-300 hover:text-white text-sm">ℹ️ О системе</a><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти →</button></div></div></div>
<div class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-4"><div class="flex space-x-4 overflow-x-auto">
<button onclick="sw('settings')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="settings">⚙️ Настройки</button>
<button onclick="sw('offers')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="offers">📋 Предложения</button>
<button onclick="sw('links')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="links">🔗 Ссылки</button>
<button onclick="sw('cats')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="cats">📂 Категории</button>
<button onclick="sw('articles')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="articles">📰 Статьи</button>
<button onclick="sw('reviews')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="reviews">⭐ Отзывы</button>
<button onclick="sw('tags')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="tags">🏷️ Теги</button>
<button onclick="sw('geo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="geo">🌍 Гео-редиректы</button>
<button onclick="sw('cities')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="cities">🏘️ Города</button>
<button onclick="sw('cityseo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="cityseo">🏙️ SEO городов</button>
<button onclick="sw('stats')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="stats">📊 Статистика</button>
<button onclick="sw('funnel')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="funnel">🔻 Воронка</button>
<button onclick="sw('smart')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="smart">🧠 Рейтинг</button>
<button onclick="sw('conversions')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="conversions">💰 Конверсии</button>
<button onclick="sw('ab')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="ab">🧪 A/B тесты</button>
<button onclick="sw('subs')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="subs">📬 Подписчики</button>
<button onclick="sw('scheduler')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="scheduler">⏰ Планировщик</button>
<button onclick="sw('batch')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="batch">🤖 Пакетная</button>
<button onclick="sw('history')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="history">📜 История</button>
<button onclick="sw('analytics')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="analytics">📈 Аналитика</button>
<button onclick="sw('backup')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="backup">💾 Бэкап</button>
<button onclick="sw('users')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="users">👥 Пользователи</button>
<button onclick="sw('monitor')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="monitor">🖥️ Мониторинг</button>
<button onclick="sw('pwa')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="pwa">📱 PWA</button>
<button onclick="sw('mobileapp')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="mobileapp">📲 Приложение</button>
<button onclick="sw('health')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="health">🏥 Здоровье</button>
<button onclick="sw('giveaway')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="giveaway">🎁 Розыгрыши</button>
<button onclick="sw('positions')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="positions">📊 Позиции</button>
<button onclick="sw('indexing')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="indexing">🔍 Индексация</button>
<button onclick="sw('security')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="security">🔒 Безопасность</button>
<button onclick="sw('direct')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="direct">📣 Директ</button>
<button onclick="sw('leadssu')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="leadssu">🔗 Leads.su</button>
<button onclick="sw('emailfunnel')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="emailfunnel">📧 Email-воронка</button>
<button onclick="sw('aiproviders')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="aiproviders">🤖 AI провайдеры</button>
</div></div></div>
<div class="bg-gray-50 border-b"><div class="max-w-7xl mx-auto px-4 py-3 text-sm text-gray-500" id="admin-breadcrumb">Админка</div></div>
<div class="max-w-7xl mx-auto px-4 py-8">
<div id="p-settings" class="tp hidden"></div>
<div id="p-offers" class="tp"></div>
<div id="p-links" class="tp hidden"></div>
<div id="p-cats" class="tp hidden"></div>
<div id="p-articles" class="tp hidden"></div>
<div id="p-reviews" class="tp hidden"></div>
<div id="p-tags" class="tp hidden"></div>
<div id="p-geo" class="tp hidden"></div>
<div id="p-cities" class="tp hidden"></div>
<div id="p-cityseo" class="tp hidden"></div>
<div id="p-stats" class="tp hidden"></div>
<div id="p-funnel" class="tp hidden"></div>
<div id="p-smart" class="tp hidden"></div>
<div id="p-conversions" class="tp hidden"></div>
<div id="p-ab" class="tp hidden"></div>
<div id="p-subs" class="tp hidden"></div>
<div id="p-scheduler" class="tp hidden"></div>
<div id="p-batch" class="tp hidden"></div>
<div id="p-history" class="tp hidden"></div>
<div id="p-analytics" class="tp hidden"></div>
<div id="p-backup" class="tp hidden"></div>
<div id="p-users" class="tp hidden"></div>
<div id="p-monitor" class="tp hidden"></div>
<div id="p-health" class="tp hidden"></div>
<div id="p-pwa" class="tp hidden"></div>
<div id="p-mobileapp" class="tp hidden"></div>
<div id="p-security" class="tp hidden"></div>
<div id="p-giveaway" class="tp hidden"></div>
<div id="p-positions" class="tp hidden"></div>
<div id="p-indexing" class="tp hidden"></div>
<div id="p-direct" class="tp hidden"></div>
<div id="p-leadssu" class="tp hidden"></div>
<div id="p-emailfunnel" class="tp hidden"></div>
<div id="p-aiproviders" class="tp hidden"></div>
</div>
<div id="M"></div>
<div id="M2"></div>
<script>
const A='/api/admin';
var siteName='<?= e(SITE_NAME) ?>';
var SITE_URL='<?= e(SITE_URL) ?>';
var adminCities=<?= json_encode(array_values(getCities()), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
function ap(u,o){return fetch(A+u,{headers:{'Content-Type':'application/json'},...o}).then(r=>r.json());}
function e(s){if(!s)return'';let d=document.createElement('div');d.textContent=s;return d.innerHTML;}
const TAB_LABELS={aiproviders:'AI провайдеры',direct:'Яндекс Директ',leadssu:'Leads.su',emailfunnel:'Email-воронка',giveaway:'Розыгрыши',positions:'Позиции',indexing:'Индексация',cities:'Города',settings:'Настройки',offers:'Предложения',articles:'Статьи',reviews:'Отзывы',tags:'Теги',geo:'Гео-редиректы',cityseo:'SEO городов',stats:'Статистика',funnel:'Воронка',smart:'Умный рейтинг',links:'Партнёрские ссылки',conversions:'Конверсии',ab:'A/B тесты',subs:'Подписчики и рассылки',scheduler:'Планировщик',batch:'Пакетная генерация',history:'История изменений',analytics:'Финансовая аналитика',backup:'Бэкап',users:'Пользователи',cats:'Категории',security:'Безопасность',monitor:'Мониторинг',health:'Здоровье сайта',pwa:'PWA Статистика',mobileapp:'Приложение'};
function sw(t){document.querySelectorAll('.tp').forEach(x=>x.classList.add('hidden'));document.getElementById('p-'+t).classList.remove('hidden');document.querySelectorAll('.tb').forEach(b=>{let a=b.dataset.t===t;b.classList.toggle('border-blue-600',a);b.classList.toggle('text-blue-600',a);b.classList.toggle('border-transparent',!a);b.classList.toggle('text-gray-500',!a);});var bc=document.getElementById('admin-breadcrumb');if(bc)bc.innerHTML='<a href="/admin" class="hover:text-blue-600">Админка</a> → <span class="text-gray-700">'+(TAB_LABELS[t]||t)+'</span>';({settings:lSet,offers:lO,cats:lCats,articles:lA,reviews:lR,tags:lT,geo:lG,cityseo:lCS,stats:lS,funnel:lFunnel,smart:lSmart,links:lLinks,conversions:lConv,ab:lAB,subs:lSu,scheduler:lSch,batch:lBatch,history:lHistory,analytics:lAnalytics,backup:lB,users:lUsers,security:lSec,direct:lYD,leadssu:lLS,emailfunnel:lEF,health:lHealth,monitor:lMonitor,indexing:lIndexing,cities:lCities,positions:lPositions,giveaway:lGiveaway,aiproviders:lAIP})[t]?.();}
function clearCache(){fetch('/admin/clear-cache').then(r=>r.json()).then(d=>{if(d.success)alert('✓ Кэш очищен');else alert('Ошибка');}).catch(()=>alert('Ошибка'));}
function clearApiCache(){fetch(A+'/clear-api-cache',{method:'POST'}).then(r=>r.json()).then(d=>{if(d.success)alert('✓ API-кэш очищен: '+d.cleared);else alert(d.error||'Ошибка');}).catch(()=>alert('Ошибка'));}
function resetOpcache(){fetch(A+'/opcache-reset',{method:'POST'}).then(r=>r.json()).then(d=>{alert((d.message||'Результат неизвестен') + (d.enabled===false ? '\n\nopcache_reset() недоступен на хостинге.' : ''));}).catch(()=>alert('Ошибка сброса OPcache'));}
function logout(){fetch(A+'/logout',{method:'POST'}).then(()=>location.href='/admin/login');}
function loadBonusWithdrawAlert(){fetch(A+'/bonus-withdraw-alert').then(r=>r.json()).then(function(d){var btn=document.getElementById('bonus-withdraw-alert-btn');if(!btn)return;var cnt=Number(d.pending||0);if(cnt>0){btn.textContent='💸 '+cnt+' заяв'+(cnt===1?'ка':(cnt<5?'ки':'ок'))+' на вывод';btn.classList.remove('hidden');}else{btn.classList.add('hidden');}}).catch(function(){});}
function modal(h){document.getElementById('M').innerHTML='<div class="modal-bg" onclick="if(event.target===this)cm()"><div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-2xl">'+h+'</div></div>';}
function cm(){document.getElementById('M').innerHTML='';}
function cm2(){document.getElementById('M2').innerHTML='';}
const CL={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
const BL={any:'Любой',employed:'Работающий',unemployed:'Безработный',pensioner:'Пенсионер',student:'Студент',self_employed:'Самозанятый'};

/* ============ OFFERS ============ */
function getOffersUiState(){
try{return JSON.parse(localStorage.getItem('offers_ui_state')||'{}')||{};}catch(e){return{};}}
function setOffersUiState(state){localStorage.setItem('offers_ui_state',JSON.stringify(state));}
function toggleOfferGroup(key){
var state=getOffersUiState();
state['group_'+key]=!(state['group_'+key]===true);
setOffersUiState(state);
var box=document.getElementById('offers-group-'+key);
var icon=document.getElementById('offers-group-icon-'+key);
if(box){box.classList.toggle('hidden',state['group_'+key]===true);} 
if(icon){icon.textContent=(state['group_'+key]===true?'▸':'▾');}
}

function lO(){ap('/offers').then(list=>{
let state=getOffersUiState();
let currentFilter=state.filter||'all';
let currentSearch=state.search||'';
let h='<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6"><div><h2 class="text-xl font-bold">Предложения ('+list.length+')</h2><p class="text-sm text-gray-500 mt-1">Сортировка отдельно внутри каждой категории</p></div><div class="flex flex-wrap gap-2"><button onclick="bulkToggle(\'offers\')" id="bulk-offers-toggle" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">☑ Выбрать</button><button onclick="faqBulkGen()" class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1.5 rounded-lg text-xs font-semibold">❓ FAQ всем</button><button onclick="oForm()" class="btn-p text-sm">+ Добавить</button></div></div>';

h+='<div class="bg-white rounded-xl border p-4 mb-6"><div class="grid sm:grid-cols-[1fr_220px_auto] gap-3 items-end">';
h+='<div><label class="block text-xs font-medium text-gray-500 mb-1">Поиск по названию</label><input id="offers-search" class="input-f" placeholder="Например, Вебзайм" value="'+e(currentSearch)+'" oninput="applyOffersFilters()"></div>';
h+='<div><label class="block text-xs font-medium text-gray-500 mb-1">Категория</label><select id="offers-filter" class="sel-f" onchange="applyOffersFilters()"><option value="all">Все категории</option>';
Object.keys(CL).forEach(function(k){h+='<option value="'+k+'"'+(currentFilter===k?' selected':'')+'>'+CL[k]+'</option>';});
h+='</select></div>';
h+='<div class="flex gap-2"><button type="button" onclick="expandAllOfferGroups()" class="px-3 py-2 text-sm text-gray-600 border rounded-lg hover:bg-gray-50">Развернуть</button><button type="button" onclick="collapseAllOfferGroups()" class="px-3 py-2 text-sm text-gray-600 border rounded-lg hover:bg-gray-50">Свернуть</button></div>';
h+='</div></div>';

let filtered=list.filter(function(o){
  let okCat=currentFilter==='all'||o.category===currentFilter;
  let q=currentSearch.trim().toLowerCase();
  let okSearch=!q || (o.title||'').toLowerCase().includes(q) || (o.slug||'').toLowerCase().includes(q);
  return okCat && okSearch;
});

let groups={};
filtered.forEach(function(o){ let key=o.category||'other'; if(!groups[key]) groups[key]=[]; groups[key].push(o); });
let orderedKeys=[];
Object.keys(CL).forEach(function(k){ if(groups[k]&&groups[k].length) orderedKeys.push(k); });
Object.keys(groups).forEach(function(k){ if(!orderedKeys.includes(k)) orderedKeys.push(k); });

if(!filtered.length){
h+='<div class="bg-white rounded-xl border p-10 text-center text-gray-500">Ничего не найдено по выбранным фильтрам</div>';
}else{
orderedKeys.forEach(function(key){
  let items=groups[key]||[];
  let totalClicks=items.reduce((s,o)=>s+Number(o.clicks_total||0),0);
  let monthClicks=items.reduce((s,o)=>s+Number(o.clicks_30d||0),0);
  let collapsed=state['group_'+key]===true;
  h+='<div class="bg-white rounded-2xl border shadow-sm p-4 mb-6">';
  h+='<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">';
  h+='<button type="button" onclick="toggleOfferGroup(\''+key+'\')" class="flex items-center gap-3 text-left min-w-0">';
  h+='<span id="offers-group-icon-'+key+'" class="text-gray-400 text-lg">'+(collapsed?'▸':'▾')+'</span>';
  h+='<div><h3 class="text-lg font-bold text-gray-900">'+(CL[key]||key)+' <span class="text-sm font-normal text-gray-400">('+items.length+')</span></h3><p class="text-xs text-gray-500 mt-1">Клики за 30 дней: <strong>'+monthClicks+'</strong> • Всего: <strong>'+totalClicks+'</strong></p></div>';
  h+='</button>';
  h+='<div class="text-xs text-gray-400">Перетаскивай внутри блока за ☰</div>';
  h+='</div>';
  h+='<div id="offers-group-'+key+'" class="space-y-2'+(collapsed?' hidden':'')+'">';
  h+='<div id="offers-sortable-'+key+'" class="space-y-2">';
  items.forEach(function(o){
    h+='<div class="bg-gray-50 rounded-xl border p-4 flex items-center gap-4 cursor-move hover:shadow-sm transition-shadow" data-id="'+o.id+'">';
    h+='<input type="checkbox" class="bulk-cb bulk-offers-cb w-4 h-4 hidden" data-id="'+o.id+'" onclick="event.stopPropagation();bulkUpdate(\'offers\')">';h+='<span class="text-gray-300 cursor-grab drag-handle text-lg">☰</span>';
    if(o.logo_url){var lg=o.logo_url;if(lg.indexOf("/public/")===0)lg=lg.substring(7);h+='<div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0 border"><img src="'+lg+'" class="w-full h-full object-contain p-0.5" loading="lazy"></div>';}else{h+='<div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0 border">🏦</div>';}
    h+='<div class="flex-1 min-w-0"><p class="font-semibold text-gray-900 text-sm">'+e(o.title)+'</p><p class="text-xs text-gray-500">'+(CL[o.category]||o.category)+' • '+o.rate+'% '+((o.rate_unit==='year')?'в год':'в день')+' • '+Number(o.amount_min).toLocaleString()+'—'+Number(o.amount_max).toLocaleString()+' ₽</p></div>';
    h+='<div class="text-right text-xs text-gray-500 min-w-[86px]"><div>30 дн: <strong>'+Number(o.clicks_30d||0)+'</strong></div><div>всего: <strong>'+Number(o.clicks_total||0)+'</strong></div></div>';
    h+='<span class="px-2 py-0.5 rounded text-xs font-semibold '+(o.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(o.is_active?'Вкл':'Выкл')+'</span>';
    h+='<button onclick="event.stopPropagation();oForm('+JSON.stringify(o).replace(/'/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-sm">Ред.</button>';
    h+='<button onclick="event.stopPropagation();faqGen('+o.id+',&#39;'+e(o.title).replace(/'/g,'')+'&#39;)" class="text-purple-600 hover:underline text-sm">FAQ</button>';
    h+='<button onclick="event.stopPropagation();oD('+o.id+')" class="text-red-500 hover:underline text-sm">Уд.</button>';
    h+='</div>';
  });
  h+='</div></div></div>';
});
}

document.getElementById('p-offers').innerHTML=h;
orderedKeys.forEach(function(key){ if(document.getElementById('offers-sortable-'+key)) initSort('offers-sortable-'+key,'offers'); });
});}

function applyOffersFilters(){
var state=getOffersUiState();
state.search=document.getElementById('offers-search')?.value||'';
state.filter=document.getElementById('offers-filter')?.value||'all';
setOffersUiState(state);
lO();
}
function expandAllOfferGroups(){var state=getOffersUiState();Object.keys(CL).forEach(function(k){delete state['group_'+k];});setOffersUiState(state);lO();}
function collapseAllOfferGroups(){var state=getOffersUiState();Object.keys(CL).forEach(function(k){state['group_'+k]=true;});setOffersUiState(state);lO();}

function oForm(o){let f=o||{title:'',category:'microloans',amount_min:1000,amount_max:100000,term_min_days:1,term_max_days:365,psk:'0',rate:'0',rate_unit:'day',free_term_days:0,logo_url:'',affiliate_url:'',borrower_category:'any',description:'',seo_keywords:'',regions:'',is_active:true,sort_order:0,phone:'',address:'',trademark:'',license:'',kosmobonus_enabled:false,kosmobonus_amount:0,kosmobonus_conditions:''};let id=o?o.id:0;
let catOpts='',borOpts='';for(let k in CL)catOpts+='<option value="'+k+'"'+(f.category===k?' selected':'')+'>'+CL[k]+'</option>';for(let k in BL)borOpts+='<option value="'+k+'"'+(f.borrower_category===k?' selected':'')+'>'+BL[k]+'</option>';
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать':'Новое предложение')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>'+
'<form onsubmit="return oS(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="of-t" class="input-f" value="'+e(f.title)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Категория</label><select id="of-c" class="sel-f">'+catOpts+'</select></div>'+
'<div id="of-borrower-wrap"><label class="block text-xs font-medium mb-1">Заёмщик</label><select id="of-b" class="sel-f">'+borOpts+'</select></div>'+
'<div><label class="block text-xs font-medium mb-1">Сумма от</label><input id="of-am1" type="number" class="input-f" value="'+f.amount_min+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сумма до</label><input id="of-am2" type="number" class="input-f" value="'+f.amount_max+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Срок от (дн)</label><input id="of-t1" type="number" class="input-f" value="'+f.term_min_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Срок до (дн)</label><input id="of-t2" type="number" class="input-f" value="'+f.term_max_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">ПСК %</label><input id="of-psk" type="number" step="0.01" class="input-f" value="'+f.psk+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Ставка %</label><div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_140px] gap-2"><input id="of-r" type="number" step="0.01" class="input-f w-full min-w-0" value="'+f.rate+'"><select id="of-ru" class="sel-f w-full sm:w-[140px]"><option value="day"'+((f.rate_unit||'day')==='day'?' selected':'')+'>в день</option><option value="year"'+((f.rate_unit||'day')==='year'?' selected':'')+'>в год</option></select></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Без % (дн)</label><input id="of-fr" type="number" class="input-f" value="'+f.free_term_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сортировка</label><input id="of-so" type="number" class="input-f" value="'+f.sort_order+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">URL логотипа</label><div class="flex gap-2"><input id="of-lo" class="input-f flex-1" value="'+e(f.logo_url||'')+'"><button type="button" onclick="mediaPicker(\'of-lo\',\'offer\')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap">📁 Выбрать</button></div><div id="of-lo-preview" class="mt-2">'+(f.logo_url?'<img src="'+e(f.logo_url)+'" class="w-16 h-16 object-contain rounded border bg-white">':'')+'</div></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Партнёрская ссылка *</label><input id="of-af" class="input-f" value="'+e(f.affiliate_url||'')+'" required></div>'+
'<div class="col-span-2 border-t pt-3 mt-2"><div class="flex items-center justify-between mb-2"><span class="text-xs font-bold text-gray-700">📋 Справочная информация</span><div class="flex items-center gap-3"><button type="button" onclick="genOfferInfo(this)" class="bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded text-xs">🤖 ИИ</button><a href="https://cbr.ru/microfinance/registry/" target="_blank" class="text-blue-600 hover:underline text-xs">Реестр ЦБ ↗</a></div></div><div class="grid grid-cols-2 gap-2"><div><label class="block text-xs font-medium mb-1">Телефон</label><input id="of-phone" class="input-f" value="'+e(f.phone||'')+'" placeholder="8-800-..."></div><div><label class="block text-xs font-medium mb-1">Лицензия ЦБ</label><input id="of-license" class="input-f" value="'+e(f.license||'')+'" placeholder="№..."></div><div><label class="block text-xs font-medium mb-1">Торговая марка</label><input id="of-trademark" class="input-f" value="'+e(f.trademark||'')+'" placeholder="ООО..."></div><div><label class="block text-xs font-medium mb-1">Адрес</label><input id="of-address" class="input-f" value="'+e(f.address||'')+'" placeholder="г. Москва..."></div></div></div>'+
'<div class="col-span-2 border-t pt-3 mt-2"><div class="flex items-center gap-3 mb-2"><span class="text-xs font-bold text-amber-700">🎁 КосмоБонус</span></div><div class="grid grid-cols-3 gap-2"><div><label class="flex items-center gap-2"><input type="checkbox" id="of-kb-enabled" '+(f.kosmobonus_enabled?'checked':'')+' class="w-4 h-4"><span class="text-xs">Участвует в акции</span></label></div><div><label class="block text-xs font-medium mb-1">Бонус (₽)</label><input id="of-kb-amount" type="number" class="input-f" value="'+(f.kosmobonus_amount||0)+'" min="0"></div><div><label class="block text-xs font-medium mb-1">Условия</label><input id="of-kb-conditions" class="input-f" value="'+e(f.kosmobonus_conditions||'')+'" placeholder="Условия акции..."></div></div></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Описание</label><div class="flex flex-wrap gap-2 mb-2"><button type="button" onclick="cqAnalyzeForm(&#39;of&#39;,&#39;offer&#39;)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">🧪 Качество</button><button type="button" onclick="cqImproveField(&#39;of&#39;,&#39;offer&#39;,&#39;description&#39;)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Улучшить</button><button type="button" onclick="cqImproveField(&#39;of&#39;,&#39;offer&#39;,&#39;description&#39;,80,3)" style="background:#059669;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🎯 До 80+</button><button type="button" onclick="cqImproveField(&#39;of&#39;,&#39;offer&#39;,&#39;description&#39;,90,4)" style="background:#0f766e;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🚀 До 90+</button><button type="button" onclick="cqCleanupOnly(&#39;of&#39;,&#39;offer&#39;,&#39;description&#39;)" style="background:#475569;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🧹 Только мусор</button><span id="of-quality-badge" class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2.5 py-1 text-xs font-semibold">score —</span></div><textarea id="of-de" class="input-f" rows="3">'+e(f.description||'')+'</textarea></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO ключевые слова</label><input id="of-sk" class="input-f" value="'+e(f.seo_keywords||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-2">🧩 Стандартные поля</label><div id="of-display-fields" class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm"></div></div>'+'<div class="col-span-2"><label class="block text-xs font-medium mb-2">📋 Дополнительные поля</label><div id="of-extra-template-actions" class="mb-3 rounded-xl border border-gray-200 bg-gray-50 p-3"><div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold text-gray-700">Быстрые шаблоны полей</p><p id="of-extra-template-hint" class="text-xs text-gray-500">Выберите категорию оффера, чтобы подставить готовые поля.</p></div><div class="flex flex-wrap gap-2"><button type="button" onclick="ofApplyCurrentCategoryTemplate(false)" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Подставить шаблон категории</button><button type="button" onclick="ofApplyCurrentCategoryTemplate(true)" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Добавить шаблон категории</button></div></div></div><div id="of-extra-fields"></div><button type="button" onclick="ofAddExtraField()" class="text-sm text-blue-600 hover:underline mt-1">+ Добавить поле</button></div>'+
'<div class="col-span-2"><label class="flex items-center gap-2"><input type="checkbox" id="of-ac" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активно</span></label></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-2">🏷️ Теги</label><div id="of-tags-box" class="flex flex-wrap gap-2"><span class="text-xs text-gray-400">Загрузка...</span></div></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
setTimeout(function(){ cqSetInlineScore('of', 0); }, 0);
// Инициализируем видимость стандартных полей
ofInitDisplayFields(f);
ofToggleOfferFieldGroups();
ofToggleExtraTemplateActions();

// Инициализируем дополнительные поля (из шаблона категории или существующих)
var ef=[];try{ef=JSON.parse(f.extra_fields||'[]');}catch(x){}
if(!ef.length){
  // Загружаем шаблон из категории
  var catSlug=f.category||document.getElementById('of-c')?.value||'microloans';
  ap('/categories').then(function(cats){
    var cat=cats.find(function(c){return c.slug===catSlug;});
    var tpl=[];
    if(cat&&cat.field_templates){try{tpl=JSON.parse(cat.field_templates);}catch(x){}}
    if(!tpl.length)tpl=[{label:'Льготный период',visible:true}];
    tpl=tpl.map(function(t){return{label:t.label||'',value:'',visible:!!t.visible};});
    ofRenderExtraFields(tpl);
  }).catch(function(){
    ofRenderExtraFields([{label:'Льготный период',value:'',visible:true}]);
  });
} else {
  ofRenderExtraFields(ef);
}

// При смене категории — предлагать подставить шаблон
var catSelect=document.getElementById('of-c');
if(catSelect&&!catSelect.dataset.boundTpl){
  catSelect.dataset.boundTpl='1';
  catSelect.addEventListener('change',function(){
    if(!id){
      ap('/categories').then(function(cats){
        var cat=cats.find(function(c){return c.slug===catSelect.value;});
        if(cat&&cat.field_templates){
          var tpl=[];try{tpl=JSON.parse(cat.field_templates);}catch(x){}
          if(tpl.length&&confirm('Подставить шаблон полей для категории "'+(cat.name||catSelect.value)+'"?')){
            tpl=tpl.map(function(t){return{label:t.label||'',value:'',visible:!!t.visible};});
            ofRenderExtraFields(tpl);
          }
        }
        ofToggleOfferFieldGroups();
        ofToggleExtraTemplateActions();
      });
    }
    ofToggleOfferFieldGroups();
    ofToggleExtraTemplateActions();
  });
}

// Загружаем теги для оффера
Promise.all([ap('/tags'),ap('/tag-links?offerId='+(id||0))]).then(([allTags,linked])=>{
var box=document.getElementById('of-tags-box');if(!box)return;
var linArr=linked.map(Number);
box.innerHTML=allTags.filter(t=>t.category===f.category||!id).map(t=>'<label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer '+(linArr.includes(Number(t.id))?'bg-blue-50 border-blue-300':'bg-white border-gray-200')+' hover:border-blue-400"><input type="checkbox" class="of-tag-cb w-3.5 h-3.5" value="'+t.id+'"'+(linArr.includes(Number(t.id))?' checked':'')+'> '+(t.icon||'🏷️')+' '+e(t.title)+'</label>').join('');
if(!allTags.length)box.innerHTML='<span class="text-xs text-gray-400">Нет тегов. Создайте на вкладке 🏷️ Теги</span>';
});}

function ofToggleOfferFieldGroups(){
var catSel=document.getElementById('of-c');
if(!catSel)return;
var category=catSel.value||'microloans';
var isDebit=category==='debit_cards';
var ids=['of-am1','of-am2','of-t1','of-t2','of-psk','of-r','of-fr','of-b'];
ids.forEach(function(id){
  var el=document.getElementById(id);
  if(!el)return;
  var wrap=el.closest('div');
  if(wrap) wrap.style.display=isDebit?'none':'';
});
  var rateUnit=document.getElementById('of-ru');
  if(rateUnit){
    var rateWrap=rateUnit.closest('div');
    if(rateWrap) rateWrap.style.display=isDebit?'none':'';
  }
  var borrower=document.getElementById('of-borrower-wrap');
  if(borrower) borrower.style.display=isDebit?'none':(['microloans','credits'].includes(category)?'block':'none');
}

function ofDefaultDisplayFields(category, freeTermDays){
return {
amount: ['microloans','credits','credit_cards'].includes(category),
term: ['microloans','credits'].includes(category),
rate: ['microloans','credits','credit_cards'].includes(category),
psk: ['microloans','credits','credit_cards'].includes(category),
free_term: Number(freeTermDays||0) > 0,
borrower: ['microloans','credits'].includes(category)
};
}
function ofInitDisplayFields(f){
var cfg=ofDefaultDisplayFields(f.category||'microloans', f.free_term_days||0);
try{if(f.display_fields){var parsed=JSON.parse(f.display_fields); if(parsed && typeof parsed==='object') cfg=Object.assign(cfg, parsed);}}catch(e){}
var box=document.getElementById('of-display-fields'); if(!box) return;
var labels={amount:'Сумма/лимит',term:'Срок',rate:'Ставка',psk:'ПСК',free_term:'Льготный период',borrower:'Заёмщик'};
box.innerHTML=Object.keys(labels).map(function(k){
var checked=cfg[k]?'checked':'';
var hidden=(k==='borrower' && !['microloans','credits'].includes(f.category))?' style="display:none"':'';
return '<label class="flex items-center gap-2" data-display-key="'+k+'"'+hidden+'><input type="checkbox" class="of-df w-4 h-4" data-key="'+k+'" '+checked+'><span>'+labels[k]+'</span></label>';
}).join('');
var borrowerWrap=document.getElementById('of-borrower-wrap');
if(borrowerWrap) borrowerWrap.style.display=['microloans','credits'].includes(f.category)?'block':'none';
var catSel=document.getElementById('of-c');
if(catSel&&!catSel.dataset.boundDisplay){
catSel.dataset.boundDisplay='1';
catSel.addEventListener('change',function(){
var category=this.value;
var borrower=document.getElementById('of-borrower-wrap');
if(borrower) borrower.style.display=(category==='debit_cards'?'none':(['microloans','credits'].includes(category)?'block':'none'));
document.querySelectorAll('[data-display-key="borrower"]').forEach(function(el){el.style.display=['microloans','credits'].includes(category)?'flex':'none';});
ofToggleOfferFieldGroups();
ofToggleExtraTemplateActions();
});
}
}
function ofCollectDisplayFields(){
var out={};
document.querySelectorAll('.of-df').forEach(function(cb){ out[cb.dataset.key]=cb.checked; });
return JSON.stringify(out);
}

function ofRenderExtraFields(fields){
var box=document.getElementById('of-extra-fields');if(!box)return;
box.innerHTML=fields.map(function(f,i){
return '<div class="flex items-center gap-2 mb-2 of-ef-row">'+
'<label class="flex items-center gap-1 flex-shrink-0"><input type="checkbox" class="of-ef-vis w-3.5 h-3.5" '+(f.visible?'checked':'')+' title="Показывать"></label>'+
'<input class="input-f text-xs of-ef-label flex-1" value="'+e(f.label||'')+'" placeholder="Название поля">'+
'<input class="input-f text-xs of-ef-value flex-1" value="'+e(f.value||'')+'" placeholder="Значение">'+
'<button type="button" onclick="this.closest(\'.of-ef-row\').remove()" class="text-red-400 hover:text-red-600 text-sm">&times;</button></div>';
}).join('');
}
function ofAddExtraField(){
var box=document.getElementById('of-extra-fields');if(!box)return;
box.insertAdjacentHTML('beforeend','<div class="flex items-center gap-2 mb-2 of-ef-row"><label class="flex items-center gap-1 flex-shrink-0"><input type="checkbox" class="of-ef-vis w-3.5 h-3.5" checked title="Показывать"></label><input class="input-f text-xs of-ef-label flex-1" placeholder="Название поля"><input class="input-f text-xs of-ef-value flex-1" placeholder="Значение"><button type="button" onclick="this.closest(\'.of-ef-row\').remove()" class="text-red-400 hover:text-red-600 text-sm">&times;</button></div>');
}
function ofCollectExtraFields(){
var fields=[];
document.querySelectorAll('.of-ef-row').forEach(function(row){
var label=row.querySelector('.of-ef-label')?.value?.trim()||'';
var value=row.querySelector('.of-ef-value')?.value?.trim()||'';
var visible=row.querySelector('.of-ef-vis')?.checked||false;
if(label)fields.push({label:label,value:value,visible:visible});
});
return JSON.stringify(fields);
}

const OF_DEBIT_CARD_TEMPLATE=[
{label:'Кэшбэк',value:'',visible:true},
{label:'Процент на остаток',value:'',visible:true},
{label:'Стоимость обслуживания',value:'',visible:true},
{label:'Снятие наличных',value:'',visible:true},
{label:'Переводы по СБП',value:'',visible:true},
{label:'Бонусы / мили',value:'',visible:true}
];
const OF_CREDIT_CARD_TEMPLATE=[
{label:'Кредитный лимит',value:'',visible:true},
{label:'Льготный период',value:'',visible:true},
{label:'Стоимость обслуживания',value:'',visible:true},
{label:'Кэшбэк',value:'',visible:true},
{label:'Минимальный платеж',value:'',visible:true},
{label:'Снятие наличных',value:'',visible:true}
];
const OF_CREDIT_TEMPLATE=[
{label:'Сумма кредита',value:'',visible:true},
{label:'Срок кредита',value:'',visible:true},
{label:'Ставка годовая',value:'',visible:true},
{label:'Ежемесячный платеж',value:'',visible:true},
{label:'Без справок',value:'',visible:true},
{label:'Досрочное погашение',value:'',visible:true}
];
function ofToggleExtraTemplateActions(){
var cat=document.getElementById('of-c');
var hint=document.getElementById('of-extra-template-hint');
if(!cat||!hint)return;
var labels={debit_cards:'Шаблон для дебетовой карты: кэшбэк, процент на остаток, обслуживание, снятие наличных, СБП, бонусы.',credit_cards:'Шаблон для кредитной карты: лимит, льготный период, обслуживание, кэшбэк, минимальный платеж, снятие наличных.',credits:'Шаблон для кредита: сумма, срок, ставка, ежемесячный платеж, без справок, досрочное погашение.'};
hint.textContent=labels[cat.value]||'Для этой категории шаблоны не предусмотрены. Доступны: кредиты, кредитные карты и дебетовые карты.';
}
function ofGetCurrentExtraFields(){
var current=[];
document.querySelectorAll('.of-ef-row').forEach(function(row){
  var label=row.querySelector('.of-ef-label')?.value?.trim()||'';
  var value=row.querySelector('.of-ef-value')?.value?.trim()||'';
  var visible=row.querySelector('.of-ef-vis')?.checked||false;
  if(label)current.push({label:label,value:value,visible:visible});
});
return current;
}
function ofApplyTemplate(template,merge){
var current=merge?ofGetCurrentExtraFields():[];
var existingMap={};
current.forEach(function(f){existingMap[(f.label||'').toLowerCase()]=true;});
var next=merge?current.slice():[];
template.forEach(function(f){
  if(!existingMap[(f.label||'').toLowerCase()]) next.push({label:f.label,value:f.value,visible:f.visible});
});
ofRenderExtraFields(next);
}
function ofApplyDebitCardTemplate(merge){ofApplyTemplate(OF_DEBIT_CARD_TEMPLATE,merge);}
function ofApplyCreditCardTemplate(merge){ofApplyTemplate(OF_CREDIT_CARD_TEMPLATE,merge);}
function ofApplyCreditTemplate(merge){ofApplyTemplate(OF_CREDIT_TEMPLATE,merge);}
function ofApplyCurrentCategoryTemplate(merge){
var cat=document.getElementById('of-c');
var v=cat?cat.value:'';
if(v==='debit_cards') return ofApplyDebitCardTemplate(merge);
if(v==='credit_cards') return ofApplyCreditCardTemplate(merge);
if(v==='credits') return ofApplyCreditTemplate(merge);
alert('Для категории "'+(cat&&cat.options[cat.selectedIndex]?cat.options[cat.selectedIndex].text:'')+'" готового шаблона пока нет.');
}

function oS(ev,id){ev.preventDefault();let d={title:document.getElementById('of-t').value,category:document.getElementById('of-c').value,amountMin:document.getElementById('of-am1').value,amountMax:document.getElementById('of-am2').value,termMinDays:document.getElementById('of-t1').value,termMaxDays:document.getElementById('of-t2').value,psk:document.getElementById('of-psk').value,rate:document.getElementById('of-r').value,rateUnit:document.getElementById('of-ru').value,freeTermDays:document.getElementById('of-fr').value,logoUrl:document.getElementById('of-lo').value,affiliateUrl:document.getElementById('of-af').value,borrowerCategory:document.getElementById('of-b').value,description:document.getElementById('of-de').value,seoKeywords:document.getElementById('of-sk').value,isActive:document.getElementById('of-ac').checked,sortOrder:document.getElementById('of-so').value,extraFields:ofCollectExtraFields(),displayFields:ofCollectDisplayFields(),phone:document.getElementById('of-phone').value,address:document.getElementById('of-address').value,trademark:document.getElementById('of-trademark').value,license:document.getElementById('of-license').value,kosmobonusEnabled:document.getElementById('of-kb-enabled').checked,kosmobonusAmount:parseInt(document.getElementById('of-kb-amount').value)||0,kosmobonusConditions:document.getElementById('of-kb-conditions').value};if(d.category==='debit_cards'){d.amountMin=0;d.amountMax=0;d.termMinDays=0;d.termMaxDays=0;d.psk='0';d.rate='0';d.rateUnit='year';d.freeTermDays=0;d.borrowerCategory='any';}ap(id?'/offers/'+id:'/offers',{method:id?'PUT':'POST',body:JSON.stringify(d)}).then(r=>{
var oid=id||r.id;
var tagIds=Array.from(document.querySelectorAll('.of-tag-cb:checked')).map(x=>Number(x.value));
return ap('/tag-links',{method:'POST',body:JSON.stringify({offerId:oid,tagIds:tagIds})});
}).then(()=>{cm();lO();});return false;}
function oD(id){if(confirm('Удалить?'))ap('/offers/'+id,{method:'DELETE'}).then(()=>lO());}

/* ============ ARTICLES ============ */
let aTopics=[],aAi={};
function lA(){ap('/generate-article').then(d=>{aTopics=d.topics||[];aAi=d.aiStatus||{};});
ap('/articles').then(list=>{let h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">Статьи ('+list.length+')</h2><div class="flex gap-2"><button onclick="bulkToggle(\'articles\')" id="bulk-articles-toggle" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">☑ Выбрать</button><button onclick="aGen()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🤖 Автогенерация</button><button onclick="aForm()" class="btn-p">+ Добавить</button></div></div>';
h+='<div class="bg-white rounded-xl shadow-sm border overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="px-4 py-3 w-8"></th><th class="text-left px-4 py-3">Заголовок</th><th class="text-left px-4 py-3">Дата</th><th class="text-left px-4 py-3">Публикация</th><th class="text-left px-4 py-3">Контент</th><th class="text-right px-4 py-3">Действия</th></tr></thead><tbody>';
list.forEach(a=>{h+='<tr class="border-b hover:bg-gray-50"><td class="px-4 py-1"><input type="checkbox" class="bulk-cb bulk-articles-cb w-4 h-4 hidden" data-id="'+a.id+'" onclick="event.stopPropagation();bulkUpdate(\'articles\')"></td><td class="px-4 py-3 font-medium">'+e(a.title)+'</td><td class="px-4 py-3 text-gray-500">'+new Date(a.created_at).toLocaleDateString('ru-RU')+'</td><td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(a.is_published?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700')+'">'+(a.is_published?'Опубликовано':'Черновик')+'</span></td><td class="px-4 py-3">'+articleStatusBadge(a.content_status||'draft', a.quality_score||0)+'</td><td class="px-4 py-3 text-right space-x-2"><a href="/articles/'+e(a.slug)+'" target="_blank" class="text-gray-400 hover:text-gray-600">👁</a> <button onclick=\'aForm('+JSON.stringify(a).replace(/\x27/g,"&#39;")+')\' class="text-blue-600 hover:underline text-sm">Ред.</button> <button onclick="aToggle('+a.id+','+(!a.is_published)+')" class="text-blue-500 hover:underline text-sm">'+(a.is_published?'Скрыть':'Опубл.')+'</button> <button onclick="aD('+a.id+')" class="text-red-500 hover:underline text-sm">Удалить</button></td></tr>';});
h+='</tbody></table></div>';document.getElementById('p-articles').innerHTML=h;});}

function aGen(){let cats='<option value="">Случайная</option>';aTopics.forEach(t=>{var avail=t.themes?t.themes.length:0;var total=t.total||avail;var used=t.used||0;var label=t.category.charAt(0).toUpperCase()+t.category.slice(1);if(avail>0)cats+='<option value="'+t.category+'">'+label+' ('+avail+' из '+total+' доступно)</option>';else cats+='<option value="'+t.category+'">'+label+' — темы закончились, AI создаст новые</option>';});
let textLabel=aAi.activeText||'нет';let imageLabel=aAi.activeImage||'нет';let badges='<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded ml-2">'+textLabel+'</span><span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded ml-1">'+imageLabel+'</span>';
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🤖 Автогенерация'+badges+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Категория</label><select id="ag-c" class="sel-f" onchange="agUpd()">'+cats+'</select></div><div><label class="block text-sm font-medium mb-1">Тема из списка</label><select id="ag-t" class="sel-f"><option value="">Случайная</option></select></div><div><label class="block text-sm font-medium mb-1">Или своя тема</label><input id="ag-cu" class="input-f" placeholder="Введите свою тему"></div><div class="bg-blue-50 p-3 rounded-lg text-sm text-blue-700"><p class="font-medium mb-1">ℹ️ Как работает:</p><p class="text-xs">Текст: '+textLabel+' • Картинка: '+imageLabel+' • Черновик • До 90 сек</p><p class="text-xs mt-1">🏦 МФО — обзор организации • Если темы закончились — AI сгенерирует новые автоматически</p></div><div class="bg-green-50 p-3 rounded-lg text-sm"><button type="button" onclick="agNewTopics()" id="ag-newtopics" class="text-green-800 font-semibold hover:underline">🔄 Сгенерировать 10 новых тем через AI</button><span id="ag-newtopics-status" class="ml-2 text-green-600"></span></div></div><div class="flex justify-end gap-3 mt-6"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="agDo()" id="ag-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-semibold">🚀 Сгенерировать</button></div>');}



function agUpd(){let c=document.getElementById('ag-c').value,s=document.getElementById('ag-t');s.innerHTML='<option value="">Случайная</option>';if(c){let g=aTopics.find(t=>t.category===c);if(g)g.themes.forEach(th=>{s.innerHTML+='<option value="'+th+'">'+th+'</option>';});}}

function agDo(){let cu=document.getElementById('ag-cu').value.trim(),tp=cu||document.getElementById('ag-t').value,ct=document.getElementById('ag-c').value,b=document.getElementById('ag-btn');b.disabled=true;b.textContent='⏳ Генерация...';
ap('/generate-article',{method:'POST',body:JSON.stringify({topic:tp||null,category:ct||null})}).then(d=>{cm();if(d.success){let im=d.hasImage?'\n📷 Обложка: '+(d.imageProvider||'есть'):'\n📷 Без обложки';let errNote=d.imageError?'\n⚠️ Картинка: '+d.imageError:'';let textErr=d.textError?'\n⚠️ Текст: '+d.textError:'';alert('Статья "'+d.article.title+'" создана!\n🤖 '+d.aiProvider+im+errNote+textErr);}else alert('Ошибка: '+(d.error||d.textError||'неизвестная'));lA();}).catch(()=>{alert('Ошибка');b.disabled=false;b.textContent='🚀 Сгенерировать';});}

function aForm(a){let f=a||{title:'',excerpt:'',content:'',meta_title:'',meta_description:'',cover_image:'',is_published:false,content_status:'draft',quality_score:0};let id=a?a.id:0;
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать статью':'Новая статья')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><form onsubmit="return aS(event,'+id+')"><div class="space-y-3"><div><label class="block text-xs font-medium mb-1">Заголовок *</label><input id="af-t" class="input-f" value="'+e(f.title)+'" required></div><div><label class="block text-xs font-medium mb-1">Краткое описание</label><textarea id="af-ex" class="input-f" rows="2">'+e(f.excerpt||'')+'</textarea></div><div><label class="block text-xs font-medium mb-1">Содержание *</label><div class="flex flex-wrap gap-2 mb-2"><button type="button" onclick="cqAnalyzeForm(&#39;af&#39;,&#39;article&#39;)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">🧪 Качество</button><button type="button" onclick="cqImproveField(&#39;af&#39;,&#39;article&#39;,&#39;content&#39;)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Улучшить</button><button type="button" onclick="cqImproveField(&#39;af&#39;,&#39;article&#39;,&#39;content&#39;,80,3)" style="background:#059669;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🎯 До 80+</button><button type="button" onclick="cqImproveField(&#39;af&#39;,&#39;article&#39;,&#39;content&#39;,90,4)" style="background:#0f766e;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🚀 До 90+</button><button type="button" onclick="cqCleanupOnly(&#39;af&#39;,&#39;article&#39;,&#39;content&#39;)" style="background:#475569;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🧹 Только мусор</button></div><textarea id="af-co" class="input-f" rows="10" required>'+e(f.content)+'</textarea></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Meta Title</label><div class="flex gap-2"><input id="af-mt" class="input-f flex-1" value="'+e(f.meta_title||'')+'"><button type="button" id="af-meta-btn" onclick="fillMeta(&quot;af&quot;,&quot;article&quot;)" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap">🤖 Meta</button></div></div><div><label class="block text-xs font-medium mb-1">Обложка</label><div class="flex gap-2"><input id="af-ci" class="input-f flex-1" value="'+e(f.cover_image||'')+'"><button type="button" onclick="mediaPicker(\'af-ci\',\'articles\')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap">📁 Выбрать</button><button type="button" onclick="aiRegenImage('+id+')" id="af-ai-img-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-xs font-semibold whitespace-nowrap"'+(id?'':' disabled title="Сначала сохраните статью"')+'>🤖 AI картинка</button></div><div id="af-ci-preview" class="mt-1">'+(f.cover_image?'<img src="'+e(f.cover_image)+'" class="w-20 h-12 object-cover rounded border">':'')+'</div></div></div><div><label class="block text-xs font-medium mb-1">Meta Description</label><textarea id="af-md" class="input-f" rows="2">'+e(f.meta_description||'')+'</textarea></div><div><label class="flex items-center gap-2"><input type="checkbox" id="af-pu" '+(f.is_published?'checked':'')+' class="w-4 h-4"><span class="text-sm">Опубликовать</span></label></div><div><label class="block text-xs font-medium mb-1">Статус контента</label><select id="af-status" class="sel-f" onchange="this.dataset.userchanged=&#39;1&#39;"><option value="draft"'+((f.content_status||'draft')==='draft'?' selected':'')+'>Черновик</option><option value="reviewed"'+((f.content_status||'draft')==='reviewed'?' selected':'')+'>Проверено</option><option value="ready"'+((f.content_status||'draft')==='ready'?' selected':'')+'>Готово к публикации</option></select><p class="text-xs text-gray-400 mt-1">Текущий score: <span id="af-quality-score">'+(f.quality_score||0)+'</span></p><span id="af-quality-badge" class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2.5 py-1 text-xs font-semibold mt-2">score '+(f.quality_score||0)+'</span></div></div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>'); setTimeout(function(){ cqSetInlineScore('af', f.quality_score||0); }, 0);}

function articleStatusBadge(status, score){
  var map={draft:['bg-gray-100 text-gray-700','Черновик'],reviewed:['bg-yellow-100 text-yellow-700','Проверено'],ready:['bg-green-100 text-green-700','Готово']};
  var cfg=map[status]||map.draft;
  return '<div class="flex items-center gap-2"><span class="px-2 py-0.5 rounded text-xs font-semibold '+cfg[0]+'">'+cfg[1]+'</span><span class="text-xs text-gray-400">'+Number(score||0)+'/100</span></div>';
}

function aS(ev,id){
  ev.preventDefault();
  let statusEl=document.getElementById('af-status');
  let qualityEl=document.getElementById('af-quality-score');
  let d={title:document.getElementById('af-t').value,excerpt:document.getElementById('af-ex').value,content:document.getElementById('af-co').value,metaTitle:document.getElementById('af-mt').value,metaDescription:document.getElementById('af-md').value,coverImage:document.getElementById('af-ci').value,isPublished:document.getElementById('af-pu').checked};
  let saveBtn=ev.target.querySelector('button[type="submit"]');
  if(saveBtn){ saveBtn.disabled=true; saveBtn.textContent='⏳ Проверка...'; }
  ap('/content-quality',{method:'POST',body:JSON.stringify({action:'analyze',entity:'article',title:d.title,description:d.excerpt,content:d.content})}).then(function(res){
    if(res.error){ throw new Error(res.error); }
    var score=Number(res.analysis?.score||0);
    var recommended=score>=80?'ready':(score>=60?'reviewed':'draft');
    d.qualityScore=score;
    d.contentStatus=statusEl && statusEl.value ? statusEl.value : recommended;
    if(statusEl && !statusEl.dataset.userchanged){ statusEl.value=recommended; d.contentStatus=recommended; }
    if(qualityEl) qualityEl.textContent=String(score);
    if(d.isPublished && score < 60){
      var issues=(res.analysis?.issues||[]).map(function(i){return '• '+i.msg;}).join('\n');
      if(!confirm('Качество контента низкое ('+score+'/100).\n\nПроблемы:\n'+issues+'\n\nСохранить и опубликовать всё равно?')){
        if(saveBtn){ saveBtn.disabled=false; saveBtn.textContent='Сохранить'; }
        return null;
      }
    }
    if(saveBtn){ saveBtn.textContent='⏳ Сохранение...'; }
    return ap(id?'/articles/'+id:'/articles',{method:id?'PUT':'POST',body:JSON.stringify(d)});
  }).then(function(resp){
    if(resp===null) return;
    cm();
    lA();
  }).catch(function(err){
    alert('Ошибка: '+(err&&err.message?err.message:'Ошибка проверки качества'));
  }).finally(function(){
    if(saveBtn){ saveBtn.disabled=false; saveBtn.textContent='Сохранить'; }
  });
  return false;
}
function aToggle(id,v){ap('/articles/'+id,{method:'PUT',body:JSON.stringify({isPublished:v})}).then(()=>lA());}
function aD(id){if(confirm('Удалить?'))ap('/articles/'+id,{method:'DELETE'}).then(()=>lA());}

function fillMeta(prefix, entity, extra){
var payload=Object.assign({entity:entity}, extra||{});
var titleEl=document.getElementById(prefix+'-t') || document.getElementById(prefix+'-name');
var h1El=document.getElementById(prefix+'-h1');
var descEl=document.getElementById(prefix+'-desc') || document.getElementById(prefix+'-ex');
var contentEl=document.getElementById(prefix+'-co') || document.getElementById(prefix+'-content') || document.getElementById(prefix+'-text');
if(titleEl) payload.title=titleEl.value;
if(h1El) payload.h1=h1El.value;
if(descEl) payload.description=descEl.value;
if(contentEl) payload.content=contentEl.value;
var btn=document.getElementById(prefix+'-meta-btn');
if(btn){btn.disabled=true;btn.textContent='⏳ Генерация...';}
return ap('/meta-generate',{method:'POST',body:JSON.stringify(payload)}).then(function(d){
  if(btn){btn.disabled=false;btn.textContent='🤖 Meta';}
  if(d.error){alert(d.error);return;}
  var mt=document.getElementById(prefix+'-mt');
  var md=document.getElementById(prefix+'-md');
  if(mt) mt.value=d.metaTitle||'';
  if(md) md.value=d.metaDescription||'';
}).catch(function(){ if(btn){btn.disabled=false;btn.textContent='🤖 Meta';} alert('Ошибка генерации'); });
}

/* ============ REVIEWS ============ */
function lR(){ap('/reviews').then(list=>{let pend=list.filter(r=>!r.is_approved).length;let h='<div class="flex justify-between mb-6"><div class="flex items-center gap-4"><h2 class="text-xl font-bold">Отзывы ('+list.length+')</h2>'+(pend?'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-sm">'+pend+' на модерации</span>':'')+'</div><button onclick="rGen()" class="btn-p">🤖 Сгенерировать</button></div><div class="space-y-3">';
list.forEach(r=>{let st='';for(let i=1;i<=5;i++)st+='<span class="'+(i<=r.rating?'text-yellow-400':'text-gray-300')+'">★</span>';
h+='<div class="bg-white rounded-xl border p-4 '+(r.is_approved?'':'border-yellow-200 bg-yellow-50/50')+'"><div class="flex justify-between"><div class="flex-1"><div class="flex items-center gap-2 mb-1"><span class="font-semibold">'+e(r.author_name)+'</span><span>'+st+'</span><span class="text-xs text-gray-400">'+new Date(r.created_at).toLocaleDateString('ru-RU')+'</span></div><p class="text-sm text-gray-500">'+e(r.offer_title||'—')+'</p><p class="text-gray-700 mt-1">'+e(r.comment)+'</p></div><div class="flex flex-col gap-1 ml-4">'+(r.is_approved?'<button onclick="rA('+r.id+',false)" class="text-sm bg-gray-100 px-3 py-1 rounded">Скрыть</button>':'<button onclick="rA('+r.id+',true)" class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded">✓ Одобрить</button>')+'<button onclick="rD('+r.id+')" class="text-sm text-red-500">Удалить</button></div></div></div>';});
h+='</div>';document.getElementById('p-reviews').innerHTML=h;});}
function rGen(){
ap('/offers').then(list=>{
  let opts='<option value="">Случайный оффер</option>';
  (list||[]).forEach(o=>{ opts+='<option value="'+o.id+'">'+e(o.title)+'</option>'; });
  modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🤖 Генерация отзыва</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
  '<div class="space-y-4">'+
  '<div><label class="block text-sm font-medium mb-1">На какой оффер сгенерировать отзыв?</label><select id="rg-offer" class="sel-f">'+opts+'</select><p class="text-xs text-gray-400 mt-1">Можно выбрать конкретный оффер или оставить случайный вариант.</p></div>'+
  '<div class="flex justify-end gap-3 pt-2"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="rGenDo()" id="rg-btn" class="btn-p">Сгенерировать</button></div></div>');
});
}
function rGenDo(){
  let btn=document.getElementById('rg-btn');
  let offerId=document.getElementById('rg-offer')?.value||'';
  btn.disabled=true; btn.textContent='⏳ Генерация...';
  ap('/generate-review',{method:'POST',body:JSON.stringify({offerId:offerId||null})}).then(d=>{
    if(d.success){ cm(); alert(d.review.name+' → '+d.review.offer+' ('+d.review.rating+'/5)'); lR(); }
    else { btn.disabled=false; btn.textContent='Сгенерировать'; alert(d.error||'Ошибка'); }
  }).catch(()=>{ btn.disabled=false; btn.textContent='Сгенерировать'; alert('Ошибка'); });
}
function rA(id,v){ap('/reviews/'+id,{method:'PUT',body:JSON.stringify({isApproved:v})}).then(()=>lR());}
function rD(id){if(confirm('Удалить?'))ap('/reviews/'+id,{method:'DELETE'}).then(()=>lR());}

/* ============ DRAG & DROP SORT ============ */
function initSort(containerId, tableName){
var el=document.getElementById(containerId);
if(!el||!window.Sortable)return;
new Sortable(el,{
handle:'.drag-handle',
animation:200,
ghostClass:'opacity-30',
onEnd:function(){
var ids=Array.from(el.children).map(function(c){return Number(c.dataset.id);}).filter(Boolean);
ap('/reorder',{method:'POST',body:JSON.stringify({table:tableName,ids:ids})}).then(function(d){
if(!d.success)alert('Ошибка сортировки');
});
}
});
}

/* ============ CATEGORIES ============ */
function lCats(){ap('/categories').then(cats=>{
var h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">📂 Категории и подкатегории</h2><button onclick="catForm()" class="btn-p text-sm">+ Добавить</button></div>';
h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-700">Перетаскивайте за ☰. Верхний блок влияет на порядок категорий. Ниже можно отдельно упорядочить, что показывать в футере в разделах «Продукты» и «Инструменты».</div>';

var parents=cats.filter(c=>!c.parent_id).sort((a,b)=>(a.sort_order-b.sort_order)||(a.id-b.id));
var children=cats.filter(c=>c.parent_id).sort((a,b)=>(a.sort_order-b.sort_order)||(a.id-b.id));

if(!cats.length){h+='<p class="text-gray-500 text-center py-8">Нет категорий</p>';}
else{
// Общий список категорий
h+='<div class="bg-white rounded-xl border p-4 mb-6"><h3 class="font-semibold text-gray-900 mb-3">Все категории</h3><div id="cats-parent-sortable" class="space-y-3">';
parents.forEach(c=>{
var subs=children.filter(s=>Number(s.parent_id)===Number(c.id));
h+='<div class="bg-white rounded-xl border p-4" data-id="'+c.id+'" data-parent-id="" data-footer-section="'+(c.footer_section||'products')+'">';
h+='<div class="flex items-center gap-3">';
h+='<span class="text-gray-300 cursor-grab drag-handle text-lg">☰</span>';
h+='<span class="text-xl">'+(c.icon||'📁')+'</span>';
h+='<div class="flex-1 min-w-0"><p class="font-semibold text-gray-900 text-sm">'+e(c.name)+'</p><p class="text-xs text-gray-500 font-mono">/'+e(c.slug)+'</p></div>';
h+='<div class="flex items-center gap-2 text-xs flex-wrap">';
h+=(c.show_in_header?'<span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded">Шапка</span>':'');
h+=(c.show_in_footer?'<span class="bg-green-50 text-green-600 px-2 py-0.5 rounded">Футер:'+((c.footer_section||'products')==='tools'?'Инструменты':'Продукты')+'</span>':'');
h+='<span class="'+(c.is_active?'text-green-600':'text-gray-400')+'">'+(c.is_active?'Вкл':'Выкл')+'</span>';
h+='<a href="/'+e(c.slug)+'" target="_blank" class="text-gray-400 hover:text-blue-600">👁</a>';
h+='<button onclick="catForm('+JSON.stringify(c).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline">Ред.</button>';
h+='<button onclick="catDel('+c.id+')" class="text-red-500 hover:underline">Уд.</button>';
h+='</div></div>';
if(subs.length){
h+='<div class="ml-8 mt-3 space-y-2 border-l-2 border-gray-100 pl-4 cats-child-sortable" data-parent-id="'+c.id+'">';
subs.forEach(s=>{
h+='<div class="flex items-center gap-2 text-sm bg-gray-50 rounded-lg border p-3" data-id="'+s.id+'" data-parent-id="'+c.id+'" data-footer-section="'+(s.footer_section||'products')+'">';
h+='<span class="text-gray-300 cursor-grab drag-handle text-base">☰</span>';
h+='<span>'+(s.icon||'📄')+'</span><span class="font-medium">'+e(s.name)+'</span><span class="text-gray-400 font-mono text-xs">/'+e(s.slug)+'</span>';
h+=(s.show_in_header?'<span class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded text-xs">Шапка</span>':'');
h+=(s.show_in_footer?'<span class="bg-green-50 text-green-600 px-1.5 py-0.5 rounded text-xs">Футер:'+((s.footer_section||'products')==='tools'?'Инстр.':'Прод.')+'</span>':'');
h+='<a href="/'+e(s.slug)+'" target="_blank" class="text-gray-400 hover:text-blue-600 text-xs">👁</a>';
h+='<button onclick="catForm('+JSON.stringify(s).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-xs">Ред.</button>';
h+='<button onclick="catDel('+s.id+')" class="text-red-500 hover:underline text-xs">Уд.</button>';
h+='</div>';});
h+='</div>';}
h+='</div>';});
h+='</div></div>';

// Футер: Продукты
var footerProducts=parents.filter(c=>c.show_in_footer && (c.footer_section||'products')==='products');
h+='<div class="bg-white rounded-xl border p-4 mb-6"><h3 class="font-semibold text-gray-900 mb-3">Футер → Продукты</h3><div id="footer-products-sortable" class="space-y-2">';
footerProducts.forEach(c=>{h+='<div class="flex items-center gap-3 bg-gray-50 rounded-lg border p-3" data-id="'+c.id+'" data-parent-id="" data-footer-section="products"><span class="text-gray-300 cursor-grab drag-handle text-base">☰</span><span>'+(c.icon||'📁')+'</span><span class="font-medium">'+e(c.name)+'</span><span class="text-gray-400 font-mono text-xs">/'+e(c.slug)+'</span></div>';});
h+='</div></div>';

// Футер: Инструменты
var footerTools=parents.filter(c=>c.show_in_footer && (c.footer_section||'products')==='tools');
h+='<div class="bg-white rounded-xl border p-4 mb-6"><h3 class="font-semibold text-gray-900 mb-3">Футер → Инструменты</h3><div id="footer-tools-sortable" class="space-y-2">';
footerTools.forEach(c=>{h+='<div class="flex items-center gap-3 bg-gray-50 rounded-lg border p-3" data-id="'+c.id+'" data-parent-id="" data-footer-section="tools"><span class="text-gray-300 cursor-grab drag-handle text-base">☰</span><span>'+(c.icon||'🧰')+'</span><span class="font-medium">'+e(c.name)+'</span><span class="text-gray-400 font-mono text-xs">/'+e(c.slug)+'</span></div>';});
h+='</div></div>';
}
document.getElementById('p-cats').innerHTML=h;
initCategorySort();});}

function initCategorySort(){
var parentEl=document.getElementById('cats-parent-sortable');
if(parentEl&&window.Sortable){ new Sortable(parentEl,{handle:'.drag-handle',animation:200,ghostClass:'opacity-30',onEnd:saveCategorySort}); }
document.querySelectorAll('.cats-child-sortable').forEach(function(el){ if(window.Sortable){ new Sortable(el,{handle:'.drag-handle',animation:200,ghostClass:'opacity-30',onEnd:saveCategorySort}); }});
var fp=document.getElementById('footer-products-sortable'); if(fp&&window.Sortable){ new Sortable(fp,{handle:'.drag-handle',animation:200,ghostClass:'opacity-30',onEnd:saveCategorySort}); }
var ft=document.getElementById('footer-tools-sortable'); if(ft&&window.Sortable){ new Sortable(ft,{handle:'.drag-handle',animation:200,ghostClass:'opacity-30',onEnd:saveCategorySort}); }
}

function saveCategorySort(){
var items=[];
document.querySelectorAll('#cats-parent-sortable > [data-id]').forEach(function(parent){
  items.push({id:Number(parent.dataset.id), parent_id:null, footer_section:parent.dataset.footerSection||'products'});
  parent.querySelectorAll('.cats-child-sortable > [data-id]').forEach(function(child){
    items.push({id:Number(child.dataset.id), parent_id:Number(child.dataset.parentId||parent.dataset.id), footer_section:child.dataset.footerSection||'products'});
  });
});
// Отдельно обновляем порядок в футере по группам
(document.querySelectorAll('#footer-products-sortable > [data-id]')||[]).forEach(function(el, idx){
  items.push({id:Number(el.dataset.id), parent_id:null, footer_section:'products', sort_hint:idx});
});
(document.querySelectorAll('#footer-tools-sortable > [data-id]')||[]).forEach(function(el, idx){
  items.push({id:Number(el.dataset.id), parent_id:null, footer_section:'tools', sort_hint:idx});
});
ap('/categories/reorder',{method:'POST',body:JSON.stringify({items:items})}).then(function(r){if(!r.success)alert(r.error||'Ошибка сортировки');});
}

function catForm(c){
var f=c||{name:'',slug:'',icon:'',h1:'',description:'',meta_title:'',meta_description:'',seo_text:'',parent_id:null,show_in_header:true,show_in_footer:true,field_templates:'',is_active:true,sort_order:0};
var id=c?c.id:0;
// Загружаем список родительских категорий
ap('/categories').then(cats=>{
var parentOpts='<option value="">— Нет (корневая) —</option>';
cats.filter(cc=>!cc.parent_id&&(!id||cc.id!==id)).forEach(cc=>{parentOpts+='<option value="'+cc.id+'"'+(Number(f.parent_id)===Number(cc.id)?' selected':'')+'>'+(cc.icon||'')+' '+cc.name+'</option>';});
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать':'Новая категория')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return catSave(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div><label class="block text-xs font-medium mb-1">Иконка</label><input id="cat-icon" class="input-f" value="'+e(f.icon||'')+'"></div>'+

'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="cat-name" class="input-f" value="'+e(f.name)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Slug (URL)</label><input id="cat-slug" class="input-f" value="'+e(f.slug)+'" placeholder="авто"></div>'+
'<div><label class="block text-xs font-medium mb-1">Родитель</label><select id="cat-parent" class="sel-f">'+parentOpts+'</select></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">H1 заголовок</label><input id="cat-h1" class="input-f" value="'+e(f.h1||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Описание</label><input id="cat-desc" class="input-f" value="'+e(f.description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Meta Title</label><div class="flex gap-2"><input id="cat-mt" class="input-f flex-1" value="'+e(f.meta_title||'')+'"><button type="button" id="cat-meta-btn" onclick="fillMeta(&quot;cat&quot;,&quot;category&quot;,{categoryName:document.getElementById(&quot;cat-name&quot;).value||document.getElementById(&quot;cat-h1&quot;).value})" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap">🤖 Meta</button></div></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="cat-md" class="input-f" value="'+e(f.meta_description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO-текст (HTML)</label><textarea id="cat-seo" class="input-f text-xs" rows="4">'+e(f.seo_text||'')+'</textarea></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Шаблон доп. полей для офферов <span class="text-gray-400">(JSON)</span></label><textarea id="cat-field-tpl" class="input-f font-mono text-xs" rows="3" placeholder=\'[{"label":"Кэшбэк","visible":true}]\'>'+e(f.field_templates||'')+'</textarea><p class="text-xs text-gray-400 mt-1">При создании оффера в этой категории поля подставятся автоматически</p></div>'+'<div><label class="flex items-center gap-2"><input type="checkbox" id="cat-header" '+(f.show_in_header?'checked':'')+' class="w-4 h-4"><span class="text-sm">В шапке</span></label></div>'+
'<div><label class="flex items-center gap-2"><input type="checkbox" id="cat-footer" '+(f.show_in_footer?'checked':'')+' class="w-4 h-4"><span class="text-sm">В футере</span></label></div>'+'<div><label class="block text-xs font-medium mb-1">Раздел футера</label><select id="cat-footer-section" class="sel-f"><option value="products"'+((f.footer_section||'products')==='products'?' selected':'')+'>Продукты</option><option value="tools"'+((f.footer_section||'products')==='tools'?' selected':'')+'>Инструменты</option></select></div>'+
'<div class="col-span-2"><label class="flex items-center gap-2"><input type="checkbox" id="cat-active" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активна</span></label></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
});}

function catSave(ev,id){ev.preventDefault();
var d={id:id,name:document.getElementById('cat-name').value,slug:document.getElementById('cat-slug').value,icon:document.getElementById('cat-icon').value,h1:document.getElementById('cat-h1').value,description:document.getElementById('cat-desc').value,metaTitle:document.getElementById('cat-mt').value,metaDescription:document.getElementById('cat-md').value,seoText:document.getElementById('cat-seo').value,parentId:document.getElementById('cat-parent').value||null,showInHeader:document.getElementById('cat-header').checked,showInFooter:document.getElementById('cat-footer').checked,fieldTemplates:document.getElementById('cat-field-tpl').value,footerSection:document.getElementById('cat-footer-section').value,isActive:document.getElementById('cat-active').checked};
ap(id?'/categories/'+id:'/categories',{method:id?'PUT':'POST',body:JSON.stringify(d)}).then(r=>{if(r.error){alert(r.error);return;}cm();lCats();});return false;}

function catDel(id){if(confirm('Удалить категорию?'))ap('/categories/'+id,{method:'DELETE'}).then(()=>lCats());}

/* ============ TAGS ============ */
var TG_CAT={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
var TG_CAT_URLS={microloans:'/zajmy',credits:'/kredity',credit_cards:'/karty/kreditnye',debit_cards:'/karty/debetovye'};
function tUrl(t){return (TG_CAT_URLS[t.category]||'/zajmy')+'/type/'+t.slug;}

function lT(){ap('/tags').then(tags=>{
var h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">🏷️ Теги / Типы предложений ('+tags.length+')</h2><div class="flex gap-2"><button onclick="bulkToggle(\'tags\')" id="bulk-tags-toggle" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">☑ Выбрать</button><button onclick="tForm()" class="btn-p">+ Добавить</button></div></div>';
if(!tags.length){h+='<p class="text-gray-500 text-center py-8">Нет тегов. Добавьте первый!</p>';}
else{
h+='<div class="bg-gray-50 rounded-lg p-2 mb-4 text-xs text-gray-500">💡 Перетаскивайте за ☰ для изменения порядка</div>';
h+='<div id="tags-sortable" class="space-y-2">';
tags.forEach(t=>{
h+='<div class="bg-white rounded-xl border p-4 flex items-center gap-4 cursor-move hover:shadow-sm transition-shadow" data-id="'+t.id+'">';
h+='<input type="checkbox" class="bulk-cb bulk-tags-cb w-4 h-4 hidden" data-id="'+t.id+'" onclick="event.stopPropagation();bulkUpdate(\'tags\')">';h+='<span class="text-gray-300 cursor-grab drag-handle text-lg">☰</span>';
h+='<span class="text-xl">'+(t.icon||'🏷️')+'</span>';
h+='<div class="flex-1 min-w-0"><p class="font-semibold text-gray-900 text-sm">'+e(t.title)+'</p><p class="text-xs text-gray-500">'+(TG_CAT[t.category]||t.category)+' • <span class="font-mono">'+e(t.slug)+'</span></p></div>';
h+='<span class="px-2 py-0.5 rounded text-xs font-semibold '+(t.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(t.is_active?'Вкл':'Выкл')+'</span>';
h+='<a href="'+tUrl(t)+'" target="_blank" onclick="event.stopPropagation()" class="text-gray-400 hover:text-blue-600 text-sm" title="Открыть на сайте">👁</a>';
h+='<button onclick="event.stopPropagation();tPreview('+JSON.stringify(t).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-purple-600 hover:underline text-sm">Превью</button>';
h+='<button onclick="event.stopPropagation();tForm('+JSON.stringify(t).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-sm">Ред.</button>';
h+='<button onclick="event.stopPropagation();tD('+t.id+')" class="text-red-500 hover:underline text-sm">Уд.</button>';
h+='</div>';});
h+='</div>';}
h+='<div class="bg-gray-50 rounded-xl p-4 mt-6"><p class="text-sm text-gray-500">💡 Теги создают SEO-страницы. Пример: <code>/zajmy/type/bez-otkaza</code></p></div>';
document.getElementById('p-tags').innerHTML=h;
initSort('tags-sortable','offer_tags');});}

function tgSuggestIconPool(title, category){
  var t=(title||'').toLowerCase().trim();
  var pools=[];
  var rules=[
    ['кэшбэк',['💸','🎁','🪙','🛍️']],['кешбек',['💸','🎁','🪙','🛍️']],
    ['бонус',['🎁','🎉','🏆','✨']],['льгот',['🗓️','⏳','📅','🆓']],['грейс',['🗓️','⏳','📅','🆓']],
    ['без процент',['🆓','0️⃣','✨','🎯']],['0%',['🆓','0️⃣','✨','🎯']],['под 0',['🆓','0️⃣','✨','🎯']],
    ['без отказ',['✅','🟢','👍','🔓']],['одобрени',['✅','👍','📩','🟢']],
    ['студент',['🎓','📚','🧑‍🎓','📝']],['пенсион',['👴','👵','🏦','💳']],
    ['на карту',['💳','📲','🏧','⚡']],['сроч',['⚡','🚀','⏱️','💨']],['быстр',['⚡','🚀','⏱️','💨']],
    ['плохой кредитной истории',['📊','🔍','🧾','✅']],['плохой ки',['📊','🔍','🧾','✅']],
    ['рефинанс',['♻️','🔄','💱','📉']],['наличными',['💵','💰','🏦','📄']],
    ['дебет',['🪪','💳','💸','📲']],['кредитн',['💳','🏦','🪙','📈']],
    ['ипотек',['🏠','🔑','🏗️','📄']],['вклад',['🏦','📈','💎','🔒']],['страхов',['🛡️','📋','✅','🔒']],['авто',['🚗','🛣️','🔑','⛽']]
  ];
  for(var i=0;i<rules.length;i++){
    if(t.includes(rules[i][0])) { pools = rules[i][1].slice(); break; }
  }
  if(!pools.length){
    if(category==='microloans') pools=['💵','⚡','📱','✅'];
    else if(category==='credits') pools=['🏦','💰','📄','📈'];
    else if(category==='credit_cards') pools=['💳','🗓️','💸','🎁'];
    else if(category==='debit_cards') pools=['🪪','💸','📲','🏦'];
    else pools=['🏷️','⭐','📌','✨'];
  }
  return pools;
}
function tgSuggestIcon(title, category){
  var pool=tgSuggestIconPool(title, category);
  return pool[0]||'🏷️';
}
function tgAutoIcon(cycle){
  var title=document.getElementById('tg-title')?.value||'';
  var cat=document.getElementById('tg-cat')?.value||'microloans';
  var el=document.getElementById('tg-icon');
  if(!el) return;
  var pool=tgSuggestIconPool(title, cat);
  if(!pool.length){ el.value='🏷️'; return; }
  if(cycle){
    var current=(el.value||'').trim();
    var idx=pool.indexOf(current);
    el.value = idx===-1 ? pool[0] : pool[(idx+1)%pool.length];
  } else {
    if(!el.value || el.value.trim()==='' || !el.dataset.manual){
      el.value=pool[0];
    }
  }
}

function tgAutoFeatures(){
  var title=(document.getElementById('tg-title')?.value||'').trim();
  var category=document.getElementById('tg-cat')?.value||'microloans';
  if(!title){ alert('Сначала заполните название тега'); return; }
  var t=title.toLowerCase();

  // Пул иконок по темам
  var iconPool={
    speed:['⚡','🚀','💨','🏃'],
    money:['💰','💵','💲','🤑'],
    card:['💳','🪪','📲','🏧'],
    safe:['🛡️','🔒','✅','🏛️'],
    gift:['🎁','🎉','💸','🎊'],
    time:['⏰','🗓️','📅','⌛'],
    doc:['📋','📄','📝','✍️'],
    people:['👥','🧑','👤','🤝'],
    calc:['🧮','📊','📈','🔢'],
    online:['📱','💻','🌐','📡'],
    free:['🆓','0️⃣','🎯','✨'],
    star:['⭐','🌟','💎','🏆']
  };

  function pickIcon(pool, used){
    for(var i=0;i<pool.length;i++){ if(used.indexOf(pool[i])===-1) return pool[i]; }
    return pool[0];
  }

  var usedIcons=[];
  var features=[];

  function addFeat(pools, titleText, descText){
    var icon='📌';
    for(var p=0;p<pools.length;p++){
      var pool=iconPool[pools[p]]||[];
      var picked=pickIcon(pool, usedIcons);
      if(picked && usedIcons.indexOf(picked)===-1){ icon=picked; break; }
    }
    usedIcons.push(icon);
    features.push({icon:icon, title:titleText, text:descText});
  }

  // Генерируем 4 фичи на основе названия тега и категории
  // Фича 1: главное преимущество по названию
  if(t.includes('без отказ')) addFeat(['safe','star'],'Высокий шанс одобрения','Подборка предложений с лояльными требованиями к «'+title+'»');
  else if(t.includes('без процент')||t.includes('0%')||t.includes('под 0')) addFeat(['free','gift'],'Без процентов','Акции для новых клиентов по теме «'+title+'»');
  else if(t.includes('кэшбэк')||t.includes('кешбек')) addFeat(['gift','money'],'Кэшбэк','Возврат части расходов по запросу «'+title+'»');
  else if(t.includes('льгот')||t.includes('грейс')) addFeat(['time','free'],'Льготный период','Используйте средства без процентов по «'+title+'»');
  else if(t.includes('студент')) addFeat(['people','star'],'Для студентов','Специальные условия по запросу «'+title+'»');
  else if(t.includes('пенсион')) addFeat(['people','safe'],'Для пенсионеров','Предложения с повышенным возрастным лимитом');
  else if(t.includes('на карту')) addFeat(['card','speed'],'Деньги на карту','Мгновенный перевод по «'+title+'»');
  else if(t.includes('сроч')) addFeat(['speed','time'],'Срочное оформление','Быстрое решение по «'+title+'»');
  else if(t.includes('наличн')) addFeat(['money','doc'],'Наличными','Выдача средств по запросу «'+title+'»');
  else if(t.includes('рефинанс')) addFeat(['calc','money'],'Рефинансирование','Снижение нагрузки по «'+title+'»');
  else if(t.includes('ипотек')) addFeat(['money','doc'],'Ипотека','Предложения по «'+title+'»');
  else addFeat(['star','safe'],''+title,'Подборка лучших предложений');

  // Фича 2: удобство
  if(features.length<2){
    if(t.includes('онлайн')||t.includes('на карту')) addFeat(['online','speed'],'Онлайн-оформление','Подача заявки без визита в офис');
    else addFeat(['online','card'],'Удобная подача','Заявка онлайн за несколько минут');
  }

  // Фича 3: сравнение
  if(features.length<3){
    addFeat(['calc','doc'],'Сравнение условий','Ставки, суммы и сроки в одном месте');
  }

  // Фича 4: по категории
  if(features.length<4){
    if(category==='microloans') addFeat(['speed','money'],'Быстрое решение','Одобрение заявки за 5-15 минут');
    else if(category==='credits') addFeat(['money','doc'],'Прозрачные условия','ПСК и ставка указаны для каждого предложения');
    else if(category==='credit_cards') addFeat(['time','card'],'Грейс-период','Льготный период на покупки');
    else if(category==='debit_cards') addFeat(['gift','card'],'Бонусная программа','Кэшбэк и проценты на остаток');
    else addFeat(['safe','star'],'Проверенные партнёры','Все организации в реестре ЦБ РФ');
  }

  document.getElementById('tg-feat').value = JSON.stringify(features, null, 2);
}

function tPreview(t){
var url=tUrl(t);
var feat=t.features||'[]';if(typeof feat==='string')try{feat=JSON.parse(feat);}catch(x){feat=[];}
var catLabel=TG_CAT[t.category]||'Предложения';
var h='<div class="flex justify-between items-start mb-4"><h3 class="text-lg font-bold">Предпросмотр: '+e(t.title)+'</h3><div class="flex gap-2"><a href="'+url+'" target="_blank" class="text-sm bg-blue-50 text-blue-600 px-3 py-1 rounded hover:bg-blue-100">Открыть ↗</a><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div></div>';
h+='<div class="text-xs text-gray-400 mb-3 font-mono bg-gray-50 rounded px-3 py-1">'+location.origin+url+'</div>';
h+='<div class="border rounded-xl bg-white">';
h+='<div class="px-6 pt-4 text-sm text-gray-400">Главная &rarr; '+catLabel+' &rarr; '+e(t.title)+'</div>';
h+='<div class="px-6 pt-3"><h1 class="text-2xl font-bold text-gray-900">'+(e(t.h1)||e(t.title))+'</h1></div>';
if(t.description)h+='<div class="px-6 pt-2 text-gray-600">'+e(t.description)+'</div>';
if(feat&&feat.length){h+='<div class="px-6 pt-4 grid grid-cols-2 md:grid-cols-4 gap-3">';feat.forEach(function(f){h+='<div class="bg-gray-50 rounded-xl border p-3 text-center"><span class="text-xl block mb-1">'+(f.icon||'📌')+'</span><p class="font-semibold text-xs">'+e(f.title||'')+'</p><p class="text-xs text-gray-400">'+e(f.text||'')+'</p></div>';});h+='</div>';}
h+='<div class="px-6 pt-4 pb-2 text-sm text-gray-500">Предложения:</div><div class="px-6 space-y-2 pb-4">';
for(var i=0;i<3;i++){h+='<div class="bg-gray-50 rounded-lg border p-4 flex items-center gap-3"><div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center text-lg">🏦</div><div class="flex-1"><div class="h-3 bg-gray-200 rounded w-32 mb-2"></div><div class="h-2 bg-gray-100 rounded w-48"></div></div><div class="bg-green-500 text-white px-3 py-1.5 rounded text-xs">Оформить</div></div>';}
h+='</div>';
if(t.content)h+='<div class="px-6 py-4 border-t prose prose-sm text-gray-600">'+e(t.content)+'</div>';
h+='</div>';
h+='<div class="mt-4 bg-gray-50 rounded-lg p-4 text-xs space-y-1"><h4 class="font-semibold text-gray-500 mb-2">SEO</h4>';
h+='<div><span class="text-gray-400">Title:</span> <span class="text-blue-700">'+(e(t.meta_title)||((e(t.h1)||e(t.title))+' — '+siteName))+'</span></div>';
h+='<div><span class="text-gray-400">Description:</span> <span class="text-green-700">'+(e(t.meta_description)||e(t.description)||'—')+'</span></div>';
h+='<div><span class="text-gray-400">URL:</span> <span class="font-mono text-gray-600">'+url+'</span></div>';
h+='<div><span class="text-gray-400">Статус:</span> '+(t.is_active?'<span class="text-green-600">Активен</span>':'<span class="text-red-500">Выключен</span>')+'</div></div>';
modal(h);}

function tForm(t){
var f=t||{title:'',slug:'',h1:'',description:'',meta_title:'',meta_description:'',content:'',icon:'🏷️',category:'microloans',features:'[]',search_queries:'',is_active:true,sort_order:0};
var id=t?t.id:0;
var catOpts='';for(var k in TG_CAT)catOpts+='<option value="'+k+'"'+(f.category===k?' selected':'')+'>'+TG_CAT[k]+'</option>';
var feat=f.features||'[]';if(typeof feat==='string')try{feat=JSON.parse(feat);}catch(e){feat=[];}
if(!f.icon)f.icon=tgSuggestIcon(f.title||'', f.category||'microloans');
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать тег':'Новый тег')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return tS(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div><label class="block text-xs font-medium mb-1">Иконка (эмодзи)</label><div class="flex gap-2"><input id="tg-icon" class="input-f flex-1" value="'+e(f.icon||'')+'" placeholder="авто"><button type="button" onclick="tgAutoIcon(true)" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-indigo-700 whitespace-nowrap">✨ Иконка</button></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Категория</label><select id="tg-cat" class="sel-f">'+catOpts+'</select></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="tg-title" class="input-f" value="'+e(f.title)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Slug (авто)</label><input id="tg-slug" class="input-f" value="'+e(f.slug)+'" placeholder="авто из названия"></div>'+
'<div><label class="block text-xs font-medium mb-1">Порядок</label><input id="tg-sort" type="number" class="input-f" value="'+f.sort_order+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">H1 заголовок</label><input id="tg-h1" class="input-f" value="'+e(f.h1||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Короткое описание</label><input id="tg-desc" class="input-f" value="'+e(f.description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Meta Title</label><div class="flex flex-wrap gap-2"><input id="tg-mt" class="input-f flex-1 min-w-0" value="'+e(f.meta_title||'')+'"><button type="button" id="tg-meta-btn" onclick="fillMeta(&quot;tg&quot;,&quot;tag&quot;)" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap">🤖 Meta</button><button type="button" id="tg-seo-btn" onclick="tgGenerateSeo()" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-indigo-700 whitespace-nowrap">✨ SEO</button></div></div>'+'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="tg-md" class="input-f" value="'+e(f.meta_description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO текст</label><div class="flex flex-wrap gap-2 mb-2"><button type="button" onclick="cqAnalyzeForm(&#39;tg&#39;,&#39;tag&#39;)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">🧪 Качество</button><button type="button" onclick="cqImproveField(&#39;tg&#39;,&#39;tag&#39;,&#39;content&#39;)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Улучшить текст</button><button type="button" onclick="cqImproveField(&#39;tg&#39;,&#39;tag&#39;,&#39;content&#39;,80,3)" style="background:#059669;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🎯 До 80+</button><button type="button" onclick="cqImproveField(&#39;tg&#39;,&#39;tag&#39;,&#39;content&#39;,90,4)" style="background:#0f766e;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🚀 До 90+</button><button type="button" onclick="cqCleanupOnly(&#39;tg&#39;,&#39;tag&#39;,&#39;content&#39;)" style="background:#475569;color:#fff;padding:6px 12px;border-radius:10px;font-size:12px;font-weight:600;white-space:nowrap;display:inline-flex;align-items:center">🧹 Только мусор</button><button type="button" onclick="cqImproveField(&#39;tg&#39;,&#39;tag&#39;,&#39;description&#39;)" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">✨ Улучшить описание</button><span id="tg-quality-badge" class="inline-flex items-center rounded-full bg-gray-100 text-gray-600 px-2.5 py-1 text-xs font-semibold">score —</span></div><textarea id="tg-content" class="input-f" rows="6">'+e(f.content||'')+'</textarea></div>'+'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Поисковые запросы для перелинковки <span class="text-gray-400">(по одному на строку)</span></label><textarea id="tg-queries" class="input-f text-xs" rows="5" placeholder="кредитная карта с кэшбэком\nкарта с кэшбеком">'+e(f.search_queries||'')+'</textarea></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Фичи (JSON) <span class="text-gray-400">[{"icon":"⚡","title":"...","text":"..."}]</span></label><div class="flex gap-2 mb-2"><button type="button" onclick="tgAutoFeatures()" class="bg-indigo-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-indigo-700 whitespace-nowrap">✨ Автофичи</button><span class="text-xs text-gray-400 self-center">Сгенерирует 4 карточки по категории и названию</span></div><textarea id="tg-feat" class="input-f font-mono text-xs" rows="5">'+e(JSON.stringify(feat,null,2))+'</textarea></div>'+
'<div class="col-span-2"><label class="flex items-center gap-2"><input type="checkbox" id="tg-active" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активен</span></label></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-2">📋 Привязанные предложения</label><div id="tg-offers-box" class="flex flex-wrap gap-2"><span class="text-xs text-gray-400">Загрузка...</span></div></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
setTimeout(function(){ cqSetInlineScore('tg', f.quality_score||0); }, 0);
if(!id){setTimeout(function(){var tt=document.getElementById('tg-title'), cc=document.getElementById('tg-cat'), ii=document.getElementById('tg-icon'); if(tt&&!tt.dataset.iconbound){tt.dataset.iconbound='1'; tt.addEventListener('input', function(){ if(ii) delete ii.dataset.manual; tgAutoIcon(); });} if(cc&&!cc.dataset.iconbound){cc.dataset.iconbound='1'; cc.addEventListener('change', function(){ if(ii) delete ii.dataset.manual; tgAutoIcon(); });} if(ii&&!ii.dataset.manualbound){ii.dataset.manualbound='1'; ii.addEventListener('input', function(){ ii.dataset.manual='1'; });} tgAutoIcon();},0);}

// Загружаем офферы для тега
Promise.all([ap('/offers'),ap('/tag-links?tagId='+(id||0))]).then(([allOffers,linked])=>{
var box=document.getElementById('tg-offers-box');if(!box)return;
var linArr=linked.map(Number);
var filtered=allOffers.filter(o=>o.category===f.category);
box.innerHTML=filtered.map(o=>'<label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer '+(linArr.includes(Number(o.id))?'bg-green-50 border-green-300':'bg-white border-gray-200')+' hover:border-green-400"><input type="checkbox" class="tg-off-cb w-3.5 h-3.5" value="'+o.id+'"'+(linArr.includes(Number(o.id))?' checked':'')+'> '+e(o.title)+'</label>').join('');
if(!filtered.length)box.innerHTML='<span class="text-xs text-gray-400">Нет предложений этой категории</span>';
});
}

function tgGenerateSeo(){
var title=(document.getElementById('tg-title')?.value||'').trim();
var category=document.getElementById('tg-cat')?.value||'microloans';
if(!title){ alert('Сначала заполните название тега'); return; }
var btn=document.getElementById('tg-seo-btn');
var oldText=btn?btn.textContent:'';
if(btn){ btn.disabled=true; btn.textContent='⏳ Генерация...'; }
ap('/tag-seo-generate',{method:'POST',body:JSON.stringify({title:title,category:category})}).then(function(d){
if(btn){ btn.disabled=false; btn.textContent=oldText||'✨ SEO'; }
if(d.error){ alert(d.error); return; }
var h1=document.getElementById('tg-h1'); if(h1 && d.h1) h1.value=d.h1;
var desc=document.getElementById('tg-desc'); if(desc && d.description) desc.value=d.description;
var mt=document.getElementById('tg-mt'); if(mt && d.metaTitle) mt.value=d.metaTitle;
var md=document.getElementById('tg-md'); if(md && d.metaDescription) md.value=d.metaDescription;
var co=document.getElementById('tg-content'); if(co && d.content) co.value=d.content;
var q=document.getElementById('tg-queries'); if(q && d.searchQueries) q.value=d.searchQueries;
alert('SEO сгенерировано ('+(d.provider||'template')+')');
}).catch(function(){ if(btn){ btn.disabled=false; btn.textContent=oldText||'✨ SEO'; } alert('Ошибка генерации SEO'); });
}

function tS(ev,id){ev.preventDefault();
var feat='[]';try{feat=document.getElementById('tg-feat').value;JSON.parse(feat);}catch(e){alert('Неверный JSON в фичах');return false;}
var body={title:document.getElementById('tg-title').value,slug:document.getElementById('tg-slug').value,h1:document.getElementById('tg-h1').value,description:document.getElementById('tg-desc').value,metaTitle:document.getElementById('tg-mt').value,metaDescription:document.getElementById('tg-md').value,content:document.getElementById('tg-content').value,searchQueries:document.getElementById('tg-queries').value,icon:document.getElementById('tg-icon').value,category:document.getElementById('tg-cat').value,features:feat,isActive:document.getElementById('tg-active').checked,sortOrder:parseInt(document.getElementById('tg-sort').value)||0};
var url=id?'/tags/'+id:'/tags';var method=id?'PUT':'POST';
ap(url,{method:method,body:JSON.stringify(body)}).then(d=>{if(d.error){alert(d.error);return;}
var tid=id||d.id;
var offerIds=Array.from(document.querySelectorAll('.tg-off-cb:checked')).map(x=>Number(x.value));
return ap('/tag-links',{method:'POST',body:JSON.stringify({tagId:tid,offerIds:offerIds})});
}).then(()=>{cm();lT();});return false;}

function tD(id){if(confirm('Удалить тег?'))ap('/tags/'+id,{method:'DELETE'}).then(()=>lT());}


/* ============ GEO ============ */
var GC={'AF':'Афганистан','AL':'Албания','DZ':'Алжир','AD':'Андорра','AO':'Ангола','AG':'Антигуа и Барбуда','AR':'Аргентина','AM':'Армения','AU':'Австралия','AT':'Австрия','AZ':'Азербайджан','BS':'Багамы','BH':'Бахрейн','BD':'Бангладеш','BB':'Барбадос','BY':'Беларусь','BE':'Бельгия','BT':'Бутан','BO':'Боливия','BA':'Босния и Герцеговина','BR':'Бразилия','BG':'Болгария','KH':'Камбоджа','CM':'Камерун','CA':'Канада','CL':'Чили','CN':'Китай','CO':'Колумбия','CG':'Конго','CR':'Коста-Рика','HR':'Хорватия','CU':'Куба','CY':'Кипр','CZ':'Чехия','DK':'Дания','DO':'Доминик. Респ.','EC':'Эквадор','EG':'Египет','EE':'Эстония','FI':'Финляндия','FR':'Франция','GE':'Грузия','DE':'Германия','GH':'Гана','GR':'Греция','HU':'Венгрия','IS':'Исландия','IN':'Индия','ID':'Индонезия','IR':'Иран','IQ':'Ирак','IE':'Ирландия','IL':'Израиль','IT':'Италия','JP':'Япония','JO':'Иордания','KZ':'Казахстан','KE':'Кения','KR':'Юж. Корея','KW':'Кувейт','KG':'Кыргызстан','LV':'Латвия','LB':'Ливан','LY':'Ливия','LT':'Литва','LU':'Люксембург','MY':'Малайзия','MT':'Мальта','MX':'Мексика','MD':'Молдова','MN':'Монголия','ME':'Черногория','MA':'Марокко','NL':'Нидерланды','NZ':'Нов. Зеландия','NG':'Нигерия','MK':'Сев. Македония','NO':'Норвегия','PK':'Пакистан','PS':'Палестина','PA':'Панама','PE':'Перу','PH':'Филиппины','PL':'Польша','PT':'Португалия','QA':'Катар','RO':'Румыния','RU':'Россия','SA':'Сауд. Аравия','RS':'Сербия','SG':'Сингапур','SK':'Словакия','SI':'Словения','ZA':'ЮАР','ES':'Испания','SE':'Швеция','CH':'Швейцария','TW':'Тайвань','TJ':'Таджикистан','TH':'Таиланд','TN':'Тунис','TR':'Турция','TM':'Туркменистан','UA':'Украина','AE':'ОАЭ','GB':'Великобритания','US':'США','UY':'Уругвай','UZ':'Узбекистан','VE':'Венесуэла','VN':'Вьетнам','HK':'Гонконг','XK':'Косово'};
var GS=Object.keys(GC).map(c=>({code:c,name:GC[c]})).sort((a,b)=>a.name.localeCompare(b.name,'ru'));
var _gr=[];
function lG(){ap('/geo-redirects').then(rules=>{_gr=rules;var w=rules.find(r=>r.country_code==='*');var exc=rules.filter(r=>r.country_code!=='*'&&(!r.redirect_url||!r.redirect_url.trim()));var sp=rules.filter(r=>r.country_code!=='*'&&r.redirect_url&&r.redirect_url.trim());
var h='<h2 class="text-xl font-bold mb-6">\u{1f30d} Гео-редиректы</h2>';
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6"><h3 class="font-bold text-gray-900 mb-1">\u{1f310} Редирект для всех стран</h3><p class="text-sm text-gray-500 mb-4">Все посетители (кроме исключений) перенаправляются.</p>';
if(w){h+='<div class="flex items-center gap-3 mb-3"><span class="px-2 py-1 rounded text-xs font-semibold '+(w.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(w.is_active?'Активен':'Выключен')+'</span><button onclick="gTg('+w.id+','+(w.is_active?0:1)+')" class="text-sm text-blue-600 hover:underline">'+(w.is_active?'Выключить':'Включить')+'</button><button onclick="gD('+w.id+')" class="text-sm text-red-500 hover:underline ml-2">Удалить</button></div><div class="flex gap-2"><input id="g-wu" class="input-f flex-1" value="'+e(w.redirect_url)+'"><button onclick="gUW('+w.id+')" class="btn-p text-sm">Сохранить</button></div>';}
else{h+='<div class="flex gap-2"><input id="g-nwu" class="input-f flex-1" placeholder="https://example.com"><button onclick="gAW()" class="btn-p text-sm">Включить</button></div>';}
h+='</div>';
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6"><h3 class="font-bold text-gray-900 mb-1">\u{1f6e1}\ufe0f Исключения</h3><p class="text-sm text-gray-500 mb-4">Эти страны НЕ редиректятся.</p>';
if(exc.length){h+='<div class="flex flex-wrap gap-2 mb-4">';exc.forEach(r=>{h+='<span class="inline-flex items-center gap-1 bg-blue-50 border border-blue-200 text-blue-800 px-3 py-1.5 rounded-lg text-sm"><span class="font-mono text-xs text-blue-500">'+r.country_code+'</span> '+(GC[r.country_code]||r.country_name||r.country_code)+' <button onclick="gD('+r.id+')" class="ml-1 text-blue-400 hover:text-red-500 font-bold">&times;</button></span>';});h+='</div>';}
h+='<div class="relative"><input id="g-es" class="input-f" placeholder="\u{1f50d} Добавить страну в исключения..." oninput="gFE()" onfocus="gFE()"><div id="g-ed" class="hidden absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg"></div></div></div>';
h+='<div class="bg-white rounded-xl border shadow-sm p-6"><h3 class="font-bold text-gray-900 mb-1">\u{1f3af} Редирект для конкретной страны</h3><p class="text-sm text-gray-500 mb-4">Отдельный URL для выбранной страны.</p>';
if(sp.length){h+='<div class="space-y-2 mb-4">';sp.forEach(r=>{h+='<div class="flex items-center gap-2 bg-gray-50 rounded-lg p-3 border"><span class="font-semibold text-sm w-36"><span class="font-mono text-xs text-gray-400 mr-1">'+r.country_code+'</span>'+(GC[r.country_code]||r.country_name)+'</span><span class="text-gray-400">&rarr;</span><input id="gs'+r.id+'" class="input-f flex-1 text-sm" value="'+e(r.redirect_url)+'"><button onclick="gUS('+r.id+')" class="text-blue-600 text-sm">\u{1f4be}</button><span class="px-2 py-0.5 rounded text-xs cursor-pointer '+(r.is_active?'bg-green-100 text-green-700':'bg-gray-200 text-gray-500')+'" onclick="gTg('+r.id+','+(r.is_active?0:1)+')">'+(r.is_active?'Вкл':'Выкл')+'</span><button onclick="gD('+r.id+')" class="text-red-400 hover:text-red-600">&times;</button></div>';});h+='</div>';}
h+='<div class="grid grid-cols-1 sm:grid-cols-3 gap-2"><div class="relative"><input id="g-ss" class="input-f text-sm" placeholder="\u{1f50d} Страна..." oninput="gFS()" onfocus="gFS()"><input type="hidden" id="g-sc"><div id="g-sd" class="hidden absolute z-10 mt-1 w-full max-h-60 overflow-y-auto bg-white border rounded-lg shadow-lg"></div></div><input id="g-su" class="input-f text-sm" placeholder="URL редиректа"><button onclick="gAS()" class="btn-p text-sm">+ Добавить</button></div></div>';
document.getElementById('p-geo').innerHTML=h;
document.addEventListener('click',function(ev){if(!ev.target.closest('#g-es,#g-ed')){var d=document.getElementById('g-ed');if(d)d.classList.add('hidden');}if(!ev.target.closest('#g-ss,#g-sd')){var d=document.getElementById('g-sd');if(d)d.classList.add('hidden');}});});}
function gAW(){var u=document.getElementById('g-nwu').value.trim();if(!u){alert('Введите URL');return;}ap('/geo-redirects',{method:'POST',body:JSON.stringify({countryCode:'*',countryName:'Все страны',redirectUrl:u,isActive:true})}).then(function(d){if(d.error)alert(d.error);lG();});}
function gUW(id){var u=document.getElementById('g-wu').value.trim();ap('/geo-redirects/'+id,{method:'PUT',body:JSON.stringify({countryCode:'*',countryName:'Все страны',redirectUrl:u,isActive:true})}).then(function(){lG();});}
function gFE(){var q=(document.getElementById('g-es').value||'').toLowerCase(),used=_gr.map(function(r){return r.country_code;}),m=GS.filter(function(c){return!used.includes(c.code)&&(c.name.toLowerCase().includes(q)||c.code.toLowerCase().includes(q));}),dd=document.getElementById('g-ed');if(!m.length){dd.classList.add('hidden');return;}dd.classList.remove('hidden');dd.innerHTML=m.slice(0,20).map(function(c){return'<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm" onclick="gAE(\''+c.code+'\')">'+c.name+' <span class="text-gray-400">('+c.code+')</span></div>';}).join('');}
function gAE(code){document.getElementById('g-ed').classList.add('hidden');document.getElementById('g-es').value='';ap('/geo-redirects',{method:'POST',body:JSON.stringify({countryCode:code,countryName:GC[code],redirectUrl:'',isActive:true})}).then(function(d){if(d.error)alert(d.error);lG();});}
function gFS(){var q=(document.getElementById('g-ss').value||'').toLowerCase(),used=_gr.map(function(r){return r.country_code;}),m=GS.filter(function(c){return!used.includes(c.code)&&(c.name.toLowerCase().includes(q)||c.code.toLowerCase().includes(q));}),dd=document.getElementById('g-sd');if(!m.length){dd.classList.add('hidden');return;}dd.classList.remove('hidden');dd.innerHTML=m.slice(0,20).map(function(c){return'<div class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm" onclick="gSS(\''+c.code+'\',\''+c.name+'\')">'+c.name+' <span class="text-gray-400">('+c.code+')</span></div>';}).join('');}
function gSS(code,name){document.getElementById('g-ss').value=name;document.getElementById('g-sc').value=code;document.getElementById('g-sd').classList.add('hidden');}
function gAS(){var code=document.getElementById('g-sc').value,url=document.getElementById('g-su').value.trim();if(!code){alert('Выберите страну');return;}if(!url){alert('Введите URL');return;}ap('/geo-redirects',{method:'POST',body:JSON.stringify({countryCode:code,countryName:GC[code],redirectUrl:url,isActive:true})}).then(function(d){if(d.error)alert(d.error);lG();});}
function gUS(id){var url=document.getElementById('gs'+id).value.trim();if(!url)return;var r=_gr.find(function(x){return x.id==id;});ap('/geo-redirects/'+id,{method:'PUT',body:JSON.stringify({countryCode:r.country_code,countryName:r.country_name,redirectUrl:url,isActive:true})}).then(function(){alert('Сохранено');lG();});}
function gTg(id,v){ap('/geo-redirects/'+id,{method:'PUT',body:JSON.stringify({isActive:!!v})}).then(function(){lG();});}
function gD(id){if(confirm('Удалить?'))ap('/geo-redirects/'+id,{method:'DELETE'}).then(function(){lG();});}


/* ============ CITY SEO ============ */
var _csCat='microloans';
var _csOverwrite=false;
var _csCitySlugs=[];
var _csShowMissingOnly=false;
var _ctsCityFilter='';
var _ctsTagFilter='';
var _ctsOverwrite=false;
var _ctsCitySlugs=[];
function csCityLabelBySlug(slug){var c=(adminCities||[]).find(function(x){return x.slug===slug;});return c?c.name:slug;}
function csCityPrepBySlug(slug){var c=(adminCities||[]).find(function(x){return x.slug===slug;});return c?(c.prep||c.name):slug;}
function csBuildCitySeoRows(list){
  var map={};
  (list||[]).forEach(function(item){map[item.city_slug]=item;});
  var pool=(_csCitySlugs&&_csCitySlugs.length)?adminCities.filter(function(c){return _csCitySlugs.indexOf(c.slug)!==-1;}):adminCities.slice();
  return pool.map(function(city){
    if(map[city.slug]) return Object.assign({}, map[city.slug], {_missing:false, city_name:city.name, city_prep:city.prep});
    return {id:0, city_slug:city.slug, city_name:city.name, city_prep:city.prep, seo_h1:'', meta_title:'', meta_description:'', seo_text:'', generated_by:'missing', _missing:true};
  });
}
function lCS(){
var cat=_csCat;
Promise.all([ap('/city-seo?category='+cat), ap('/city-tag-seo?category='+cat), ap('/tags')]).then(function(res){
var list=res[0]||[], cityTagList=res[1]||[], allTags=res[2]||[];
var scopedList=csBuildCitySeoRows(list);
var generatedCount=scopedList.filter(function(x){return !x._missing;}).length;
var displayList=_csShowMissingOnly?scopedList.filter(function(x){return !!x._missing;}):scopedList;
var missingCount=scopedList.filter(function(x){return !!x._missing;}).length;
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🏙️ SEO-тексты для городов</h2><div class="flex gap-2">';
h+='<select id="cs-cat" onchange="_csCat=this.value;lCS()" class="sel-f text-sm w-auto"><option value="microloans"'+(cat==='microloans'?' selected':'')+'>Займы</option><option value="credits"'+(cat==='credits'?' selected':'')+'>Кредиты</option><option value="credit_cards"'+(cat==='credit_cards'?' selected':'')+'>Кредитные карты</option><option value="debit_cards"'+(cat==='debit_cards'?' selected':'')+'>Дебетовые карты</option></select>';
h+='<select id="cs-overwrite" onchange="_csOverwrite=this.value===&#39;1&#39;" class="sel-f text-sm w-auto"><option value="0"'+(!_csOverwrite?' selected':'')+'>Только отсутствующие</option><option value="1"'+(_csOverwrite?' selected':'')+'>Перезаписать существующие</option></select>';
h+='<button type="button" onclick="openCityScopePicker(&#39;cs&#39;)" class="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-50">🏙 '+cityScopeLabel(_csCitySlugs)+'</button>';
h+='<button onclick="csGen(false)" class="btn-p text-sm" id="cs-gen-btn">'+(_csOverwrite?'⚡ Перегенерировать шаблоны':'⚡ Создать отсутствующие')+'</button>';
h+='<button onclick="csGen(true)" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-purple-700" id="cs-gpt-btn">'+(_csOverwrite?'🤖 Перегенерировать GPT':'🤖 GPT для отсутствующих')+'</button>';
h+='<button onclick="csClean(&#39;markdown&#39;)" class="bg-gray-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-700">🧹 Markdown</button>';
h+='<button onclick="csClean(&#39;plain&#39;)" class="bg-gray-800 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-black">🧽 HTML</button>';
h+='</div></div>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-700"><strong>Как работает:</strong> ⚡ Шаблоны — мгновенная генерация из готовых текстов. 🤖 YandexGPT — уникальные AI-тексты. Теперь список показывает и города без SEO, чтобы их было проще заполнить.</div>';

h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">';
h+='<p class="text-sm text-gray-500">Всего городов: <strong>'+scopedList.length+'</strong> • Показано: <strong>'+displayList.length+'</strong> • С SEO: <strong>'+generatedCount+'</strong> • Без SEO: <strong>'+missingCount+'</strong>'+( (_csCitySlugs&&_csCitySlugs.length)?' <span class="text-xs text-blue-600">(с учётом выбранных городов)</span>':'' )+'</p>';
h+='<div class="flex gap-2 items-center flex-wrap">';
h+='<select id="cs-city-filter" onchange="csFilterCity()" class="sel-f text-sm w-auto"><option value="">Все города</option>';
displayList.forEach(function(s){h+='<option value="'+e(s.city_slug)+'">'+e(s.city_name||s.city_slug)+'</option>';});
h+='</select>';
h+='<input id="cs-city-search" class="input-f text-sm" placeholder="🔍 Поиск..." oninput="csFilterCity()" style="width:160px">';
h+='<label class="inline-flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap"><input type="checkbox" '+(_csShowMissingOnly?'checked':'')+' onchange="_csShowMissingOnly=this.checked;lCS()" class="w-4 h-4">Только без SEO</label>';
h+='</div></div>';

if(displayList.length){
h+='<div class="bg-white rounded-xl border overflow-hidden"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-3 text-left">Город</th><th class="p-3 text-left">H1</th><th class="p-3 text-left w-24">Статус</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
displayList.forEach(function(s){
var badge=s.generated_by==='yandexgpt'?'<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">🤖 GPT</span>':s.generated_by==='manual'?'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">✏️ Ручной</span>':s._missing?'<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">— Нет SEO</span>':'<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">⚡ Шаблон</span>';
var h1Text=s.seo_h1?e((s.seo_h1||'').substring(0,80))+(s.seo_h1&&s.seo_h1.length>80?'...':''):'<span class="text-gray-300">Не сгенерировано</span>';
var actions=s._missing
 ? '<button onclick="csGenOne(\''+e(s.city_slug)+'\',false)" class="text-green-600 hover:underline text-sm mr-2">⚡ Создать</button><button onclick="csGenOne(\''+e(s.city_slug)+'\',true)" class="text-purple-600 hover:underline text-sm">🤖 GPT</button>'
 : '<button onclick="csEdit('+s.id+','+JSON.stringify(s).replace(/"/g,'&quot;')+')" class="text-blue-600 hover:underline text-sm mr-2">Ред.</button><button onclick="csDel('+s.id+')" class="text-red-500 hover:underline text-sm">Уд.</button>';
h+='<tr class="border-t hover:bg-gray-50 cs-row" data-city="'+e(s.city_slug)+'"><td class="p-3 font-medium">'+e(s.city_name||s.city_slug)+'<div class="text-xs text-gray-400 mt-0.5">'+e(s.city_slug)+'</div></td><td class="p-3 text-gray-600 text-xs">'+h1Text+'</td><td class="p-3">'+badge+'</td><td class="p-3 text-right">'+actions+'</td></tr>';
});
h+='</tbody></table></div>';
}else{
h+='<div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-500">'+(_csShowMissingOnly?'Все города уже имеют SEO-тексты.':'Нет городов для отображения. Проверьте фильтры.')+'</p></div>';
}
h+=renderCityTagSeoBlock(cityTagList, allTags, cat);
document.getElementById('p-cityseo').innerHTML=h;
csFilterCity();
});}

function csGen(useGPT){
var btn=document.getElementById(useGPT?'cs-gpt-btn':'cs-gen-btn');
var oldText=btn.textContent;
btn.disabled=true;btn.textContent='⏳ Генерация...';
var cityFilterEl=document.getElementById('cs-city-filter');
var cityFilter=cityFilterEl&&cityFilterEl.value?cityFilterEl.value:'';
var citySlugs=Array.isArray(_csCitySlugs)?_csCitySlugs.slice():[];
var payload={category:_csCat,useGPT:useGPT,overwrite:_csOverwrite,citySlugs:citySlugs};
if(cityFilter) payload.citySlug=cityFilter;
ap('/city-seo/generate',{method:'POST',body:JSON.stringify(payload)}).then(function(d){
btn.disabled=false;btn.textContent=oldText;
if(d.success)alert('Сгенерировано: '+d.generated+'; пропущено: '+d.skipped+'; ошибок: '+d.errors+'; всего: '+d.total);
else alert(d.error||'Ошибка');
lCS();
}).catch(function(){btn.disabled=false;btn.textContent=oldText;alert('Ошибка');});}

function csGenOne(citySlug,useGPT){
var modeText=useGPT?'YandexGPT':'шаблон';
if(!confirm('Сгенерировать SEO для города '+citySlug+' ('+modeText+')?')) return;
ap('/city-seo/generate',{method:'POST',body:JSON.stringify({category:_csCat,useGPT:useGPT,overwrite:true,citySlug:citySlug})}).then(function(d){
if(d.success) alert('Готово: '+d.generated+' из '+d.total);
else alert(d.error||'Ошибка');
lCS();
}).catch(function(){alert('Ошибка');});}

function csClean(mode){var cleanupMode=mode==='plain'?'plain':'markdown';var cityFilterEl=document.getElementById('cs-city-filter');var cityFilter=cityFilterEl&&cityFilterEl.value?cityFilterEl.value:'';var citySlugs=Array.isArray(_csCitySlugs)?_csCitySlugs.slice():[];if(cityFilter) citySlugs=[cityFilter];var scopeText=citySlugs.length?(' для '+citySlugs.join(', ')):' для всех городов';var modeText=cleanupMode==='plain'?'с полным удалением HTML':'с очисткой markdown-мусора';if(!confirm('Очистить тексты '+modeText+' в категории '+_csCat+scopeText+'?'))return;ap('/city-seo/clean',{method:'POST',body:JSON.stringify({category:_csCat,citySlugs:citySlugs,mode:cleanupMode})}).then(d=>{if(d.success)alert('Режим: '+(d.mode==='plain'?'HTML':'Markdown')+'; очищено записей: '+d.cleaned+' из '+d.total+'; SEO-текст: '+(d.updated_seo_text||0)+'; H1: '+(d.updated_seo_h1||0)+'; Meta title: '+(d.updated_meta_title||0)+'; Meta description: '+(d.updated_meta_description||0));else alert(d.error||'Ошибка');lCS();}).catch(()=>alert('Ошибка'));}
function csEdit(id,s){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать SEO: '+e(s.city_slug)+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return csSave(event,'+id+')">'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">H1</label><input id="cs-h1" class="input-f" value="'+e(s.seo_h1||'')+'"></div>'+
'<input type="hidden" id="cs-city-slug" value="'+e(s.city_slug||'')+'">'+'<div class="mb-3"><label class="block text-xs font-medium mb-1">Meta Title</label><div class="flex gap-2"><input id="cs-mt" class="input-f flex-1" value="'+e(s.meta_title||'')+'"><button type="button" id="cs-meta-btn" onclick="fillMeta(&quot;cs&quot;,&quot;city&quot;,{cityName:csCityLabelBySlug(document.getElementById(&quot;cs-city-slug&quot;).value),cityPrep:csCityPrepBySlug(document.getElementById(&quot;cs-city-slug&quot;).value),categoryName:(document.getElementById(&quot;cs-cat&quot;)?document.getElementById(&quot;cs-cat&quot;).selectedOptions[0].text:&quot;Город&quot;)})" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap">🤖 Meta</button></div></div>'+'<div class="mb-3"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="cs-md" class="input-f" value="'+e(s.meta_description||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">SEO-текст (HTML)</label><textarea id="cs-text" class="input-f font-mono text-xs" rows="12">'+e(s.seo_text||'')+'</textarea></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
}

function csSave(ev,id){ev.preventDefault();
ap('/city-seo/'+id,{method:'PUT',body:JSON.stringify({metaTitle:document.getElementById('cs-mt').value,seoH1:document.getElementById('cs-h1').value,seoText:document.getElementById('cs-text').value,metaDescription:document.getElementById('cs-md').value})}).then(()=>{cm();lCS();});return false;}

function csDel(id){if(confirm('Удалить SEO-текст? (будет регенерирован автоматически)'))ap('/city-seo/'+id,{method:'DELETE'}).then(()=>lCS());}

function cityScopeLabel(arr){
if(!arr||!arr.length) return 'Все города';
if(arr.length===1){
  var c=adminCities.find(function(x){return x.slug===arr[0];});
  return c?c.name:'1 город';
}
return 'Выбрано: '+arr.length;
}

var __cityScopeTarget='cs';
var __cityScopeTemp=[];

function cityScopeRenderList(){
var list=document.getElementById('csp-list');
if(!list) return;
var q=((document.getElementById('csp-search')||{}).value||'').toLowerCase().trim();
var filtered=adminCities.filter(function(c){
  return !q || c.name.toLowerCase().includes(q) || c.slug.toLowerCase().includes(q) || (c.region||'').toLowerCase().includes(q);
});
if(!filtered.length){
  list.innerHTML='<div class="sm:col-span-2 rounded-xl border border-dashed border-gray-200 px-4 py-6 text-center text-sm text-gray-400">Ничего не найдено</div>';
} else {
  list.innerHTML=filtered.map(function(c){
    var checked=__cityScopeTemp.indexOf(c.slug)!==-1?'checked':'';
    return '<label class="flex items-start gap-3 p-3 rounded-lg border hover:bg-gray-50 cursor-pointer">'
      +'<input type="checkbox" '+checked+' onchange="cityScopeToggle(\''+c.slug+'\',this.checked)" class="mt-1">'
      +'<div><div class="font-medium text-sm">'+e(c.name)+'</div><div class="text-xs text-gray-400">'+e(c.region||'')+'</div></div>'
      +'</label>';
  }).join('');
}
document.getElementById('csp-count').textContent='Выбрано: '+__cityScopeTemp.length;
}

function cityScopeToggle(slug, checked){
var idx=__cityScopeTemp.indexOf(slug);
if(checked && idx===-1) __cityScopeTemp.push(slug);
if(!checked && idx!==-1) __cityScopeTemp.splice(idx,1);
cityScopeRenderList();
}

function openCityScopePicker(target){
__cityScopeTarget=target;
__cityScopeTemp=(target==='cts'?_ctsCitySlugs:_csCitySlugs).slice();
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🏙 Выбор городов</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<div class="space-y-4">'+
'<div class="flex flex-col sm:flex-row gap-2"><input id="csp-search" oninput="cityScopeRenderList()" class="input-f flex-1" placeholder="Найти город..."><div class="text-sm text-gray-500 flex items-center px-2" id="csp-count"></div></div>'+
'<div class="flex gap-2"><button type="button" onclick="__cityScopeTemp=adminCities.map(function(c){return c.slug});cityScopeRenderList()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold">Выбрать все</button><button type="button" onclick="__cityScopeTemp=[];cityScopeRenderList()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold">Снять все</button></div>'+
'<div id="csp-list" class="grid sm:grid-cols-2 gap-2 max-h-80 overflow-y-auto"></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="button" onclick="applyCityScope()" class="btn-p">Применить</button></div>'+
'</div>');
cityScopeRenderList();
var inp=document.getElementById('csp-search');if(inp)inp.focus();
}

function applyCityScope(){
var vals=__cityScopeTemp.slice();
if(__cityScopeTarget==='cts') _ctsCitySlugs=vals; else _csCitySlugs=vals;
cm();
lCS();
}

function renderCityTagSeoBlock(list, allTags, cat){
var cityOptions='<option value="">Все города</option>'+adminCities.map(function(c){return '<option value="'+e(c.slug)+'"'+(_ctsCityFilter===c.slug?' selected':'')+'>'+e(c.name)+'</option>';}).join('');
var tagOptions='<option value="">Все теги</option>'+allTags.filter(function(t){return t.category===cat;}).map(function(t){return '<option value="'+e(t.slug)+'"'+(_ctsTagFilter===t.slug?' selected':'')+'>'+e(t.title)+'</option>';}).join('');
var filtered=list.filter(function(item){
  return (!_ctsCityFilter || item.city_slug===_ctsCityFilter) && (!_ctsTagFilter || item.tag_slug===_ctsTagFilter);
});
var h='';
h+='<div class="mt-10 flex flex-col gap-4 mb-6"><div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3"><h2 class="text-xl font-bold">🏷️ SEO-тексты для страниц город + тег</h2><div class="flex flex-wrap gap-2"><select id="cts-overwrite" onchange="_ctsOverwrite=this.value===\'1\'" class="sel-f text-sm w-auto"><option value="0"'+(!_ctsOverwrite?' selected':'')+'>Только отсутствующие</option><option value="1"'+(_ctsOverwrite?' selected':'')+'>Перезаписать существующие</option></select><button type="button" onclick="openCityScopePicker(&#39;cts&#39;)" class="bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-50">🏙 '+cityScopeLabel(_ctsCitySlugs)+'</button><button onclick="ctsGen(false)" class="btn-p text-sm" id="cts-gen-btn">⚡ Шаблоны</button><button onclick="ctsGen(true)" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-purple-700" id="cts-gpt-btn">🤖 AI</button><button onclick="ctsClean(&#39;markdown&#39;)" class="bg-gray-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-700">🧹 Markdown</button><button onclick="ctsClean(&#39;plain&#39;)" class="bg-gray-800 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-black">🧽 HTML</button></div></div>';
h+='<div class="grid md:grid-cols-2 gap-3 bg-white rounded-xl border p-4"><div><label class="block text-xs font-medium mb-1">Фильтр по городу</label><select id="cts-city-filter" onchange="_ctsCityFilter=this.value;lCS()" class="sel-f text-sm">'+cityOptions+'</select></div><div><label class="block text-xs font-medium mb-1">Фильтр по тегу</label><select id="cts-tag-filter" onchange="_ctsTagFilter=this.value;lCS()" class="sel-f text-sm">'+tagOptions+'</select></div></div></div>';
h+='<div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6 text-sm text-indigo-700">';
h+='<strong>Что генерируется:</strong> title, H1, description и SEO-текст для страниц вида <code>/город/type/тег</code>. Например: <code>/zajmy/tyumen/type/bez-otkaza</code>. Фильтры выше можно использовать для выборочной генерации, а режим справа — для перезаписи существующих записей.';
h+='</div>';
h+='<p class="text-sm text-gray-500 mb-4">Сгенерировано city+tag страниц: <strong>'+(list.length||0)+'</strong>'+(filtered.length!==list.length?' <span class="text-gray-400">(показано: '+filtered.length+')</span>':'')+'</p>';
if(filtered.length){
h+='<div class="bg-white rounded-xl border overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-3 text-left">Город</th><th class="p-3 text-left">Тег</th><th class="p-3 text-left">H1</th><th class="p-3 text-left w-24">Способ</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
filtered.forEach(function(s){
var badge=s.generated_by==='yandexgpt'?'<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">🤖 GPT</span>':s.generated_by==='manual'?'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">✏️ Ручной</span>':'<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">⚡ Шаблон</span>';
h+='<tr class="border-t hover:bg-gray-50 cs-row" data-city="'+e(s.city_slug)+'"><td class="p-3 font-medium">'+e(s.city_slug)+'</td><td class="p-3">'+e(s.tag_slug)+'</td><td class="p-3 text-gray-600 text-xs">'+e((s.seo_h1||'').substring(0,70))+(s.seo_h1&&s.seo_h1.length>70?'...':'')+'</td><td class="p-3">'+badge+'</td><td class="p-3 text-right"><button onclick="ctsEdit('+s.id+','+JSON.stringify(s).replace(/"/g,'&quot;')+')" class="text-blue-600 hover:underline text-sm mr-2">Ред.</button><button onclick="ctsDel('+s.id+')" class="text-red-500 hover:underline text-sm">Уд.</button></td></tr>';
});
h+='</tbody></table></div></div>';
}else{
h+='<div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-500">Нет city+tag SEO-текстов для выбранных фильтров. Используйте ⚡ Шаблоны или 🤖 YandexGPT.</p></div>';
}
return h;
}

function ctsGen(useGPT){
var btn=document.getElementById(useGPT?'cts-gpt-btn':'cts-gen-btn');
var oldText=btn.textContent;
btn.disabled=true;btn.textContent='⏳ Генерация...';
ap('/city-tag-seo/generate',{method:'POST',body:JSON.stringify({category:_csCat,useGPT:useGPT,overwrite:_ctsOverwrite,citySlug:_ctsCityFilter||null,citySlugs:_ctsCitySlugs,tagSlug:_ctsTagFilter||null})}).then(d=>{
btn.disabled=false;btn.textContent=oldText;
if(d.success)alert('Сгенерировано: '+d.generated+'; пропущено: '+d.skipped+'; ошибок: '+d.errors+'; всего: '+d.total);
else alert(d.error||'Ошибка');
lCS();
}).catch(()=>{btn.disabled=false;btn.textContent=oldText;alert('Ошибка');});}

function ctsClean(mode){
var cleanupMode=mode==='plain'?'plain':'markdown';
var citySlugs=Array.isArray(_ctsCitySlugs)?_ctsCitySlugs.slice():[];
if(_ctsCityFilter) citySlugs=[_ctsCityFilter];
var scopeText=citySlugs.length?(' для '+citySlugs.join(', ')):' для всех городов';
var tagText=_ctsTagFilter?(' и тега '+_ctsTagFilter):'';
var modeText=cleanupMode==='plain'?'с полным удалением HTML':'с очисткой markdown-мусора';
if(!confirm('Очистить city+tag тексты '+modeText+' в категории '+_csCat+scopeText+tagText+'?')) return;
ap('/city-tag-seo/clean',{method:'POST',body:JSON.stringify({category:_csCat,citySlugs:citySlugs,tagSlug:_ctsTagFilter||'',mode:cleanupMode})}).then(function(d){
if(d.success) alert('Режим: '+(d.mode==='plain'?'HTML':'Markdown')+'; очищено записей: '+d.cleaned+' из '+d.total+'; SEO-текст: '+(d.updated_seo_text||0)+'; H1: '+(d.updated_seo_h1||0)+'; Meta title: '+(d.updated_meta_title||0)+'; Meta description: '+(d.updated_meta_description||0));
else alert(d.error||'Ошибка');
lCS();
}).catch(function(){ alert('Ошибка'); });}

function ctsEdit(id,s){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать city+tag SEO: '+e(s.city_slug)+' / '+e(s.tag_slug)+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return ctsSave(event,'+id+')">'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">H1</label><input id="cts-h1" class="input-f" value="'+e(s.seo_h1||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Meta Title</label><input id="cts-mt" class="input-f" value="'+e(s.meta_title||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="cts-md" class="input-f" value="'+e(s.meta_description||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">SEO-текст (HTML)</label><textarea id="cts-text" class="input-f font-mono text-xs" rows="12">'+e(s.seo_text||'')+'</textarea></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
}
function ctsSave(ev,id){ev.preventDefault();ap('/city-tag-seo/'+id,{method:'PUT',body:JSON.stringify({metaTitle:document.getElementById('cts-mt').value,seoH1:document.getElementById('cts-h1').value,seoText:document.getElementById('cts-text').value,metaDescription:document.getElementById('cts-md').value})}).then(()=>{cm();lCS();});return false;}
function ctsDel(id){if(confirm('Удалить city+tag SEO-текст?'))ap('/city-tag-seo/'+id,{method:'DELETE'}).then(()=>lCS());}


/* ============ STATS ============ */
var _statsPeriod=30;
var _statsTimer=null;

function lS(){
var p=_statsPeriod;
ap('/stats?period='+p).then(s=>{
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">📊 Аналитика</h2><div class="flex items-center gap-2">';
h+='<select id="st-period" onchange="_statsPeriod=+this.value;lS()" class="sel-f text-sm w-auto"><option value="7"'+(p==7?' selected':'')+'>7 дней</option><option value="14"'+(p==14?' selected':'')+'>14 дней</option><option value="30"'+(p==30?' selected':'')+'>30 дней</option><option value="90"'+(p==90?' selected':'')+'>90 дней</option><option value="365"'+(p==365?' selected':'')+'>Год</option></select>';
h+='<button onclick="lS()" class="text-sm text-blue-600 hover:underline">🔄</button></div></div>';

// Realtime
h+='<div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-5 mb-6 text-white"><div class="flex items-center justify-between"><div><p class="text-blue-100 text-sm">В реальном времени</p><div class="flex items-center gap-4 mt-1"><span class="text-3xl font-bold">'+s.last5min+'</span><span class="text-blue-200 text-sm">за 5 мин</span><span class="text-2xl font-semibold ml-4">'+s.lastHour+'</span><span class="text-blue-200 text-sm">за час</span></div></div><div class="text-right"><p class="text-blue-100 text-sm">Сегодня</p><p class="text-3xl font-bold">'+s.clicksToday+'</p></div></div></div>';

// Счётчики
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+s.clicksToday+'</p><p class="text-xs text-gray-500">Сегодня</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+s.clicksWeek+'</p><p class="text-xs text-gray-500">Неделя</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+s.clicksMonth+'</p><p class="text-xs text-gray-500">Месяц</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-gray-800">'+s.clicksTotal+'</p><p class="text-xs text-gray-500">Всего</p></div></div>';

// График
h+='<div class="bg-white rounded-xl border p-5 mb-6"><h3 class="font-semibold mb-3">Клики и просмотры по дням</h3><div style="position:relative;height:250px;max-height:250px"><canvas id="st-chart"></canvas></div></div>';

// Клики по часам
h+='<div class="bg-white rounded-xl border p-5 mb-6"><h3 class="font-semibold mb-3">Клики по часам (сегодня)</h3><div style="position:relative;height:150px;max-height:150px"><canvas id="st-hourly"></canvas></div></div>';

// Топ офферов + конверсия
if(s.topOffers&&s.topOffers.length){
h+='<div class="bg-white rounded-xl border mb-6"><div class="p-4 border-b"><h3 class="font-semibold">Топ офферов за '+p+' дней</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Оффер</th><th class="p-3 text-right">Просмотры</th><th class="p-3 text-right">Клики</th><th class="p-3 text-right">Конверсия</th><th class="p-3 text-left w-40">Прогресс</th></tr></thead><tbody>';
var maxC=Math.max(...s.topOffers.map(o=>o.clicks));
s.topOffers.forEach(o=>{
var pct=maxC>0?Math.round(o.clicks/maxC*100):0;
var convColor=o.conversion>=10?'text-green-600':o.conversion>=5?'text-yellow-600':'text-gray-500';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-medium">'+e(o.title)+'</td><td class="p-3 text-right text-gray-500">'+o.views+'</td><td class="p-3 text-right font-semibold">'+o.clicks+'</td><td class="p-3 text-right '+convColor+' font-semibold">'+o.conversion+'%</td><td class="p-3"><div class="bg-gray-200 rounded-full h-2"><div class="bg-blue-500 rounded-full h-2" style="width:'+pct+'%"></div></div></td></tr>';});
h+='</tbody></table></div></div>';}

// UTM-источники
if(s.utmSources&&s.utmSources.length){
h+='<div class="bg-white rounded-xl border mb-6"><div class="p-4 border-b"><h3 class="font-semibold">🔗 UTM-источники за '+p+' дней</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Source</th><th class="p-3 text-left">Medium</th><th class="p-3 text-left">Campaign</th><th class="p-3 text-right">Клики</th></tr></thead><tbody>';
s.utmSources.forEach(u=>{
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3"><span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold">'+(e(u.utm_source)||'—')+'</span></td><td class="p-3 text-gray-600">'+(e(u.utm_medium)||'—')+'</td><td class="p-3 text-gray-600">'+(e(u.utm_campaign)||'—')+'</td><td class="p-3 text-right font-semibold">'+u.clicks+'</td></tr>';});
h+='</tbody></table></div></div>';}

// Общие счётчики контента
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4"><div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold">'+s.offers+'</p><p class="text-xs text-gray-500">Предложений</p></div><div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold">'+s.articles+'</p><p class="text-xs text-gray-500">Статей</p></div><div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold">'+s.reviews+'</p><p class="text-xs text-gray-500">Отзывов</p></div><div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold">'+s.subscribers+'</p><p class="text-xs text-gray-500">Подписчиков</p></div></div>';

document.getElementById('p-stats').innerHTML=h;

// Рисуем графики
setTimeout(function(){stChart(s);stHourly(s);},100);

// Автообновление каждые 30 сек
if(_statsTimer)clearInterval(_statsTimer);
_statsTimer=setInterval(function(){ap('/stats?period='+_statsPeriod).then(function(ns){
document.querySelector('#p-stats .text-3xl.font-bold')&&lS();
});},30000);
}).catch(function(err){
if(_statsTimer)clearInterval(_statsTimer);
document.getElementById('p-stats').innerHTML='<div class="bg-red-50 border border-red-200 rounded-xl p-6"><h2 class="text-xl font-bold text-red-700 mb-2">Ошибка загрузки статистики</h2><p class="text-sm text-red-600 mb-3">'+e((err&&err.message)?err.message:'Неизвестная ошибка')+'</p><p class="text-xs text-red-500">Проверьте API /api/admin/stats и обновите страницу.</p></div>';
});}

function stChart(s){
var canvas=document.getElementById('st-chart');if(!canvas)return;
var ctx=canvas.getContext('2d');
// Подготовка данных
var days={};
s.chartClicks.forEach(function(d){days[d.day]={clicks:Number(d.cnt),views:0};});
s.chartViews.forEach(function(d){if(!days[d.day])days[d.day]={clicks:0,views:0};days[d.day].views=Number(d.cnt);});
var labels=Object.keys(days).sort();
var clicks=labels.map(function(d){return days[d].clicks;});
var views=labels.map(function(d){return days[d].views;});
var shortLabels=labels.map(function(d){var p=d.split('-');return p[2]+'.'+p[1];});

if(window.stChartInst)window.stChartInst.destroy();
window.stChartInst=new Chart(ctx,{type:'line',data:{labels:shortLabels,datasets:[{label:'Клики',data:clicks,borderColor:'#1a56db',backgroundColor:'rgba(26,86,219,0.1)',fill:true,tension:0.3,borderWidth:2,pointRadius:2},{label:'Просмотры',data:views,borderColor:'#059669',backgroundColor:'rgba(5,150,105,0.1)',fill:true,tension:0.3,borderWidth:2,pointRadius:2}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{usePointStyle:true,pointStyle:'circle',padding:20}}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{ticks:{maxTicksToShow:15}}}}});
}

function stHourly(s){
var canvas=document.getElementById('st-hourly');if(!canvas)return;
var ctx=canvas.getContext('2d');
var data=new Array(24).fill(0);
s.hourly.forEach(function(h){data[Number(h.h)]=Number(h.cnt);});
var labels=data.map(function(_,i){return i+':00';});

if(window.stHourlyInst)window.stHourlyInst.destroy();
window.stHourlyInst=new Chart(ctx,{type:'bar',data:{labels:labels,datasets:[{label:'Клики',data:data,backgroundColor:'rgba(26,86,219,0.6)',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
}



/* ============ SMART RATING ============ */
var _smartPeriod=30;
function lSmart(){
var p=_smartPeriod;
ap('/smart-rating?period='+p).then(d=>{
var items=d.items||[];
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🧠 Умный рейтинг офферов</h2><div class="flex gap-2"><select id="smart-period" onchange="_smartPeriod=+this.value;lSmart()" class="sel-f text-sm w-auto"><option value="7"'+(p==7?' selected':'')+'>7 дн</option><option value="14"'+(p==14?' selected':'')+'>14 дн</option><option value="30"'+(p==30?' selected':'')+'>30 дн</option><option value="90"'+(p==90?' selected':'')+'>90 дн</option><option value="365"'+(p==365?' selected':'')+'>Год</option></select><button onclick="smartApply()" class="btn-p text-sm">Применить сортировку</button></div></div>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-700">Формула рейтинга внутри каждой категории: <strong>клики 25%</strong> + <strong>CTR 20%</strong> + <strong>approval 20%</strong> + <strong>EPC 20%</strong> + <strong>отзывы 10%</strong> + <strong>свежесть 5%</strong>. Кнопка «Применить сортировку» перезапишет sort_order внутри каждой категории.</div>';

if(!items.length){h+='<div class="bg-white rounded-xl border p-8 text-center text-gray-500">Нет офферов для расчёта</div>';document.getElementById('p-smart').innerHTML=h;return;}

let groups={}; items.forEach(function(o){ if(!groups[o.category]) groups[o.category]=[]; groups[o.category].push(o); });
Object.keys(groups).forEach(function(cat){
  h+='<div class="bg-white rounded-2xl border shadow-sm p-4 mb-6"><h3 class="text-lg font-bold text-gray-900 mb-4">'+(CL[cat]||cat)+'</h3><div class="space-y-3">';
  groups[cat].sort(function(a,b){ return b.smart_score-a.smart_score; });
  groups[cat].forEach(function(o,idx){
    h+='<div class="bg-gray-50 rounded-xl border p-4">';
    h+='<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-3">';
    h+='<div class="min-w-0"><p class="font-semibold text-gray-900 text-sm">#'+(idx+1)+' • '+e(o.title)+'</p><p class="text-xs text-gray-500 mt-1">Текущий sort_order: '+o.sort_order+' • Новый: '+idx+'</p></div>';
    h+='<div class="text-right"><p class="text-xs text-gray-400">Smart Score</p><p class="text-2xl font-bold text-purple-700">'+o.smart_score+'</p></div>';
    h+='</div>';
    h+='<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3 text-center">';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">Клики</p><p class="mt-1 font-bold text-gray-900">'+o.clicks+'</p><p class="text-[11px] text-blue-600">+'+o.score_parts.clicks+'</p></div>';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">CTR</p><p class="mt-1 font-bold text-gray-900">'+o.ctr+'%</p><p class="text-[11px] text-blue-600">+'+o.score_parts.ctr+'</p></div>';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">Approval</p><p class="mt-1 font-bold text-gray-900">'+o.approval_rate+'%</p><p class="text-[11px] text-blue-600">+'+o.score_parts.approval+'</p></div>';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">EPC</p><p class="mt-1 font-bold text-gray-900">'+Number(o.epc).toFixed(2)+' ₽</p><p class="text-[11px] text-blue-600">+'+o.score_parts.epc+'</p></div>';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">Отзывы</p><p class="mt-1 font-bold text-gray-900">'+o.rating+' ★ / '+o.review_count+'</p><p class="text-[11px] text-blue-600">+'+o.score_parts.reviews+'</p></div>';
    h+='<div class="rounded-lg bg-white border p-3"><p class="text-[11px] text-gray-400 uppercase">Свежесть</p><p class="mt-1 font-bold text-gray-900">'+o.freshness+'</p><p class="text-[11px] text-blue-600">+'+o.score_parts.freshness+'</p></div>';
    h+='</div>';
    h+='</div>';
  });
  h+='</div></div>';
});

document.getElementById('p-smart').innerHTML=h;}).catch(function(err){document.getElementById('p-smart').innerHTML='<div class="bg-red-50 border border-red-200 rounded-xl p-6"><h2 class="text-xl font-bold text-red-700 mb-2">Ошибка загрузки умного рейтинга</h2><p class="text-sm text-red-600">'+e((err&&err.message)?err.message:'Неизвестная ошибка')+'</p></div>';});}
function smartApply(){if(!confirm('Применить умную сортировку ко всем офферам? Текущий ручной порядок будет перезаписан внутри каждой категории.'))return;ap('/smart-rating?period='+_smartPeriod,{method:'POST'}).then(function(d){if(d.success){alert(d.message);lSmart();}else alert(d.error||'Ошибка');});}

/* ============ FUNNEL ============ */
var _funnelPeriod=30;
function lFunnel(){
var p=_funnelPeriod;
ap('/funnel?period='+p).then(d=>{
var t=d.totals||{};
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🔻 Воронка по офферам</h2><div class="flex gap-2"><select id="fn-period" onchange="_funnelPeriod=+this.value;lFunnel()" class="sel-f text-sm w-auto"><option value="7"'+(p==7?' selected':'')+'>7 дн</option><option value="14"'+(p==14?' selected':'')+'>14 дн</option><option value="30"'+(p==30?' selected':'')+'>30 дн</option><option value="90"'+(p==90?' selected':'')+'>90 дн</option><option value="365"'+(p==365?' selected':'')+'>Год</option></select><button onclick="showFunnelDebugPicker()" class="text-sm text-purple-600 hover:underline">🧪 Проверить логику</button><button onclick="lFunnel()" class="text-sm text-blue-600 hover:underline">🔄</button></div></div>';

// Общая воронка
h+='<div class="bg-white rounded-2xl border p-6 mb-6">';
h+='<h3 class="font-bold text-gray-900 mb-4">Общая воронка за '+p+' дней</h3>';
h+='<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">';
h+='<div class="rounded-xl bg-blue-50 p-4 min-w-0"><p class="text-xs uppercase tracking-wide text-blue-400">Просмотры</p><p class="mt-1 text-2xl font-bold text-blue-700 break-all">'+Number(t.views||0).toLocaleString('ru-RU')+'</p></div>';
h+='<div class="rounded-xl bg-indigo-50 p-4 min-w-0"><p class="text-xs uppercase tracking-wide text-indigo-400">Клики</p><p class="mt-1 text-2xl font-bold text-indigo-700 break-all">'+Number(t.clicks||0).toLocaleString('ru-RU')+'</p></div>';
h+='<div class="rounded-xl bg-green-50 p-4 min-w-0"><p class="text-xs uppercase tracking-wide text-green-400">Одобрено</p><p class="mt-1 text-2xl font-bold text-green-700 break-all">'+Number(t.approved||0).toLocaleString('ru-RU')+'</p></div>';
h+='<div class="rounded-xl bg-red-50 p-4 min-w-0"><p class="text-xs uppercase tracking-wide text-red-400">Отклонено</p><p class="mt-1 text-2xl font-bold text-red-600 break-all">'+Number(t.rejected||0).toLocaleString('ru-RU')+'</p></div>';
h+='</div>';
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">';
h+='<div class="bg-blue-50 rounded-xl p-3 text-center min-w-0"><p class="text-2xl font-bold text-blue-600 break-all">'+t.ctr+'%</p><p class="text-xs text-gray-500">CTR</p></div>';
h+='<div class="bg-green-50 rounded-xl p-3 text-center min-w-0"><p class="text-2xl font-bold text-green-600 break-all">'+t.cr+'%</p><p class="text-xs text-gray-500">CR</p></div>';
h+='<div class="bg-yellow-50 rounded-xl p-3 text-center min-w-0"><p class="text-2xl font-bold text-yellow-600 break-all">'+t.approval_rate+'%</p><p class="text-xs text-gray-500">Approval</p></div>';
h+='<div class="bg-purple-50 rounded-xl p-3 text-center min-w-0"><p class="text-2xl font-bold text-purple-600 break-all">'+Number(t.epc||0).toFixed(2)+' ₽</p><p class="text-xs text-gray-500">EPC</p></div>';
h+='</div>';
h+='<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3 text-xs text-gray-500">';
h+='<div class="rounded-xl bg-gray-50 p-3">CTR — просмотры → клики</div>';
h+='<div class="rounded-xl bg-gray-50 p-3">CR — клики → одобрения</div>';
h+='<div class="rounded-xl bg-gray-50 p-3">Approval — одобрено / все решения</div>';
h+='<div class="rounded-xl bg-gray-50 p-3">EPC — доход / клик</div>';
h+='</div>';
h+='<div class="mt-4 rounded-xl bg-green-50 p-4 text-center"><span class="text-sm font-semibold text-green-700">Доход: '+Number(t.payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</span></div>';
h+='</div>';

// Таблица по офферам
var items=d.funnel||[];
if(items.length){
h+='<div class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="p-4 border-b"><h3 class="font-bold text-gray-900">Воронка по каждому офферу</h3></div><div style="max-width:100%;overflow-x:auto;overflow-y:hidden;-webkit-overflow-scrolling:touch"><table class="text-sm" style="min-width:980px;width:100%;table-layout:auto"><thead class="bg-gray-50"><tr><th class="p-3 text-left" style="min-width:240px">Оффер</th><th class="p-3 text-right whitespace-nowrap">Просм.</th><th class="p-3 text-right whitespace-nowrap">Клики</th><th class="p-3 text-right whitespace-nowrap">CTR</th><th class="p-3 text-right text-green-700 whitespace-nowrap">Одобр.</th><th class="p-3 text-right text-red-600 whitespace-nowrap">Откл.</th><th class="p-3 text-right whitespace-nowrap">CR</th><th class="p-3 text-right whitespace-nowrap">Approval</th><th class="p-3 text-right whitespace-nowrap">EPC</th><th class="p-3 text-right font-semibold whitespace-nowrap">Доход</th><th class="p-3 text-right whitespace-nowrap">Debug</th></tr></thead><tbody>';
items.forEach(function(o){
var rowClass=o.clicks===0?'bg-gray-50 text-gray-400':'';
h+='<tr class="border-t hover:bg-gray-50 '+rowClass+'"><td class="p-3 font-medium" style="min-width:240px;max-width:240px"><div style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+e(o.title)+'">'+e(o.title)+'</div></td>';
h+='<td class="p-3 text-right whitespace-nowrap">'+o.views+'</td>';
h+='<td class="p-3 text-right whitespace-nowrap font-semibold">'+o.clicks+'</td>';
h+='<td class="p-3 text-right whitespace-nowrap '+(o.ctr>=5?'text-blue-600':'text-gray-500')+'">'+o.ctr+'%</td>';
h+='<td class="p-3 text-right whitespace-nowrap text-green-600 font-semibold">'+o.approved+'</td>';
h+='<td class="p-3 text-right whitespace-nowrap text-red-500">'+o.rejected+'</td>';
h+='<td class="p-3 text-right whitespace-nowrap '+(o.cr>=3?'text-green-600':'text-gray-500')+'">'+o.cr+'%</td>';
h+='<td class="p-3 text-right whitespace-nowrap '+(o.approval_rate>=50?'text-green-600':o.approval_rate>0?'text-yellow-600':'text-gray-400')+'">'+o.approval_rate+'%</td>';
h+='<td class="p-3 text-right whitespace-nowrap '+(o.epc>0?'text-purple-600':'text-gray-400')+'">'+Number(o.epc).toFixed(2)+'</td>';
h+='<td class="p-3 text-right whitespace-nowrap font-semibold '+(o.payout>0?'text-green-700':'text-gray-400')+'">'+Number(o.payout).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</td>';
h+='<td class="p-3 text-right whitespace-nowrap"><button type="button" onclick="showFunnelDebug('+o.id+')" class="text-purple-600 hover:underline text-sm">проверить</button></td>';
h+='</tr>';
});
h+='</tbody></table></div></div>';
}

document.getElementById('p-funnel').innerHTML=h;});}


function showFunnelDebugPicker(){
  ap('/offers').then(function(list){
    var opts='<option value="">Выберите оффер</option>';
    (list||[]).forEach(function(o){ opts+='<option value="'+o.id+'">'+e(o.title)+'</option>'; });
    modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧪 Проверка логики воронки</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>'+
      '<div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Оффер</label><select id="funnel-debug-offer" class="sel-f">'+opts+'</select></div>'+
      '<div class="flex justify-end gap-3"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="var id=document.getElementById(\'funnel-debug-offer\').value;if(!id){alert(\'Выберите оффер\');return;}showFunnelDebug(id);" class="btn-p">Проверить</button></div></div>');
  });
}

function showFunnelDebug(offerId){
  ap('/funnel?action=debug&period='+_funnelPeriod+'&offer_id='+offerId).then(function(d){
    if(d.error){ alert(d.error); return; }
    var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧪 Debug воронки: '+e(d.offer.title||'')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
    h+='<div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">';
    h+='<p><strong>Период:</strong> '+d.period+' дней</p>';
    h+='<p><strong>Страница оффера:</strong> /offer/'+e(d.offer.slug||'')+'</p>';
    h+='<p><strong>Колонки:</strong> page_views.'+e(d.columns.page_views)+' / click_stats.'+e(d.columns.click_stats)+' / postback_conversions.'+e(d.columns.postback_conversions)+'</p>';
    h+='</div>';
    h+='<div class="grid grid-cols-3 gap-3 mb-4">';
    h+='<div class="bg-blue-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-blue-600">'+(d.counts.views||0)+'</p><p class="text-xs text-gray-500">Просмотры</p></div>';
    h+='<div class="bg-indigo-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-indigo-600">'+(d.counts.clicks||0)+'</p><p class="text-xs text-gray-500">Клики</p></div>';
    var approved=0,rejected=0,pending=0,payout=0; (d.counts.conversions||[]).forEach(function(c){ if(c.status==='approved'){approved=Number(c.cnt||0); payout=Number(c.total||0);} else if(c.status==='rejected'){rejected=Number(c.cnt||0);} else {pending+=Number(c.cnt||0);} });
    h+='<div class="bg-green-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-green-600">'+approved+'</p><p class="text-xs text-gray-500">Одобрено</p></div>';
    h+='</div>';
    h+='<div class="bg-white rounded-xl border p-4 mb-4"><h4 class="font-semibold mb-2">Конверсии по статусам</h4><div class="space-y-1 text-sm">';
    (d.counts.conversions||[]).forEach(function(c){ h+='<div class="flex justify-between"><span>'+e(c.status)+'</span><span>'+c.cnt+' / '+Number(c.total||0).toLocaleString('ru-RU')+' ₽</span></div>'; });
    h+='</div></div>';
    function renderSample(title, rows, formatter){
      var out='<div class="bg-white rounded-xl border p-4 mb-4"><h4 class="font-semibold mb-2">'+title+'</h4>';
      if(!rows||!rows.length){ out+='<p class="text-sm text-gray-400">Нет данных</p></div>'; return out; }
      out+='<div class="max-h-40 overflow-y-auto space-y-1 text-xs font-mono">';
      rows.forEach(function(r){ out+='<div class="bg-gray-50 rounded px-2 py-1">'+formatter(r)+'</div>'; });
      out+='</div></div>'; return out;
    }
    h+=renderSample('Последние просмотры', d.samples.views, function(r){ return (r.dt||'')+' • '+(r.page||'')+' • '+(r.ip||''); });
    h+=renderSample('Последние клики', d.samples.clicks, function(r){ return (r.dt||'')+' • id='+r.id+' • ip='+(r.ip||'')+' • utm='+(r.utm_source||''); });
    h+=renderSample('Последние конверсии', d.samples.conversions, function(r){ return (r.dt||'')+' • id='+r.id+' • '+r.status+' • payout='+(r.payout||0)+' • click_id='+(r.click_id||'')+' • aff_sub='+(r.aff_sub||''); });
    modal(h);
  }).catch(function(){ alert('Ошибка debug-проверки'); });
}


/* ============ LINK CHECKS ============ */
function lLinks(){
var el=document.getElementById('p-links');
el.innerHTML='<div class="text-center py-12"><p class="text-gray-500">⏳ Загрузка...</p></div>';
ap('/link-checks?action=list').then(d=>{
var s=d.summary||{}; var items=d.items||[];
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🔗 Партнёрские ссылки</h2><div class="flex gap-2"><button onclick="runLinkChecks()" class="btn-p text-sm">Проверить все</button></div></div>';

h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-gray-800">'+(s.total||0)+'</p><p class="text-xs text-gray-500">Всего</p></div>';
h+='<div class="bg-green-50 rounded-xl border border-green-100 p-4 text-center"><p class="text-2xl font-bold text-green-600">'+(s.ok||0)+'</p><p class="text-xs text-gray-500">Работают</p></div>';
h+='<div class="bg-red-50 rounded-xl border border-red-100 p-4 text-center"><p class="text-2xl font-bold text-red-600">'+(s.broken||0)+'</p><p class="text-xs text-gray-500">Битые/ошибки</p></div>';
h+='<div class="bg-yellow-50 rounded-xl border border-yellow-100 p-4 text-center"><p class="text-2xl font-bold text-yellow-600">'+(s.unchecked||0)+'</p><p class="text-xs text-gray-500">Не проверены</p></div>';
h+='</div>';

if(items.length){
h+='<div class="bg-white rounded-2xl border shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Оффер</th><th class="p-3 text-left">Статус</th><th class="p-3 text-left">HTTP</th><th class="p-3 text-left">Проверено</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
items.forEach(function(it){
  var badge='';
  if(!it.checked_at) badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-700">Не проверен</span>';
  else if(Number(it.is_ok)===1) badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-700">OK</span>';
  else if(it.error_message&&it.error_message.includes('Антибот')) badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">OK*</span>';else if(it.error_message&&it.error_message.includes('Таймаут/JS-редирект')) badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">OK*</span>';else if(it.error_message&&it.error_message.includes('не считается битой')) badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700">OK*</span>';else badge='<span class="px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700">Ошибка</span>';
  var code=it.http_code?String(it.http_code):'—';
  var checked=it.checked_at?new Date(it.checked_at).toLocaleString('ru-RU'):'—';
  var title=it.error_message?('Ошибка: '+it.error_message):(it.final_url||it.affiliate_url||'');
  h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 min-w-[280px]"><div class="font-medium text-gray-900">'+e(it.title||'—')+'</div><div class="text-xs text-gray-400 break-all" title="'+e(title)+'">'+e((it.final_url||it.affiliate_url||'').substring(0,90))+( (it.final_url||it.affiliate_url||'').length>90?'...':'')+'</div></td><td class="p-3">'+badge+'</td><td class="p-3 font-mono text-xs">'+e(code)+'</td><td class="p-3 text-xs text-gray-500 whitespace-nowrap">'+e(checked)+'</td><td class="p-3 text-right"><button onclick="runLinkChecks('+it.offer_id+')" class="text-blue-600 hover:underline text-sm">Проверить</button></td></tr>';
});
h+='</tbody></table></div></div>';
} else {
 h+='<div class="bg-white rounded-xl border p-8 text-center text-gray-500">Нет данных для проверки</div>';
}

el.innerHTML=h;
});}
function runLinkChecks(offerId){
var text=offerId?'Проверить эту ссылку?':'Проверить все партнёрские ссылки?';
if(!confirm(text)) return;
ap('/link-checks?action=run',{method:'POST',body:JSON.stringify({offerId:offerId||0})}).then(function(d){
  if(d.success) alert('Проверено: '+d.checked+'; проблемных: '+d.broken);
  else alert(d.error||'Ошибка');
  lLinks();
});}

/* ============ CONVERSIONS / POSTBACK ============ */
var _convPeriod=30;
function loadPbProfiles(){
fetch(A+'/postback-profiles?action=list').then(r=>r.json()).then(profiles=>{
var el=document.getElementById('pb-profiles-list');if(!el)return;
var base=location.origin+'/api/postback';
var params='click_id={click_id}&status={status}&payout={payout}&ip={ip}&offer_id={offer_id}&transaction_id={transaction_id}&aff_sub={aff_sub}&goal_id={goal_id}';
var h='';
if(!profiles.length){
h+='<div class="bg-blue-50 rounded-lg p-3 mb-3"><p class="text-xs text-blue-700 mb-1 font-semibold">Универсальный URL (без привязки к партнёрке):</p><div class="bg-white rounded p-2 font-mono text-xs text-gray-800 break-all select-all border">'+base+'?'+params+'</div></div>';
} else {
profiles.forEach(pr=>{
var url=base+'?source='+encodeURIComponent(pr.name)+'&'+params;
h+='<div class="bg-gray-50 rounded-lg p-3 mb-3 border"><div class="flex items-center justify-between mb-2"><span class="font-semibold text-sm text-gray-900">'+e(pr.name)+'</span><button onclick="pbProfileDel('+pr.id+',\''+e(pr.name).replace(/'/g,"\\'")+'\''+')" class="text-red-400 hover:text-red-600 text-xs">Удалить</button></div>';
if(pr.notes)h+='<p class="text-xs text-gray-500 mb-2">'+e(pr.notes)+'</p>';
h+='<div class="bg-white rounded p-2 font-mono text-xs text-gray-800 break-all select-all border">'+url+'</div></div>';
});
// Универсальный URL
h+='<div class="bg-blue-50 rounded-lg p-3 mt-3"><p class="text-xs text-blue-700 mb-1 font-semibold">Универсальный URL (без привязки):</p><div class="bg-white rounded p-2 font-mono text-xs text-gray-800 break-all select-all border">'+base+'?'+params+'</div></div>';
}
el.innerHTML=h;
});}

function pbProfileForm(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Добавить партнёрку</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return pbProfileSave(event)">'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Название партнёрки *</label><input id="pb-name" class="input-f" required placeholder="Admitad, ActionPay, ..."></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Slug (латиница, авто)</label><input id="pb-slug" class="input-f" placeholder="admitad"></div>'+
'<div class="mb-4"><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="pb-notes" class="input-f" rows="2" placeholder="Где вставить postback URL, особенности..."></textarea></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Добавить</button></div></form>');
}
function pbProfileSave(ev){ev.preventDefault();
fetch(A+'/postback-profiles?action=create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
name:document.getElementById('pb-name').value,slug:document.getElementById('pb-slug').value,notes:document.getElementById('pb-notes').value
})}).then(r=>r.json()).then(d=>{if(d.error){alert(d.error);return;}cm();lConv();});return false;}
function pbProfileDel(id,name){if(confirm('Удалить профиль '+name+'?'))fetch(A+'/postback-profiles?action=delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}).then(()=>lConv());}

function lConv(){
var p=_convPeriod;
ap('/postback?period='+p).then(d=>{
var s=d.stats||{};
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">💰 Конверсии (Postback)</h2><div class="flex gap-2"><select id="conv-period" onchange="_convPeriod=+this.value;lConv()" class="sel-f text-sm w-auto"><option value="7"'+(p==7?' selected':'')+'>7 дней</option><option value="14"'+(p==14?' selected':'')+'>14 дней</option><option value="30"'+(p==30?' selected':'')+'>30 дней</option><option value="90"'+(p==90?' selected':'')+'>90 дней</option></select><button onclick="lConv()" class="text-sm text-blue-600 hover:underline">🔄</button></div></div>';

// Сводка
h+='<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-gray-800">'+(s.total||0)+'</p><p class="text-xs text-gray-500">Всего</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">'+(s.approved||0)+'</p><p class="text-xs text-gray-500">Одобрено</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-red-600">'+(s.rejected||0)+'</p><p class="text-xs text-gray-500">Отклонено</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-yellow-600">'+(Number(s.pending||0)+Number(s.hold_cnt||0))+'</p><p class="text-xs text-gray-500">В ожидании</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+Number(s.total_payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</p><p class="text-xs text-gray-500">Доход</p></div>';
h+='</div>';

// Postback профили
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6"><div class="flex justify-between items-center mb-4"><h3 class="font-bold text-gray-900">🔗 Postback URL-ы для партнёрок</h3><button onclick="pbProfileForm()" class="btn-p text-sm">+ Добавить партнёрку</button></div><div id="pb-profiles-list"><p class="text-xs text-gray-400">Загрузка...</p></div></div>';
loadPbProfiles();

// По офферам
if(d.byOffer&&d.byOffer.length){
h+='<div class="bg-white rounded-xl border mb-6"><div class="p-4 border-b"><h3 class="font-semibold">По офферам</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Оффер</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Кол-во</th><th class="p-3 text-right">Сумма</th></tr></thead><tbody>';
d.byOffer.forEach(r=>{
var stBadge=r.status==='approved'?'bg-green-100 text-green-700':r.status==='rejected'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-medium">'+e(r.title||'—')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+stBadge+'">'+e(r.status)+'</span></td><td class="p-3 text-right">'+r.cnt+'</td><td class="p-3 text-right font-semibold">'+Number(r.sum_payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</td></tr>';});
h+='</tbody></table></div></div>';}

// Список конверсий
if(d.conversions&&d.conversions.length){
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b"><h3 class="font-semibold">Последние конверсии</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Оффер</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Выплата</th><th class="p-3 text-left">IP конверсии</th><th class="p-3 text-left">Источник</th><th class="p-3 text-left text-xs">Click ID</th></tr></thead><tbody>';
d.conversions.forEach(c=>{
var stBadge=c.status==='approved'?'bg-green-100 text-green-700':c.status==='rejected'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 text-xs text-gray-500 whitespace-nowrap">'+new Date(c.created_at).toLocaleString('ru-RU')+'</td><td class="p-3 font-medium text-sm">'+e(c.offer_title||c.external_offer_id||'—')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+stBadge+'">'+e(c.status)+'</span></td><td class="p-3 text-right font-semibold">'+Number(c.payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</td><td class="p-3 font-mono text-xs text-gray-500">'+(e(c.ip)||'—')+'</td><td class="p-3 text-xs">'+(c.source?'<span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded">'+e(c.source)+'</span>':'<span class="text-gray-300">—</span>')+'</td><td class="p-3 font-mono text-xs text-gray-400">'+(e(c.click_id||c.aff_sub||'—').substring(0,16))+'</td></tr>';});
h+='</tbody></table></div></div>';
}else{h+='<div class="bg-white rounded-xl border p-8 text-center text-gray-500"><p>Конверсий пока нет. Настройте Postback URL в leads.su.</p></div>';}

document.getElementById('p-conversions').innerHTML=h;});}

/* ============ A/B TESTS ============ */
function lAB(){ap('/ab-tests').then(tests=>{
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🧪 A/B тесты кнопки «Оформить»</h2><button onclick="abForm()" class="btn-p text-sm">+ Новый тест</button></div>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-700"><strong>Как работает:</strong> Каждому посетителю случайно назначается вариант кнопки (цвет + текст). Вариант сохраняется в куке на 30 дней. Считаются показы и клики. Активным может быть только один тест.</div>';

if(!tests.length){h+='<p class="text-gray-500 text-center py-8">Нет тестов. Создайте первый!</p>';}

tests.forEach(t=>{
var active=t.is_active;
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6">';
h+='<div class="flex items-center justify-between mb-4"><div><h3 class="font-bold text-gray-900">'+e(t.name)+'</h3><p class="text-xs text-gray-400">Создан: '+new Date(t.created_at).toLocaleDateString('ru-RU')+' • Scope: '+(t.category_scope==='all'?'Все категории':(CL[t.category_scope]||t.category_scope))+'</p></div>';
h+='<div class="flex items-center gap-2"><span class="px-2 py-1 rounded text-xs font-semibold '+(active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(active?'Активен':'Выключен')+'</span>';
h+='<button onclick="abToggle('+t.id+','+(active?0:1)+')" class="text-sm text-blue-600 hover:underline">'+(active?'Выключить':'Включить')+'</button>';
h+='<button onclick="abReset('+t.id+')" class="text-sm text-yellow-600 hover:underline">Сбросить</button>';
h+='<button onclick="abDel('+t.id+')" class="text-sm text-red-500 hover:underline">Удалить</button></div></div>';

if(t.variants&&t.variants.length){
var totalImp=t.variants.reduce(function(s,v){return s+Number(v.impressions);},0);
var totalClk=t.variants.reduce(function(s,v){return s+Number(v.clicks);},0);
var maxRate=Math.max.apply(null,t.variants.map(function(v){return v.impressions>0?v.clicks/v.impressions:0;}));

h+='<div class="grid gap-3">';
t.variants.forEach(function(v){
var imp=Number(v.impressions),clk=Number(v.clicks);
var rate=imp>0?((clk/imp)*100).toFixed(1):0;
var isWinner=imp>20&&maxRate>0&&(clk/Math.max(imp,1))>=maxRate*0.99;

h+='<div class="flex items-center gap-4 bg-gray-50 rounded-lg p-4 border'+(isWinner&&imp>20?' border-green-300 bg-green-50':'')+'">';
h+='<div style="background:'+v.color+'" class="text-white px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap min-w-[130px] text-center">'+e(v.label)+' →</div>';
h+='<div class="flex-1 grid grid-cols-3 gap-4 text-center">';
h+='<div><p class="text-lg font-bold text-gray-700">'+imp+'</p><p class="text-xs text-gray-400">Показов</p></div>';
h+='<div><p class="text-lg font-bold text-blue-600">'+clk+'</p><p class="text-xs text-gray-400">Кликов</p></div>';
h+='<div><p class="text-lg font-bold '+(parseFloat(rate)>=5?'text-green-600':'text-gray-600')+'">'+rate+'%</p><p class="text-xs text-gray-400">Конверсия</p></div>';
h+='</div>';
if(isWinner&&imp>20)h+='<span class="text-green-600 text-xs font-semibold whitespace-nowrap">🏆 Лидер</span>';
h+='</div>';
});
h+='</div>';

h+='<div class="mt-4 pt-4 border-t flex items-center justify-between text-sm text-gray-500"><span>Всего: '+totalImp+' показов, '+totalClk+' кликов</span>';
if(totalImp>50){var best=t.variants.reduce(function(a,b){var ra=a.impressions>0?a.clicks/a.impressions:0;var rb=b.impressions>0?b.clicks/b.impressions:0;return ra>=rb?a:b;});
h+='<span class="text-green-700 font-semibold">🏆 Лучший вариант: <span style="background:'+best.color+';padding:2px 8px;border-radius:6px;color:#fff;font-size:12px">'+e(best.label)+'</span> ('+((best.impressions>0?best.clicks/best.impressions*100:0).toFixed(1))+'%)</span>';}
h+='</div>';
}
h+='</div>';
});

document.getElementById('p-ab').innerHTML=h;});}

function abDefaultVariants(scope){
if(scope==='credits') return [{label:'Оформить кредит',color:'#1a56db'},{label:'Получить кредит',color:'#059669'},{label:'Подать заявку',color:'#7c3aed'}];
if(scope==='credit_cards') return [{label:'Оформить карту',color:'#1a56db'},{label:'Получить карту',color:'#059669'},{label:'Оформить кредитку',color:'#7c3aed'}];
if(scope==='debit_cards') return [{label:'Заказать карту',color:'#1a56db'},{label:'Оформить карту',color:'#059669'},{label:'Выбрать карту',color:'#7c3aed'}];
if(scope==='microloans') return [{label:'Получить займ',color:'#059669'},{label:'Оформить займ',color:'#1a56db'},{label:'Оформить заявку',color:'#7c3aed'}];
return [{label:'Оформить',color:'#059669'},{label:'Получить деньги',color:'#1a56db'},{label:'Подать заявку',color:'#7c3aed'}];
}
function abRenderVars(scope){
var vars=abDefaultVariants(scope);
var box=document.getElementById('ab-vars');
if(!box)return;
box.innerHTML=vars.map(function(v){return '<div class="flex gap-2 mb-2"><input class="input-f flex-1 ab-label" value="'+e(v.label)+'" placeholder="Текст кнопки"><input type="color" class="ab-color w-12 h-9 rounded cursor-pointer" value="'+v.color+'"><button type="button" onclick="this.closest(\'.flex\').remove()" class="text-red-400 hover:text-red-600">&times;</button></div>';}).join('');
}
function abForm(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Новый A/B тест</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return abSave(event)">'+
'<div class="mb-4"><label class="block text-xs font-medium mb-1">Название теста</label><input id="ab-name" class="input-f" value="Тест кнопки" required></div>'+
'<div class="mb-4"><label class="block text-xs font-medium mb-1">Категория</label><select id="ab-scope" class="sel-f" onchange="abRenderVars(this.value)"><option value="all">Все категории</option><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>'+
'<div class="mb-4"><label class="block text-xs font-medium mb-2">Варианты кнопки</label><div id="ab-vars"></div><button type="button" onclick="abAddVar()" class="text-sm text-blue-600 hover:underline mb-4">+ Добавить вариант</button></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Создать и активировать</button></div></form>');
abRenderVars('all');
}
function abAddVar(){
document.getElementById('ab-vars').insertAdjacentHTML('beforeend','<div class="flex gap-2 mb-2"><input class="input-f flex-1 ab-label" placeholder="Текст кнопки"><input type="color" class="ab-color w-12 h-9 rounded cursor-pointer" value="#059669"><button type="button" onclick="this.closest(\'.flex\').remove()" class="text-red-400 hover:text-red-600">&times;</button></div>');}
function abSave(ev){ev.preventDefault();
var vars=[];document.querySelectorAll('#ab-vars .flex').forEach(function(row){
var label=row.querySelector('.ab-label').value.trim();
var color=row.querySelector('.ab-color').value;
if(label)vars.push({label:label,color:color});});
if(vars.length<2){alert('Нужно минимум 2 варианта');return false;}
ap('/ab-tests',{method:'POST',body:JSON.stringify({name:document.getElementById('ab-name').value,categoryScope:document.getElementById('ab-scope').value,isActive:true,variants:vars})}).then(function(){cm();lAB();});return false;}
function abToggle(id,v){ap('/ab-tests/'+id,{method:'PUT',body:JSON.stringify({isActive:!!v})}).then(function(){lAB();});}
function abReset(id){if(confirm('Сбросить счётчики?'))ap('/ab-tests/'+id+'/reset',{method:'POST'}).then(function(){lAB();});}
function abDel(id){if(confirm('Удалить тест?'))ap('/ab-tests/'+id,{method:'DELETE'}).then(function(){lAB();});}


/* ============ SUBSCRIBERS & NEWSLETTERS ============ */
function lSu(){
Promise.all([ap('/subscribers'),ap('/newsletters')]).then(([subs,nls])=>{
var active=subs.filter(s=>s.is_active);
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">📬 Подписчики и рассылки</h2><div class="flex gap-2"><span class="text-sm text-gray-500 mt-1">Активных: <strong>'+active.length+'</strong> из '+subs.length+'</span></div></div>';

// Рассылки
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6"><div class="flex justify-between items-center mb-4"><h3 class="font-bold text-gray-900">✉️ Рассылки</h3><div class="flex gap-2"><button onclick="nlSendLog()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">📋 Лог</button><button onclick="nlQuickTest()" class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-3 py-1.5 rounded-lg text-xs font-semibold">🧪 Тестовая</button><button onclick="nlForm()" class="btn-p text-sm">+ Создать</button></div></div>';
if(nls.length){
h+='<div class="space-y-3">';
nls.forEach(n=>{
var st={draft:'<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">Черновик</span>',sending:'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">Отправка...</span>',sent:'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Отправлено</span>',failed:'<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">Ошибка</span>'};
h+='<div class="bg-gray-50 rounded-lg border p-4"><div class="flex items-center justify-between mb-2"><div class="flex-1 min-w-0"><p class="font-semibold text-sm text-gray-900">'+e(n.subject||'Без темы')+'</p><p class="text-xs text-gray-400">'+new Date(n.created_at).toLocaleString('ru-RU')+(n.sent_at?' • Отправлено: '+new Date(n.sent_at).toLocaleString('ru-RU'):'')+'</p></div><div class="flex items-center gap-2">'+((st[n.status]||''))+'</div></div>';
h+='<div class="flex items-center gap-2 mt-2">';
if(n.status==='sent')h+='<span class="text-xs text-gray-500">✅ '+n.sent_count+' доставлено'+(n.failed_count>0?' / ❌ '+n.failed_count+' ошибок':'')+'</span>';
if(n.status==='sent')h+='<button onclick="nlStats('+n.id+')" class="text-blue-600 hover:underline text-xs">📊 Стат</button>';
if(n.status==='draft'){h+='<button onclick="nlForm('+JSON.stringify(n).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-xs">Ред.</button>';h+='<button onclick="nlSend('+n.id+')" class="text-green-600 hover:underline text-xs">📤 Отправить</button>';}
h+='<button onclick="nlTest('+n.id+')" class="text-orange-600 hover:underline text-xs">🧪 Тест</button>';
h+='<button onclick="nlPreview('+JSON.stringify(n).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-purple-600 hover:underline text-xs">Превью</button>';
h+='<button onclick="nlDel('+n.id+')" class="text-red-500 hover:underline text-xs">Удалить</button>';
h+='</div></div>';});
h+='</div>';
}else{h+='<p class="text-gray-500 text-sm">Нет рассылок. Создайте первую!</p>';}
h+='</div>';

// Подписчики
h+='<div class="bg-white rounded-xl border shadow-sm"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold text-gray-900">👥 Подписчики ('+subs.length+')</h3></div>';
if(subs.length){
h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Email</th><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
subs.forEach(s=>{
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-mono text-xs">'+e(s.email)+'</td><td class="p-3 text-xs text-gray-500">'+((s.created_at||s.subscribed_at)?new Date((s.created_at||s.subscribed_at).replace(' ','T')).toLocaleDateString('ru-RU'):'—')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold '+(s.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(s.is_active?'Активен':'Отписан')+'</span></td>';
h+='<td class="p-3 text-right space-x-2">';
h+='<button onclick="subToggle('+s.id+','+(s.is_active?0:1)+')" class="text-blue-600 hover:underline text-xs">'+(s.is_active?'Отключить':'Включить')+'</button>';
h+='<button onclick="subDel('+s.id+',\''+e(s.email)+'\')" class="text-red-500 hover:underline text-xs">Удалить</button>';
h+='</td></tr>';});
h+='</tbody></table></div>';
}else{h+='<p class="p-4 text-gray-500 text-sm">Нет подписчиков</p>';}
h+='</div>';

document.getElementById('p-subs').innerHTML=h;}).catch(function(err){document.getElementById('p-subs').innerHTML='<div class="bg-red-50 border border-red-200 rounded-xl p-6"><h2 class="text-xl font-bold text-red-700 mb-2">Ошибка загрузки подписчиков</h2><p class="text-sm text-red-600">'+e((err&&err.message)?err.message:'Неизвестная ошибка')+'</p></div>';});}

// Форма рассылки
function nlForm(n){
var f=n||{subject:'',body_html:''};var id=n?n.id:0;
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать рассылку':'Новая рассылка')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return nlSave(event,'+id+')">'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Тема письма *</label><div class="flex gap-2"><input id="nl-subj" class="input-f flex-1" value="'+e(f.subject||'')+'" required placeholder="Лучшие предложения недели"><button type="button" onclick="nlGenTopics()" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-purple-700 whitespace-nowrap" id="nl-topics-btn">🤖 Темы</button></div></div>'+
'<div id="nl-topics-list" class="hidden mb-3"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Содержание (HTML)</label><div class="flex gap-2 mb-2"><button type="button" onclick="nlGenBody()" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-purple-700" id="nl-genbody-btn">🤖 Сгенерировать текст</button><button type="button" onclick="nlPreviewInline()" class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs hover:bg-gray-300">👁 Превью</button></div><textarea id="nl-body" class="input-f font-mono text-xs" rows="14" placeholder="<h2>Заголовок</h2>\n<p>Текст письма...</p>">'+e(f.body_html||'')+'</textarea><div id="nl-preview-box" class="hidden mt-2 border rounded-lg p-4 bg-white"></div></div>'+
'<div class="bg-gray-50 rounded-lg p-3 mb-4 text-xs text-gray-500">💡 В шапку письма автоматически добавляется баннер <code>kosmo-rassil.jpg</code>. Вставьте <code>{{offers}}</code> в тело письма — туда подставятся карточки офферов с логотипами и кнопками. Ссылка отписки добавляется автоматически. Отправка с info@kosmozaim.ru</div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить черновик</button></div></form>');
}
function nlGenTopics(){
var btn=document.getElementById('nl-topics-btn');btn.disabled=true;btn.textContent='⏳...';
ap('/newsletter-generate',{method:'POST',body:JSON.stringify({action:'topics'})}).then(d=>{
btn.disabled=false;btn.textContent='🤖 Темы';
if(d.error){alert(d.error);return;}
var box=document.getElementById('nl-topics-list');box.classList.remove('hidden');
box.innerHTML='<p class="text-xs text-gray-500 mb-2">Выберите тему:</p><div class="flex flex-wrap gap-2">'+d.topics.map(t=>'<button type="button" onclick="document.getElementById(\'nl-subj\').value=this.textContent;document.getElementById(\'nl-topics-list\').classList.add(\'hidden\')" class="bg-purple-50 border border-purple-200 text-purple-700 px-3 py-1.5 rounded-lg text-xs hover:bg-purple-100 text-left">'+e(t)+'</button>').join('')+'</div>';
}).catch(()=>{btn.disabled=false;btn.textContent='🤖 Темы';alert('Ошибка');});}
function nlGenBody(){
var subj=document.getElementById('nl-subj').value.trim();
if(!subj){alert('Сначала укажите тему письма');return;}
var btn=document.getElementById('nl-genbody-btn');btn.disabled=true;btn.textContent='⏳ Генерация...';
ap('/newsletter-generate',{method:'POST',body:JSON.stringify({action:'body',subject:subj})}).then(d=>{
btn.disabled=false;btn.textContent='🤖 Сгенерировать текст';
if(d.error){alert(d.error);return;}
document.getElementById('nl-body').value=d.html;
}).catch(()=>{btn.disabled=false;btn.textContent='🤖 Сгенерировать текст';alert('Ошибка');});}
function nlPreviewInline(){
var box=document.getElementById('nl-preview-box');
var body=document.getElementById('nl-body').value;
var offersPlaceholder='<div style="background:#f0fdf4;border:2px dashed #86efac;border-radius:12px;padding:16px;margin:16px 0;text-align:center"><p style="color:#166534;font-size:13px;margin:0">📋 Карточки офферов (логотип + название + кнопка «Оформить»)</p></div>';
var brandHeader='<div style="margin:0 0 24px 0;text-align:center;background:#f8fafc;border-radius:12px;overflow:hidden"><img src="'+SITE_URL+'/images/kosmo-rassil.jpg" alt="'+siteName+'" style="display:block;width:100%;max-width:600px;height:auto;border:0;margin:0 auto"></div>';
body=brandHeader+body.replace(/\{\{offers\}\}/g, offersPlaceholder);
if(box.classList.contains('hidden')){box.classList.remove('hidden');box.innerHTML=body;}
else{box.classList.add('hidden');}}
function nlSave(ev,id){ev.preventDefault();
var d={subject:document.getElementById('nl-subj').value,bodyHtml:document.getElementById('nl-body').value};
ap(id?'/newsletters/'+id:'/newsletters',{method:id?'PUT':'POST',body:JSON.stringify(d)}).then(()=>{cm();lSu();});return false;}
function nlSend(id){if(!confirm('Отправить рассылку всем активным подписчикам?'))return;
ap('/newsletters/'+id+'/send',{method:'POST'}).then(d=>{
if(d.success)alert('Отправлено: '+d.sent+' из '+d.total+(d.failed?' (ошибок: '+d.failed+')':''));
else alert(d.error||'Ошибка');lSu();});}
function nlPreview(n){
var body=n.body_html||'';
var offersPlaceholder='<div style="background:#f9fafb;border:2px dashed #d1d5db;border-radius:12px;padding:16px;margin:16px 0;text-align:center"><p style="color:#6b7280;font-size:13px;margin:0">📋 Здесь будут карточки офферов с логотипами и кнопками «Оформить»</p></div>';
body=body.replace(/\{\{offers\}\}/g, offersPlaceholder);
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Превью: '+e(n.subject)+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<div class="border rounded-xl p-6 bg-white" style="max-width:600px;margin:0 auto;font-family:-apple-system,sans-serif">'+body+'<br><hr style="border:none;border-top:1px solid #eee;margin:24px 0"><p style="font-size:12px;color:#999;text-align:center">Вы получили это письмо от '+siteName+'.<br><a href="#" style="color:#999">Отписаться от рассылки</a></p></div>');}
function nlDel(id){if(confirm('Удалить рассылку?'))ap('/newsletters/'+id,{method:'DELETE'}).then(()=>lSu());}
function nlTest(id){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧪 Тестовая отправка</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Email для тестового письма</label><input id="nl-test-email" class="input-f" type="email" placeholder="ваш@email.ru" required></div>'+
'<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700"><p>⚠️ Письмо будет отправлено с пометкой <strong>[ТЕСТ]</strong> в теме и баннером внутри. Ссылки НЕ трекаются.</p></div>'+
'<div class="flex justify-end gap-3"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="nlTestSend('+id+')" id="nl-test-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold">🚀 Отправить тест</button></div></div>');
}
function nlTestSend(id){
var email=document.getElementById('nl-test-email').value.trim();
if(!email){alert('Введите email');return;}
var btn=document.getElementById('nl-test-btn');
btn.disabled=true;btn.textContent='⏳ Отправка...';
ap('/newsletters/'+id+'/test',{method:'POST',body:JSON.stringify({email:email})}).then(function(d){
btn.disabled=false;btn.textContent='🚀 Отправить тест';
if(d.success){alert('✅ '+(d.message||'Отправлено'));cm();}
else alert('❌ '+(d.error||'Ошибка'));
}).catch(function(){btn.disabled=false;btn.textContent='🚀 Отправить тест';alert('Ошибка соединения');});}
function nlSendLog(nlId){
var url='/newsletter-send-log';
if(nlId)url+='?newsletter_id='+nlId;
ap(url).then(function(d){
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📋 Лог отправки рассылок</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';
var s=d.stats||{};
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">';
h+='<div class="bg-blue-50 rounded-lg p-3 text-center"><p class="text-lg font-bold text-blue-600">'+(s.total_sends||0)+'</p><p class="text-xs text-gray-500">Всего</p></div>';
h+='<div class="bg-green-50 rounded-lg p-3 text-center"><p class="text-lg font-bold text-green-600">'+(s.total_sent||0)+'</p><p class="text-xs text-gray-500">Доставлено</p></div>';
h+='<div class="bg-red-50 rounded-lg p-3 text-center"><p class="text-lg font-bold text-red-600">'+(s.total_failed||0)+'</p><p class="text-xs text-gray-500">Ошибок</p></div>';
h+='<div class="bg-orange-50 rounded-lg p-3 text-center"><p class="text-lg font-bold text-orange-600">'+(s.total_test||0)+'</p><p class="text-xs text-gray-500">Тестовых</p></div>';
h+='</div>';
h+='<div class="max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Дата</th>';
h+='<th class="px-3 py-2 text-left">Рассылка</th>';
h+='<th class="px-3 py-2 text-left">Email</th>';
h+='<th class="px-3 py-2 text-left">Статус</th>';
h+='</tr></thead><tbody>';
if(!d.logs||!d.logs.length){
h+='<tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Нет записей</td></tr>';
}else{
d.logs.forEach(function(l){
var dt=new Date(l.sent_at);
var dtStr=dt.toLocaleDateString('ru-RU')+' '+dt.toLocaleTimeString('ru-RU',{hour:'2-digit',minute:'2-digit'});
var badge=l.status==='sent'?'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">✅ OK</span>':
'<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs" title="'+e(l.error_message||'')+'">❌ Ошибка</span>';
var testBadge=l.is_test==1?' <span class="bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded text-xs">тест</span>':'';
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 text-gray-500 whitespace-nowrap text-xs">'+dtStr+'</td>';
h+='<td class="px-3 py-2 text-xs truncate max-w-40">'+e(l.newsletter_subject||'#'+l.newsletter_id)+'</td>';
h+='<td class="px-3 py-2 font-mono text-xs">'+e(l.email)+testBadge+'</td>';
h+='<td class="px-3 py-2">'+badge+'</td>';
h+='</tr>';
});
}
h+='</tbody></table></div>';
if(d.total>100)h+='<p class="text-xs text-gray-400 mt-2 text-center">Показано 100 из '+d.total+'</p>';
modal(h);
}).catch(function(err){alert('Ошибка: '+err.message);});}
function nlQuickTest(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧪 Быстрая тестовая рассылка</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<div class="space-y-4">'+
'<div><label class="block text-sm font-medium mb-1">Тема письма</label><input id="qt-subj" class="input-f" value="Тестовая рассылка" placeholder="Тема письма"></div>'+
'<div><label class="block text-sm font-medium mb-1">Содержание (HTML)</label><textarea id="qt-body" class="input-f font-mono text-xs" rows="6" placeholder="<h2>Заголовок</h2><p>Текст...</p>"><h2>Тестовое письмо</h2><p>Проверка рассылки '+siteName+'.</p><p>{{offers}}</p></textarea></div>'+
'<div><label class="block text-sm font-medium mb-1">Email получателя *</label><input id="qt-email" class="input-f" type="email" placeholder="ваш@email.ru" required></div>'+
'<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-700">⚠️ Создаст черновик и сразу отправит тестовое письмо с пометкой [ТЕСТ].</div>'+
'<div class="flex justify-end gap-3"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="nlQuickTestSend()" id="qt-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-semibold">🚀 Отправить тест</button></div>'+
'</div>');
}
function nlQuickTestSend(){
var subj=document.getElementById('qt-subj').value.trim();
var body=document.getElementById('qt-body').value;
var email=document.getElementById('qt-email').value.trim();
if(!email){alert('Введите email');return;}
if(!subj)subj='Тестовая рассылка';
var btn=document.getElementById('qt-btn');
btn.disabled=true;btn.textContent='⏳ Создаю и отправляю...';
ap('/newsletters',{method:'POST',body:JSON.stringify({subject:subj,bodyHtml:body})}).then(function(d){
if(!d.success&&!d.id){btn.disabled=false;btn.textContent='🚀 Отправить тест';alert('Ошибка создания: '+(d.error||''));return;}
var nlId=d.id;
return ap('/newsletters/'+nlId+'/test',{method:'POST',body:JSON.stringify({email:email})});
}).then(function(d){
btn.disabled=false;btn.textContent='🚀 Отправить тест';
if(!d)return;
if(d.success){alert('✅ '+(d.message||'Тестовое письмо отправлено!'));cm();lSu();}
else alert('❌ '+(d.error||'Ошибка отправки'));
}).catch(function(err){btn.disabled=false;btn.textContent='🚀 Отправить тест';alert('Ошибка: '+err.message);});}

function nlStats(id){
ap('/newsletters/'+id+'/stats').then(d=>{
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📊 Статистика рассылки</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';

h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+d.sentCount+'</p><p class="text-xs text-gray-500">Отправлено</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">'+d.opens+'</p><p class="text-xs text-gray-500">Прочитано</p><p class="text-lg font-semibold text-green-500">'+d.openRate+'%</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-purple-600">'+d.uniqueClicks+'</p><p class="text-xs text-gray-500">Кликнули</p><p class="text-lg font-semibold text-purple-500">'+d.clickRate+'%</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-gray-700">'+d.clicks+'</p><p class="text-xs text-gray-500">Всего кликов</p></div>';
h+='</div>';

// Воронка
var barOpen=d.sentCount>0?Math.round(d.opens/d.sentCount*100):0;
var barClick=d.sentCount>0?Math.round(d.uniqueClicks/d.sentCount*100):0;
h+='<div class="bg-white rounded-xl border p-4 mb-6"><h4 class="font-semibold text-sm text-gray-700 mb-3">Воронка</h4>';
h+='<div class="space-y-3">';
h+='<div><div class="flex justify-between text-xs mb-1"><span>Отправлено</span><span>'+d.sentCount+'</span></div><div class="bg-gray-200 rounded-full h-3"><div class="bg-blue-500 rounded-full h-3" style="width:100%"></div></div></div>';
h+='<div><div class="flex justify-between text-xs mb-1"><span>Прочитано</span><span>'+d.opens+' ('+d.openRate+'%)</span></div><div class="bg-gray-200 rounded-full h-3"><div class="bg-green-500 rounded-full h-3" style="width:'+barOpen+'%"></div></div></div>';
h+='<div><div class="flex justify-between text-xs mb-1"><span>Кликнули</span><span>'+d.uniqueClicks+' ('+d.clickRate+'%)</span></div><div class="bg-gray-200 rounded-full h-3"><div class="bg-purple-500 rounded-full h-3" style="width:'+barClick+'%"></div></div></div>';
h+='</div></div>';

// Топ ссылок
if(d.topLinks&&d.topLinks.length){
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b"><h4 class="font-semibold text-sm">🔗 Топ ссылок по кликам</h4></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">URL</th><th class="p-3 text-right">Клики</th></tr></thead><tbody>';
d.topLinks.forEach(l=>{
var shortUrl=l.url.length>60?l.url.substring(0,60)+'...':l.url;
h+='<tr class="border-t"><td class="p-3 font-mono text-xs text-blue-600 break-all">'+e(shortUrl)+'</td><td class="p-3 text-right font-semibold">'+l.cnt+'</td></tr>';});
h+='</tbody></table></div></div>';}

modal(h);});}

function subToggle(id,v){ap('/subscribers/'+id,{method:'PUT',body:JSON.stringify({isActive:!!v})}).then(()=>lSu());}
function subDel(id,email){if(confirm('Удалить подписчика '+email+'?'))ap('/subscribers/'+id,{method:'DELETE'}).then(()=>lSu());}



function lYD(){}
function lLS(){}
function lEF(){}
loadBonusWithdrawAlert();
sw('offers');


/* ============ BACKUP ============ */
function lB(){ap2('/admin/backup').then(d=>{
let h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">💾 Резервные копии</h2><button onclick="bCreate()" class="btn-p" id="b-create-btn">+ Создать бэкап</button></div>';
h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6"><p class="text-blue-700 text-sm"><strong>ℹ️ Бэкап включает:</strong> базу данных, PHP-файлы, изображения, конфиги. При восстановлении .env и config.php НЕ перезаписываются (безопасность).</p></div>';
if(!d.backups||!d.backups.length){h+='<div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-500">Бэкапов пока нет</p></div>';}
else{h+='<div class="bg-white rounded-xl shadow-sm border overflow-hidden"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3">Файл</th><th class="text-left px-4 py-3">Размер</th><th class="text-left px-4 py-3">Дата</th><th class="text-right px-4 py-3">Действия</th></tr></thead><tbody>';
d.backups.forEach(b=>{h+='<tr class="border-b hover:bg-gray-50"><td class="px-4 py-3 font-medium">'+b.name+'</td><td class="px-4 py-3 text-gray-600">'+b.sizeHuman+'</td><td class="px-4 py-3 text-gray-600">'+b.date+'</td><td class="px-4 py-3 text-right space-x-2"><a href="/admin/backup?action=download&name='+encodeURIComponent(b.name)+'" class="text-blue-600 hover:underline text-sm">⬇️ Скачать</a> <button onclick="bRestore(\''+b.name+'\')" class="text-green-600 hover:underline text-sm">♻️ Восстановить</button> <button onclick="bDelete(\''+b.name+'\')" class="text-red-500 hover:underline text-sm">🗑 Удалить</button></td></tr>';});
h+='</tbody></table></div>';}
document.getElementById('p-backup').innerHTML=h;});}

function ap2(u,o){return fetch(u,{headers:{'Content-Type':'application/json'},...o}).then(r=>r.json());}

function bCreate(){
let btn=document.getElementById('b-create-btn');
btn.disabled=true;btn.textContent='⏳ Создание...';
ap2('/admin/backup?action=create',{method:'POST'}).then(d=>{
btn.disabled=false;btn.textContent='+ Создать бэкап';
if(d.success){alert('✅ Бэкап создан: '+d.backup+' ('+d.size+')');lB();}
else alert('❌ Ошибка: '+(d.error||''));
}).catch(()=>{btn.disabled=false;btn.textContent='+ Создать бэкап';alert('Ошибка');});}

function bRestore(name){
if(!confirm('⚠️ Восстановить из бэкапа "'+name+'"?\n\nБаза данных будет перезаписана!\nФайлы кода будут заменены.\n\nПродолжить?'))return;
ap2('/admin/backup?action=restore&name='+encodeURIComponent(name),{method:'POST'}).then(d=>{
if(d.success){alert('✅ '+(d.message||'Восстановлено')+(d.warnings?' (с предупреждениями)':''));location.reload();}
else alert('❌ '+(d.error||'Ошибка'));
}).catch(()=>alert('Ошибка'));}

function bDelete(name){
if(!confirm('Удалить бэкап "'+name+'"?'))return;
ap2('/admin/backup?name='+encodeURIComponent(name),{method:'DELETE'}).then(d=>{
if(d.success)lB();else alert(d.error||'Ошибка');
}).catch(()=>alert('Ошибка'));}



/* ============ CHANGE PASSWORD ============ */
function showChangePw(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔑 Смена пароля</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>'+
'<form onsubmit="return changePw(event)"><div class="space-y-4">'+
'<div><label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label><input type="password" id="pw-old" class="input-f" required></div>'+
'<div><label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label><input type="password" id="pw-new" class="input-f" required minlength="6"></div>'+
'<div><label class="block text-sm font-medium text-gray-700 mb-1">Повторите новый пароль</label><input type="password" id="pw-confirm" class="input-f" required minlength="6"></div>'+
'<div id="pw-err" class="hidden text-red-600 text-sm"></div>'+
'</div><div class="flex justify-end gap-3 mt-6"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" id="pw-btn" class="btn-p">Сохранить</button></div></form>');}

function changePw(ev){ev.preventDefault();
var o=document.getElementById('pw-old').value;
var n=document.getElementById('pw-new').value;
var c=document.getElementById('pw-confirm').value;
var err=document.getElementById('pw-err');
err.className='hidden';
if(n!==c){err.textContent='Пароли не совпадают';err.className='text-red-600 text-sm';return false;}
if(n.length<6){err.textContent='Минимум 6 символов';err.className='text-red-600 text-sm';return false;}
var btn=document.getElementById('pw-btn');btn.disabled=true;btn.textContent='Сохранение...';
ap('/change-password',{method:'POST',body:JSON.stringify({currentPassword:o,newPassword:n})}).then(d=>{
btn.disabled=false;btn.textContent='Сохранить';
if(d.success){cm();alert('✅ '+(d.message||'Пароль изменён'));}
else{err.textContent=d.error||'Ошибка';err.className='text-red-600 text-sm';}
}).catch(()=>{btn.disabled=false;btn.textContent='Сохранить';err.textContent='Ошибка соединения';err.className='text-red-600 text-sm';});
return false;}



/* ============ SCHEDULER ============ */
var schSettings={};
function lSch(){ap('/scheduler').then(d=>{
schSettings=d.settings||{};
var st=d.stats||{};
var h='<h2 class="text-xl font-bold mb-6">⏰ Планировщик автогенерации</h2>';

// Статистика
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+st.reviews_today+'</p><p class="text-xs text-gray-500">Отзывов сегодня</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+st.articles_today+'</p><p class="text-xs text-gray-500">Статей сегодня</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-lg font-bold">'+st.last_review+'</p><p class="text-xs text-gray-500">Посл. отзыв</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-lg font-bold">'+st.last_article+'</p><p class="text-xs text-gray-500">Посл. статья</p></div>';
h+='</div>';

// Форма настроек
h+='<form onsubmit="return schSave(event)" class="grid md:grid-cols-2 gap-6">';

// Отзывы
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">⭐ Автогенерация отзывов</h3>';
h+='<div class="mb-4"><label class="flex items-center gap-2"><input type="checkbox" id="sch-rev-en" '+(schSettings.reviews_enabled?'checked':'')+' class="w-4 h-4"><span class="font-medium">Включено</span></label></div>';
h+='<div class="mb-4"><label class="block text-sm font-medium mb-1">Отзывов в сутки</label><input type="number" id="sch-rev-cnt" class="input-f" min="0" max="50" value="'+(schSettings.reviews_per_day||5)+'"></div>';
h+='<div class="grid grid-cols-2 gap-3">';
h+='<div><label class="block text-sm font-medium mb-1">Начало (час)</label><input type="number" id="sch-rev-sh" class="input-f" min="0" max="23" value="'+(schSettings.review_start_hour||6)+'"></div>';
h+='<div><label class="block text-sm font-medium mb-1">Конец (час)</label><input type="number" id="sch-rev-eh" class="input-f" min="0" max="23" value="'+(schSettings.review_end_hour||22)+'"></div>';
h+='</div>';
h+='<p class="text-xs text-gray-500 mt-2">⚡ Отзывы НЕ будут создаваться с '+formatHour(schSettings.review_end_hour||22)+' до '+formatHour(schSettings.review_start_hour||6)+'</p>';
h+='</div>';

// Статьи
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">📰 Автогенерация статей</h3>';
h+='<div class="mb-4"><label class="flex items-center gap-2"><input type="checkbox" id="sch-art-en" '+(schSettings.articles_enabled?'checked':'')+' class="w-4 h-4"><span class="font-medium">Включено</span></label></div>';
h+='<div class="mb-4"><label class="block text-sm font-medium mb-1">Статей в сутки</label><input type="number" id="sch-art-cnt" class="input-f" min="0" max="10" value="'+(schSettings.articles_per_day||1)+'"></div>';
h+='<div class="grid grid-cols-2 gap-3">';
h+='<div><label class="block text-sm font-medium mb-1">Начало (час)</label><input type="number" id="sch-art-sh" class="input-f" min="0" max="23" value="'+(schSettings.article_start_hour||8)+'"></div>';
h+='<div><label class="block text-sm font-medium mb-1">Конец (час)</label><input type="number" id="sch-art-eh" class="input-f" min="0" max="23" value="'+(schSettings.article_end_hour||20)+'"></div>';
h+='</div>';
h+='<p class="text-xs text-gray-500 mt-2">⚡ Статьи НЕ будут создаваться с '+formatHour(schSettings.article_end_hour||20)+' до '+formatHour(schSettings.article_start_hour||8)+'</p>';
h+='</div>';

h+='</form>';

// Кнопки
h+='<div class="flex gap-3 mt-6">';
h+='<button onclick="schSave()" class="btn-p">💾 Сохранить настройки</button>';
h+='<button onclick="schReset()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🔄 Сбросить счётчики</button>';
h+='<button onclick="schTestReview()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">⭐ Тест: отзыв</button>';
h+='</div>';

// Инфо
h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6"><p class="text-blue-700 text-sm"><strong>ℹ️ Как работает:</strong> Система автоматически создаёт контент при каждом посещении сайта, если прошло достаточно времени. Отзывы равномерно распределяются в указанном временном окне. Часовой пояс: Москва.</p></div>';

// Лог последних запусков
if(st.last_fires&&st.last_fires.length){
h+='<div class="bg-white rounded-xl border p-4 mt-4"><h3 class="font-bold text-sm mb-2">📋 Последние запуски</h3><div class="max-h-40 overflow-y-auto text-xs font-mono text-gray-600 space-y-0.5">';
st.last_fires.forEach(function(line){
var color=line.includes('ERR')?'text-red-600':line.includes('OK')?'text-green-700':'text-gray-500';
h+='<div class="'+color+'">'+e(line)+'</div>';
});
h+='</div></div>';
}

document.getElementById('p-scheduler').innerHTML=h;
});}

function formatHour(h){return (h<10?'0':'')+h+':00';}

function schSave(ev){if(ev)ev.preventDefault();
var data={
reviews_enabled:document.getElementById('sch-rev-en').checked,
reviews_per_day:parseInt(document.getElementById('sch-rev-cnt').value)||0,
review_start_hour:parseInt(document.getElementById('sch-rev-sh').value)||0,
review_end_hour:parseInt(document.getElementById('sch-rev-eh').value)||23,
articles_enabled:document.getElementById('sch-art-en').checked,
articles_per_day:parseInt(document.getElementById('sch-art-cnt').value)||0,
article_start_hour:parseInt(document.getElementById('sch-art-sh').value)||0,
article_end_hour:parseInt(document.getElementById('sch-art-eh').value)||23
};
ap('/scheduler',{method:'POST',body:JSON.stringify(data)}).then(d=>{
if(d.success)alert('✅ Настройки сохранены');else alert('❌ '+(d.error||'Ошибка'));
lSch();
});return false;}

function schReset(){if(!confirm('Сбросить счётчики за сегодня?'))return;
ap('/scheduler',{method:'DELETE'}).then(d=>{alert(d.message||'Готово');lSch();});}

function schTestReview(){
ap('/generate-review',{method:'POST'}).then(d=>{
if(d.success)alert('✅ Отзыв создан:\n'+d.review.name+' → '+d.review.offer+'\n⭐ '+d.review.rating+'/5\n\n'+d.review.comment);
else alert('❌ '+(d.error||'Ошибка'));
lSch();
});}





/* ============ TWO-FACTOR AUTH ============ */
function show2FA(){
ap('/two-factor').then(function(d){
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔐 Двухфакторная авторизация</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';

if(d.enabled){
h+='<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 text-center">';
h+='<span class="text-3xl">✅</span>';
h+='<p class="font-bold text-green-700 mt-2">2FA включена</p>';
h+='<p class="text-sm text-green-600">Резервных кодов осталось: <strong>'+d.backup_codes_remaining+'</strong></p>';
h+='</div>';
h+='<div class="space-y-3">';
h+='<button onclick="tfa_regenBackup()" class="w-full bg-blue-100 hover:bg-blue-200 text-blue-700 py-2 rounded-lg text-sm font-semibold">🔄 Перегенерировать резервные коды</button>';
h+='<button onclick="tfa_disable()" class="w-full bg-red-100 hover:bg-red-200 text-red-700 py-2 rounded-lg text-sm font-semibold">⛔ Отключить 2FA</button>';
h+='</div>';
}else{
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
h+='<p class="text-yellow-700 text-sm"><strong>⚠️ 2FA не включена.</strong> Рекомендуем включить для защиты аккаунта.</p>';
h+='</div>';
h+='<div class="text-center">';
h+='<p class="text-gray-600 text-sm mb-4">Понадобится приложение:<br><strong>Google Authenticator</strong>, <strong>Яндекс.Ключ</strong> или <strong>Authy</strong></p>';
h+='<button onclick="tfa_setup()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">🔐 Включить 2FA</button>';
h+='</div>';
}
modal(h);
}).catch(function(err){alert('Ошибка: '+err.message);});
}

function tfa_setup(){
ap('/two-factor',{method:'POST',body:JSON.stringify({action:'setup'})}).then(function(d){
if(d.error){alert(d.error);return;}
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔐 Настройка 2FA</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="text-center mb-4">';
h+='<p class="text-sm text-gray-600 mb-3">1. Отсканируйте QR-код в приложении:</p>';
h+='<img src="'+d.qr_url+'" alt="QR" style="width:250px;height:250px;margin:0 auto;display:block;border:1px solid #e5e7eb;border-radius:12px;padding:8px">';
h+='<p class="text-xs text-gray-400 mt-2">Или введите вручную: <code class="bg-gray-100 px-2 py-1 rounded font-mono text-xs select-all">'+d.secret+'</code></p>';
h+='</div>';
h+='<div class="mt-4">';
h+='<p class="text-sm text-gray-600 mb-2">2. Введите 6-значный код из приложения:</p>';
h+='<div class="flex gap-3"><input type="text" id="tfa-verify-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" class="input-f text-center text-2xl tracking-widest font-mono flex-1"><button onclick="tfa_enable()" id="tfa-enable-btn" class="btn-p">Подтвердить</button></div>';
h+='<div id="tfa-setup-err" class="hidden text-red-600 text-sm mt-2"></div>';
h+='</div>';
modal(h);
setTimeout(function(){var el=document.getElementById('tfa-verify-code');if(el)el.focus();},200);
});
}

function tfa_enable(){
var code=document.getElementById('tfa-verify-code').value.trim();
if(!code||code.length!==6){showTfaErr('Введите 6-значный код');return;}
var btn=document.getElementById('tfa-enable-btn');
btn.disabled=true;btn.textContent='⏳';
ap('/two-factor',{method:'POST',body:JSON.stringify({action:'enable',code:code})}).then(function(d){
btn.disabled=false;btn.textContent='Подтвердить';
if(d.error){showTfaErr(d.error);return;}
// Показать резервные коды
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">✅ 2FA включена!</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 text-center"><p class="text-green-700 font-bold">Двухфакторная авторизация активна</p></div>';
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
h+='<p class="font-bold text-yellow-700 mb-2">⚠️ Сохраните резервные коды!</p>';
h+='<p class="text-sm text-yellow-600 mb-3">Эти коды нужны для входа, если потеряете телефон. Каждый код одноразовый.</p>';
h+='<div class="grid grid-cols-2 gap-2 font-mono text-sm">';
(d.backup_codes||[]).forEach(function(c){
h+='<div class="bg-white border rounded px-3 py-1.5 text-center select-all">'+c+'</div>';
});
h+='</div>';
h+='</div>';
h+='<button onclick="cm()" class="w-full btn-p">Понятно, я сохранил коды</button>';
modal(h);
}).catch(function(){btn.disabled=false;btn.textContent='Подтвердить';showTfaErr('Ошибка соединения');});
}

function showTfaErr(msg){var el=document.getElementById('tfa-setup-err');if(el){el.textContent=msg;el.classList.remove('hidden');}}

function tfa_disable(){
if(!confirm('Отключить 2FA для текущего администратора?')) return;
ap('/two-factor',{method:'POST',body:JSON.stringify({action:'disable'})}).then(function(d){
if(d.error){alert('❌ '+d.error);return;}
alert('✅ 2FA отключена');
cm();
show2FA();
});
}

function tfa_regenBackup(){
if(!confirm('Сгенерировать новые резервные коды? Старые перестанут работать.')) return;
ap('/two-factor',{method:'POST',body:JSON.stringify({action:'regenerate-backup'})}).then(function(d){
if(d.error){alert('❌ '+d.error);return;}
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔄 Новые резервные коды</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">';
h+='<p class="font-bold text-yellow-700 mb-2">⚠️ Старые коды больше не действуют!</p>';
h+='<div class="grid grid-cols-2 gap-2 font-mono text-sm mt-3">';
(d.backup_codes||[]).forEach(function(c){
h+='<div class="bg-white border rounded px-3 py-1.5 text-center select-all">'+c+'</div>';
});
h+='</div></div>';
h+='<button onclick="cm()" class="w-full btn-p">Понятно</button>';
modal(h);
});
}

/* ============ BULK ACTIONS ============ */
var bulkMode={};

function bulkToggle(entity){
bulkMode[entity]=!bulkMode[entity];
var btn=document.getElementById('bulk-'+entity+'-toggle');
if(btn){btn.textContent=bulkMode[entity]?'✕ Отмена':'☑ Выбрать';btn.className=bulkMode[entity]?'bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg text-xs font-semibold':'bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold';}
document.querySelectorAll('.bulk-'+entity+'-cb').forEach(function(cb){
cb.classList.toggle('hidden',!bulkMode[entity]);cb.checked=false;
});
var bar=document.getElementById('bulk-bar-'+entity);
if(bar)bar.remove();
if(bulkMode[entity]){
var el=document.getElementById('p-'+entity==='offers'?'offers':entity);
if(!el)el=document.getElementById('p-'+(entity==='tags'?'tags':entity));
}
}

function bulkUpdate(entity){
var checked=document.querySelectorAll('.bulk-'+entity+'-cb:checked');
var count=checked.length;
var bar=document.getElementById('bulk-bar');
if(count===0){if(bar)bar.remove();return;}

var ids=[];checked.forEach(function(cb){ids.push(cb.dataset.id);});

if(!bar){
bar=document.createElement('div');bar.id='bulk-bar';
bar.className='fixed bottom-0 left-0 right-0 bg-gray-900 text-white py-3 px-4 z-50 shadow-2xl';
document.body.appendChild(bar);
}

var h='<div class="max-w-7xl mx-auto flex items-center justify-between flex-wrap gap-3">';
h+='<span class="text-sm font-medium">Выбрано: <strong>'+count+'</strong></span>';
h+='<div class="flex flex-wrap gap-2">';

if(entity==='offers'){
h+='<button onclick="bulkDo(\'offers\',\'enable\')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-semibold">✅ Включить</button>';
h+='<button onclick="bulkDo(\'offers\',\'disable\')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded text-xs font-semibold">⛔ Выключить</button>';
h+='<button onclick="bulkAssignTags()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🏷️ Назначить теги</button>';
h+='<button onclick="bulkDo(\'offers\',\'generate-meta\')" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🤖 Генерация Meta</button>';
h+='<button onclick="bulkDo(\'offers\',\'delete\')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🗑️ Удалить</button>';
}
if(entity==='tags'){
h+='<button onclick="bulkDo(\'tags\',\'enable\')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-semibold">✅ Включить</button>';
h+='<button onclick="bulkDo(\'tags\',\'disable\')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded text-xs font-semibold">⛔ Выключить</button>';
h+='<button onclick="bulkDo(\'tags\',\'generate-meta\')" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🤖 Генерация Meta</button>';
h+='<button onclick="bulkDo(\'tags\',\'delete\')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🗑️ Удалить</button>';
}
if(entity==='articles'){
h+='<button onclick="bulkDo(\'articles\',\'publish\')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-semibold">✅ Опубликовать</button>';
h+='<button onclick="bulkDo(\'articles\',\'unpublish\')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1.5 rounded text-xs font-semibold">⛔ Снять</button>';
h+='<button onclick="bulkDo(\'articles\',\'generate-meta\')" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🤖 Генерация Meta</button>';
h+='<button onclick="bulkDo(\'articles\',\'delete\')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-semibold">🗑️ Удалить</button>';
}

h+='<button onclick="bulkSelectAll(\''+entity+'\')" class="text-gray-300 hover:text-white px-2 py-1.5 text-xs">Выбрать все</button>';
h+='<button onclick="bulkSelectNone(\''+entity+'\')" class="text-gray-300 hover:text-white px-2 py-1.5 text-xs">Снять</button>';
h+='</div></div>';
bar.innerHTML=h;
}

function bulkGetIds(entity){
var ids=[];
document.querySelectorAll('.bulk-'+entity+'-cb:checked').forEach(function(cb){ids.push(parseInt(cb.dataset.id));});
return ids;
}

function bulkSelectAll(entity){
document.querySelectorAll('.bulk-'+entity+'-cb').forEach(function(cb){if(!cb.classList.contains('hidden'))cb.checked=true;});
bulkUpdate(entity);
}

function bulkSelectNone(entity){
document.querySelectorAll('.bulk-'+entity+'-cb').forEach(function(cb){cb.checked=false;});
bulkUpdate(entity);
}

function bulkDo(entity,action){
var ids=bulkGetIds(entity);
if(!ids.length){alert('Выберите элементы');return;}

var labels={'enable':'включить','disable':'выключить','delete':'УДАЛИТЬ','publish':'опубликовать','unpublish':'снять с публикации','generate-meta':'сгенерировать Meta'};
var label=labels[action]||action;

if(action==='generate-meta'){
ap('/batch-generate',{method:'POST',body:JSON.stringify({entity:entity,ids:ids,fields:['meta_title','meta_description'],overwrite:false})}).then(function(d){
alert('✅ Готово: '+d.success+' успешно, '+d.skipped+' пропущено'+(d.errors?', '+d.errors+' ошибок':''));
bulkFinish(entity);
}).catch(function(err){alert('Ошибка: '+err.message);});
return;
}

if(!confirm(label.toUpperCase()+' '+ids.length+' элемент(ов)?'))return;

ap('/bulk-actions',{method:'POST',body:JSON.stringify({action:action,entity:entity,ids:ids})}).then(function(d){
if(d.error){alert('❌ '+d.error);return;}
alert('✅ Выполнено: '+d.count+' элементов');
bulkFinish(entity);
}).catch(function(err){alert('Ошибка: '+err.message);});
}

function bulkFinish(entity){
bulkMode[entity]=false;
var bar=document.getElementById('bulk-bar');if(bar)bar.remove();
var btn=document.getElementById('bulk-'+entity+'-toggle');
if(btn){btn.textContent='☑ Выбрать';btn.className='bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold';}
var reload={offers:lO,tags:lT,articles:lA};
if(reload[entity])reload[entity]();
}

function bulkAssignTags(){
var ids=bulkGetIds('offers');
if(!ids.length){alert('Выберите офферы');return;}
ap('/tags').then(function(tags){
if(!tags.length){alert('Нет тегов');return;}
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🏷️ Назначить теги ('+ids.length+' офферов)</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';
h+='<div class="space-y-2 max-h-80 overflow-y-auto mb-4">';
tags.forEach(function(t){
h+='<label class="flex items-center gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">';
h+='<input type="checkbox" class="bulk-tag-cb w-4 h-4" value="'+t.id+'">';
h+='<span>'+(t.icon||'🏷️')+' '+e(t.title)+'</span>';
h+='<span class="text-xs text-gray-400 ml-auto">'+(t.category||'')+'</span>';
h+='</label>';
});
h+='</div>';
h+='<div class="flex justify-end gap-3"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="bulkAssignTagsDo()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">Назначить</button></div>';
modal(h);
});
}

function bulkAssignTagsDo(){
var ids=bulkGetIds('offers');
var tagIds=[];
document.querySelectorAll('.bulk-tag-cb:checked').forEach(function(cb){tagIds.push(parseInt(cb.value));});
if(!tagIds.length){alert('Выберите хотя бы один тег');return;}
ap('/bulk-actions',{method:'POST',body:JSON.stringify({action:'assign-tags',entity:'offers',ids:ids,tagIds:tagIds})}).then(function(d){
if(d.error){alert('❌ '+d.error);return;}
alert('✅ Теги назначены: '+d.count+' связей');
cm();bulkFinish('offers');
}).catch(function(err){alert('Ошибка: '+err.message);});
}


/* ============ MEDIA MANAGER ============ */
function mediaPicker(targetInputId, dir){
var useM2=true;
var inp=document.getElementById(targetInputId);
ap('/media?dir='+dir).then(function(d){
if(d.error){alert(d.error);return;}
var files=d.files||[];
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📁 Выбрать картинку</h3><button onclick="cm2()" class="text-gray-400 text-xl">&times;</button></div>';

// Upload form
h+='<div class="border-2 border-dashed border-gray-300 rounded-xl p-4 mb-4 text-center" id="media-drop-zone">';
h+='<p class="text-sm text-gray-500 mb-2">Перетащите файл сюда или</p>';
h+='<label class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold cursor-pointer">';
h+='📤 Загрузить файл<input type="file" id="media-file-input" accept="image/*" class="hidden" onchange="mediaUpload(\''+dir+'\',\''+targetInputId+'\')">';
h+='</label>';
h+='<p class="text-xs text-gray-400 mt-2">JPG, PNG, SVG, WebP, GIF — до 5MB</p>';
h+='<div id="media-upload-status" class="mt-2"></div>';
h+='</div>';

// File grid
h+='<div class="text-xs text-gray-500 mb-2">📂 /images/'+dir+'/ — '+files.length+' файлов</div>';
h+='<div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-h-80 overflow-y-auto" id="media-grid">';
if(!files.length){
h+='<p class="col-span-full text-center text-gray-400 py-8">Нет файлов</p>';
}else{
files.forEach(function(f){
var isSvg=f.ext==='svg';
var preview=isSvg?'<div class="w-full h-full flex items-center justify-center bg-gray-100 text-2xl">SVG</div>':'<img src="'+f.url+'" class="w-full h-full object-contain" loading="lazy">';
h+='<div class="group relative border rounded-lg overflow-hidden cursor-pointer hover:border-blue-500 hover:shadow-md transition-all" onclick="mediaSelect(\''+f.url+'\',\''+targetInputId+'\')" title="'+e(f.name)+'\\n'+f.sizeHuman+'">';
h+='<div class="aspect-square bg-gray-50 flex items-center justify-center p-1">'+preview+'</div>';
h+='<div class="px-1.5 py-1 text-center"><p class="text-xs text-gray-700 truncate">'+e(f.name)+'</p><p class="text-xs text-gray-400">'+f.sizeHuman+'</p></div>';
h+='<button onclick="event.stopPropagation();mediaDelete(\''+dir+'\',\''+f.name+'\',\''+targetInputId+'\')" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs hidden group-hover:flex items-center justify-center" title="Удалить">&times;</button>';
h+='</div>';
});
}
h+='</div>';
document.getElementById('M2').innerHTML='<div class="modal-bg" style="z-index:60" onclick="if(event.target===this)cm2()"><div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-2xl">'+h+'</div></div>';

// Drag & drop
setTimeout(function(){
var zone=document.getElementById('media-drop-zone');
if(!zone)return;
zone.addEventListener('dragover',function(ev){ev.preventDefault();zone.classList.add('border-blue-400','bg-blue-50');});
zone.addEventListener('dragleave',function(){zone.classList.remove('border-blue-400','bg-blue-50');});
zone.addEventListener('drop',function(ev){
ev.preventDefault();zone.classList.remove('border-blue-400','bg-blue-50');
var files=ev.dataTransfer.files;
if(files.length){
document.getElementById('media-file-input').files=files;
mediaUpload(dir,targetInputId);
}
});
},100);
});
}

function mediaSelect(url, targetInputId){
var inp=document.getElementById(targetInputId);
if(inp)inp.value=url;
var prev=document.getElementById(targetInputId+'-preview');
if(prev)prev.innerHTML='<img src="'+url+'" class="w-16 h-16 object-contain rounded border bg-white">';
cm2();
}

function mediaUpload(dir, targetInputId){
var fileInput=document.getElementById('media-file-input');
if(!fileInput||!fileInput.files.length)return;
var file=fileInput.files[0];
var status=document.getElementById('media-upload-status');
if(status)status.innerHTML='<span class="text-blue-600 text-sm">⏳ Загрузка...</span>';

var fd=new FormData();
fd.append('file',file);
fd.append('dir',dir);

fetch(A+'/media',{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
if(d.error){
if(status)status.innerHTML='<span class="text-red-600 text-sm">❌ '+e(d.error)+'</span>';
return;
}
if(status)status.innerHTML='<span class="text-green-600 text-sm">✅ Загружено: '+e(d.name)+'</span>';
// Auto-select uploaded file
mediaSelect(d.url, targetInputId);
}).catch(function(err){
if(status)status.innerHTML='<span class="text-red-600 text-sm">❌ Ошибка</span>';
});
}

function mediaDelete(dir, name, targetInputId){
if(!confirm('Удалить файл '+name+'?'))return;
fetch(A+'/media',{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({dir:dir,name:name})}).then(r=>r.json()).then(function(d){
if(d.error){alert(d.error);return;}
// Reload picker
mediaPicker(targetInputId, dir);
});
}

/* ============ SETTINGS ============ */

/* ============ FINANCIAL ANALYTICS ============ */
var analyticsPeriod=30;
var analyticsCompare=false;
var analyticsChart=null;

function lAnalytics(){
var params='period='+analyticsPeriod;
if(analyticsCompare)params+='&compare=prev';

ap('/analytics?'+params).then(function(d){
if(d.error){document.getElementById('p-analytics').innerHTML='<div class="text-red-500">Ошибка: '+e(d.error)+'</div>';return;}

var h='<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">';
h+='<h2 class="text-xl font-bold">📈 Финансовая аналитика</h2>';
h+='<div class="flex flex-wrap gap-3 items-center">';
h+='<select id="an-period" class="sel-f w-auto" onchange="anPeriod(this.value)">';
h+='<option value="7"'+(analyticsPeriod===7?' selected':'')+'>7 дней</option>';
h+='<option value="14"'+(analyticsPeriod===14?' selected':'')+'>14 дней</option>';
h+='<option value="30"'+(analyticsPeriod===30?' selected':'')+'>30 дней</option>';
h+='<option value="90"'+(analyticsPeriod===90?' selected':'')+'>90 дней</option>';
h+='<option value="180"'+(analyticsPeriod===180?' selected':'')+'>180 дней</option>';
h+='<option value="365"'+(analyticsPeriod===365?' selected':'')+'>365 дней</option>';
h+='</select>';
h+='<label class="flex items-center gap-2 text-sm"><input type="checkbox" id="an-compare" onchange="anCompare(this.checked)"'+(analyticsCompare?' checked':'')+'> Сравнить с пред. периодом</label>';
h+='</div></div>';

// Карточки с итогами
var t=d.totals||{};
h+='<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">';
h+=anCard('💰','Доход',formatMoney(t.total_revenue||0),'revenue',d.comparison);
h+=anCard('👆','Клики',formatNum(t.total_clicks||0),'clicks',d.comparison);
h+=anCard('✅','Одобрено',formatNum(t.total_approved||0),'approved',d.comparison);
h+=anCard('❌','Отклонено',formatNum(t.total_rejected||0),'rejected',null);
h+=anCard('⏳','В ожидании',formatNum(t.total_pending||0),'pending',null);
h+=anCard('📊','EPC',formatMoney(t.avg_epc||0),'epc',null);
h+='</div>';

// График
h+='<div class="bg-white rounded-xl border p-6 mb-6">';
h+='<h3 class="font-bold mb-4">📊 Динамика дохода</h3>';
h+='<div style="height:300px"><canvas id="analytics-chart"></canvas></div>';
h+='</div>';

// Таблицы в 2 колонки
h+='<div class="grid lg:grid-cols-2 gap-6 mb-6">';

// По офферам
h+='<div class="bg-white rounded-xl border p-6">';
h+='<h3 class="font-bold mb-4">💵 Доход по офферам</h3>';
h+='<div class="overflow-x-auto max-h-80 overflow-y-auto">';
h+='<table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Оффер</th>';
h+='<th class="px-3 py-2 text-right">Клики</th>';
h+='<th class="px-3 py-2 text-right">✅</th>';
h+='<th class="px-3 py-2 text-right">❌</th>';
h+='<th class="px-3 py-2 text-right">⏳</th>';
h+='<th class="px-3 py-2 text-right">Доход</th>';
h+='<th class="px-3 py-2 text-right">EPC</th>';
h+='<th class="px-3 py-2 text-right">AR%</th>';
h+='</tr></thead><tbody>';
if(!d.by_offer||!d.by_offer.length){
h+='<tr><td colspan="8" class="px-3 py-4 text-center text-gray-400">Нет данных</td></tr>';
}else{
d.by_offer.forEach(function(o){
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2"><div class="flex items-center gap-2">';
if(o.logo_url)h+='<img src="'+e(o.logo_url)+'" class="w-6 h-6 rounded object-contain bg-gray-100">';
h+='<span class="truncate max-w-32" title="'+e(o.title)+'">'+e(o.title)+'</span></div></td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+formatNum(o.clicks)+'</td>';
h+='<td class="px-3 py-2 text-right text-green-600 font-medium">'+formatNum(o.approved)+'</td>';
h+='<td class="px-3 py-2 text-right '+(Number(o.rejected)>0?'text-red-600 font-medium':'text-gray-400')+'">'+formatNum(o.rejected)+'</td>';
h+='<td class="px-3 py-2 text-right text-yellow-600">'+formatNum(o.pending)+'</td>';
h+='<td class="px-3 py-2 text-right font-medium text-green-600">'+formatMoney(o.revenue)+'</td>';
h+='<td class="px-3 py-2 text-right">'+formatMoney(o.epc)+'</td>';
h+='<td class="px-3 py-2 text-right">'+(o.approval_rate||0)+'%</td>';
h+='</tr>';
});
}
h+='</tbody></table></div></div>';

// По категориям
h+='<div class="bg-white rounded-xl border p-6">';
h+='<h3 class="font-bold mb-4">📂 Доход по категориям</h3>';
h+='<div class="space-y-3">';
if(!d.by_category||!d.by_category.length){
h+='<p class="text-gray-400">Нет данных</p>';
}else{
var maxCatRev=Math.max(...d.by_category.map(c=>parseFloat(c.revenue)||0),1);
d.by_category.forEach(function(c){
var pct=maxCatRev>0?Math.round((parseFloat(c.revenue)||0)/maxCatRev*100):0;
h+='<div>';
h+='<div class="flex justify-between text-sm mb-1"><span class="font-medium">'+e(c.category_label)+'</span><span class="text-green-600 font-medium">'+formatMoney(c.revenue)+'</span></div>';
h+='<div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-green-500 h-2 rounded-full" style="width:'+pct+'%"></div></div>';
h+='<div class="flex justify-between text-xs text-gray-500 mt-1"><span>'+formatNum(c.clicks)+' кликов</span><span>EPC: '+formatMoney(c.epc)+' • AR: '+(c.approval_rate||0)+'%</span></div>';
h+='</div>';
});
}
h+='</div></div>';

h+='</div>'; // end grid

// Партнёрки и источники
h+='<div class="grid lg:grid-cols-2 gap-6">';

// По партнёркам
h+='<div class="bg-white rounded-xl border p-6">';
h+='<h3 class="font-bold mb-4">🤝 Доход по партнёркам</h3>';
h+='<div class="overflow-x-auto max-h-64 overflow-y-auto">';
h+='<table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Партнёрка</th>';
h+='<th class="px-3 py-2 text-right">Доход</th>';
h+='<th class="px-3 py-2 text-right">Одобр.</th>';
h+='<th class="px-3 py-2 text-right">AR%</th>';
h+='</tr></thead><tbody>';
if(!d.by_partner||!d.by_partner.length){
h+='<tr><td colspan="8" class="px-3 py-4 text-center text-gray-400">Нет данных</td></tr>';
}else{
d.by_partner.forEach(function(p){
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 font-medium">'+e(p.partner_name)+'</td>';
h+='<td class="px-3 py-2 text-right text-green-600 font-medium">'+formatMoney(p.revenue)+'</td>';
h+='<td class="px-3 py-2 text-right">'+formatNum(p.approved)+'</td>';
h+='<td class="px-3 py-2 text-right '+(p.approval_rate>=50?'text-green-600':'text-orange-500')+'">'+(p.approval_rate||0)+'%</td>';
h+='</tr>';
});
}
h+='</tbody></table></div></div>';

// По источникам
h+='<div class="bg-white rounded-xl border p-6">';
h+='<h3 class="font-bold mb-4">🔗 EPC по источникам трафика</h3>';
h+='<div class="overflow-x-auto max-h-64 overflow-y-auto">';
h+='<table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Источник</th>';
h+='<th class="px-3 py-2 text-right">Клики</th>';
h+='<th class="px-3 py-2 text-right">Доход</th>';
h+='<th class="px-3 py-2 text-right">EPC</th>';
h+='</tr></thead><tbody>';
if(!d.by_source||!d.by_source.length){
h+='<tr><td colspan="8" class="px-3 py-4 text-center text-gray-400">Нет данных</td></tr>';
}else{
d.by_source.forEach(function(s){
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2"><span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">'+e(s.source)+'</span></td>';
h+='<td class="px-3 py-2 text-right">'+formatNum(s.clicks)+'</td>';
h+='<td class="px-3 py-2 text-right text-green-600">'+formatMoney(s.revenue)+'</td>';
h+='<td class="px-3 py-2 text-right font-medium">'+formatMoney(s.epc)+'</td>';
h+='</tr>';
});
}
h+='</tbody></table></div></div>';

h+='</div>'; // end grid

// Блок отклонённых конверсий
var rejOffers=(d.by_offer||[]).filter(function(o){ return Number(o.rejected)>0; });
if(rejOffers.length){
h+='<div class="bg-white rounded-xl border p-6 mt-6">';
h+='<h3 class="font-bold mb-4">❌ Отклонённые конверсии по офферам</h3>';
h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-red-50"><tr>';
h+='<th class="px-3 py-2 text-left">Оффер</th>';
h+='<th class="px-3 py-2 text-right">Отклонено</th>';
h+='<th class="px-3 py-2 text-right">Одобрено</th>';
h+='<th class="px-3 py-2 text-right">Всего</th>';
h+='<th class="px-3 py-2 text-right">AR%</th>';
h+='<th class="px-3 py-2 text-right">Потеряно</th>';
h+='</tr></thead><tbody>';
rejOffers.sort(function(a,b){ return Number(b.rejected)-Number(a.rejected); });
rejOffers.forEach(function(o){
var total=Number(o.approved)+Number(o.rejected);
var avgPayout=Number(o.approved)>0?(Number(o.revenue)/Number(o.approved)):0;
var lost=Number(o.rejected)*avgPayout;
h+='<tr class="border-t hover:bg-red-50/50">';
h+='<td class="px-3 py-2"><div class="flex items-center gap-2">';
if(o.logo_url)h+='<img src="'+e(o.logo_url)+'" class="w-6 h-6 rounded object-contain bg-gray-100">';
h+='<span class="font-medium">'+e(o.title)+'</span></div></td>';
h+='<td class="px-3 py-2 text-right text-red-600 font-bold">'+formatNum(o.rejected)+'</td>';
h+='<td class="px-3 py-2 text-right text-green-600">'+formatNum(o.approved)+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+total+'</td>';
h+='<td class="px-3 py-2 text-right"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(Number(o.approval_rate)>=50?'bg-green-100 text-green-700':'bg-red-100 text-red-700')+'">'+(o.approval_rate||0)+'%</span></td>';
h+='<td class="px-3 py-2 text-right text-red-500">~'+formatMoney(lost)+'</td>';
h+='</tr>';
});
h+='</tbody></table></div>';
h+='<p class="text-xs text-gray-400 mt-3">💡 «Потеряно» — примерная сумма, рассчитана по среднему вознаграждению за одобренные конверсии этого оффера.</p>';
h+='</div>';
}

document.getElementById('p-analytics').innerHTML=h;

// Рисуем график
setTimeout(function(){anDrawChart(d.timeline||[]);},100);
}).catch(function(err){
document.getElementById('p-analytics').innerHTML='<div class="text-red-500">Ошибка загрузки: '+err.message+'</div>';
});
}

function anCard(icon,label,value,key,comparison){
var h='<div class="bg-white rounded-xl border p-4">';
h+='<div class="flex items-center gap-2 text-gray-500 text-sm mb-1"><span>'+icon+'</span><span>'+label+'</span></div>';
h+='<div class="text-xl font-bold">'+value+'</div>';
if(comparison&&comparison.changes&&comparison.changes[key]!==undefined){
var ch=comparison.changes[key];
var color=ch>0?'text-green-600':ch<0?'text-red-500':'text-gray-400';
var arrow=ch>0?'↑':ch<0?'↓':'→';
h+='<div class="text-xs '+color+'">'+arrow+' '+Math.abs(ch)+'% к пред.</div>';
}
h+='</div>';
return h;
}

function formatMoney(v){
v=parseFloat(v)||0;
if(v>=1000000)return (v/1000000).toFixed(1)+'M ₽';
if(v>=1000)return (v/1000).toFixed(1)+'K ₽';
return v.toFixed(2)+' ₽';
}

function formatNum(v){
v=parseInt(v)||0;
if(v>=1000000)return (v/1000000).toFixed(1)+'M';
if(v>=1000)return (v/1000).toFixed(1)+'K';
return v.toString();
}

function anPeriod(v){
analyticsPeriod=parseInt(v)||30;
lAnalytics();
}

function anCompare(v){
analyticsCompare=!!v;
lAnalytics();
}

function anDrawChart(data){
var canvas=document.getElementById('analytics-chart');
if(!canvas)return;
var ctx=canvas.getContext('2d');
if(analyticsChart){analyticsChart.destroy();}

var labels=data.map(function(d){return d.date;});
var revenues=data.map(function(d){return parseFloat(d.revenue)||0;});
var clicks=data.map(function(d){return parseInt(d.clicks)||0;});
var approved=data.map(function(d){return parseInt(d.approved)||0;});

analyticsChart=new Chart(ctx,{
type:'line',
data:{
labels:labels,
datasets:[
{label:'Доход ₽',data:revenues,borderColor:'#059669',backgroundColor:'rgba(5,150,105,0.1)',fill:true,tension:0.3,yAxisID:'y'},
{label:'Клики',data:clicks,borderColor:'#3b82f6',backgroundColor:'transparent',borderDash:[5,5],tension:0.3,yAxisID:'y1'},
{label:'Одобрено',data:approved,borderColor:'#8b5cf6',backgroundColor:'transparent',borderDash:[2,2],tension:0.3,yAxisID:'y1'}
]
},
options:{
responsive:true,
maintainAspectRatio:false,
interaction:{mode:'index',intersect:false},
plugins:{legend:{position:'bottom'}},
scales:{
y:{type:'linear',position:'left',title:{display:true,text:'Доход ₽'},grid:{color:'#f3f4f6'}},
y1:{type:'linear',position:'right',title:{display:true,text:'Кол-во'},grid:{drawOnChartArea:false}}
}
}
});
}

/* ============ HISTORY (AUDIT LOG) ============ */
var historyFilters={entity:'',action:'',dateFrom:'',dateTo:''};
var historyOffset=0;
var historyLimit=50;

function lHistory(){
var params=new URLSearchParams();
if(historyFilters.entity)params.set('entity',historyFilters.entity);
if(historyFilters.action)params.set('action',historyFilters.action);
if(historyFilters.dateFrom)params.set('date_from',historyFilters.dateFrom);
if(historyFilters.dateTo)params.set('date_to',historyFilters.dateTo);
params.set('limit',historyLimit);
params.set('offset',historyOffset);

ap('/audit-log?'+params.toString()).then(function(d){
var h='<h2 class="text-xl font-bold mb-6">📜 История изменений</h2>';

// Фильтры
h+='<div class="bg-white rounded-xl border p-4 mb-6">';
h+='<div class="flex flex-wrap gap-4 items-end">';
h+='<div><label class="block text-xs font-medium mb-1">Сущность</label><select id="hist-entity" class="sel-f" onchange="histFilter()">';
h+='<option value="">Все</option>';
h+='<option value="offer"'+(historyFilters.entity==='offer'?' selected':'')+'>📋 Офферы</option>';
h+='<option value="article"'+(historyFilters.entity==='article'?' selected':'')+'>📰 Статьи</option>';
h+='<option value="tag"'+(historyFilters.entity==='tag'?' selected':'')+'>🏷️ Теги</option>';
h+='<option value="category"'+(historyFilters.entity==='category'?' selected':'')+'>📂 Категории</option>';
h+='<option value="newsletter"'+(historyFilters.entity==='newsletter'?' selected':'')+'>📬 Рассылки</option>';
h+='<option value="postback_profile"'+(historyFilters.entity==='postback_profile'?' selected':'')+'>🔗 Postback</option>';
h+='<option value="smart_rating"'+(historyFilters.entity==='smart_rating'?' selected':'')+'>🧠 Умный рейтинг</option>';
h+='</select></div>';

h+='<div><label class="block text-xs font-medium mb-1">Действие</label><select id="hist-action" class="sel-f" onchange="histFilter()">';
h+='<option value="">Все</option>';
h+='<option value="create"'+(historyFilters.action==='create'?' selected':'')+'>➕ Создание</option>';
h+='<option value="update"'+(historyFilters.action==='update'?' selected':'')+'>✏️ Изменение</option>';
h+='<option value="delete"'+(historyFilters.action==='delete'?' selected':'')+'>🗑️ Удаление</option>';
h+='<option value="enable"'+(historyFilters.action==='enable'?' selected':'')+'>✅ Включение</option>';
h+='<option value="disable"'+(historyFilters.action==='disable'?' selected':'')+'>⛔ Отключение</option>';
h+='<option value="send"'+(historyFilters.action==='send'?' selected':'')+'>📤 Отправка</option>';
h+='<option value="apply"'+(historyFilters.action==='apply'?' selected':'')+'>⚡ Применение</option>';
h+='</select></div>';

h+='<div><label class="block text-xs font-medium mb-1">Дата от</label><input type="date" id="hist-from" class="input-f" value="'+(historyFilters.dateFrom||'')+'" onchange="histFilter()"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Дата до</label><input type="date" id="hist-to" class="input-f" value="'+(historyFilters.dateTo||'')+'" onchange="histFilter()"></div>';

h+='<button onclick="histReset()" class="text-sm text-gray-500 hover:underline">Сбросить</button>';
h+='</div>';
h+='</div>';

// Таблица
h+='<div class="bg-white rounded-xl border overflow-hidden">';
h+='<table class="w-full text-sm">';
h+='<thead class="bg-gray-50 text-left"><tr>';
h+='<th class="px-4 py-3 font-medium">Дата</th>';
h+='<th class="px-4 py-3 font-medium">Админ</th>';
h+='<th class="px-4 py-3 font-medium">Действие</th>';
h+='<th class="px-4 py-3 font-medium">Сущность</th>';
h+='<th class="px-4 py-3 font-medium">Название</th>';
h+='<th class="px-4 py-3 font-medium">Изменения</th>';
h+='</tr></thead>';
h+='<tbody>';

if(!d.logs||!d.logs.length){
h+='<tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Нет записей</td></tr>';
}else{
d.logs.forEach(function(log){
var date=new Date(log.created_at);
var dateStr=date.toLocaleDateString('ru-RU')+' '+date.toLocaleTimeString('ru-RU',{hour:'2-digit',minute:'2-digit'});
var changesStr='—';
if(log.changes&&typeof log.changes==='object'){
var parts=[];
for(var k in log.changes){
var c=log.changes[k];
if(c.old!==undefined||c.new!==undefined){
parts.push(k+': '+JSON.stringify(c.old)+' → '+JSON.stringify(c.new));
}
}
if(parts.length)changesStr='<span class="text-xs text-gray-500">'+e(parts.slice(0,3).join('; '))+(parts.length>3?' ...':'')+'</span>';
}

h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-4 py-3 text-gray-500 whitespace-nowrap">'+dateStr+'</td>';
h+='<td class="px-4 py-3">'+e(log.admin_name||'—')+'</td>';
h+='<td class="px-4 py-3"><span class="inline-flex items-center gap-1">'+(log.action_icon||'📝')+' '+e(log.action_label||log.action)+'</span></td>';
h+='<td class="px-4 py-3">'+e(log.entity_label||log.entity)+'</td>';
h+='<td class="px-4 py-3 max-w-xs truncate" title="'+e(log.entity_name||'')+'">'+e(log.entity_name||'—')+'</td>';
h+='<td class="px-4 py-3 max-w-xs">'+changesStr+'</td>';
h+='</tr>';
});
}

h+='</tbody></table>';
h+='</div>';

// Пагинация
if(d.total>historyLimit){
h+='<div class="flex justify-between items-center mt-4">';
h+='<span class="text-sm text-gray-500">Показано '+(historyOffset+1)+'–'+Math.min(historyOffset+d.logs.length,d.total)+' из '+d.total+'</span>';
h+='<div class="flex gap-2">';
if(historyOffset>0){
h+='<button onclick="histPrev()" class="px-3 py-1 border rounded hover:bg-gray-50">← Назад</button>';
}
if(historyOffset+historyLimit<d.total){
h+='<button onclick="histNext()" class="px-3 py-1 border rounded hover:bg-gray-50">Вперёд →</button>';
}
h+='</div></div>';
}

document.getElementById('p-history').innerHTML=h;
}).catch(function(err){
document.getElementById('p-history').innerHTML='<div class="text-red-500">Ошибка загрузки: '+err.message+'</div>';
});
}

function histFilter(){
historyFilters.entity=document.getElementById('hist-entity').value;
historyFilters.action=document.getElementById('hist-action').value;
historyFilters.dateFrom=document.getElementById('hist-from').value;
historyFilters.dateTo=document.getElementById('hist-to').value;
historyOffset=0;
lHistory();
}

function histReset(){
historyFilters={entity:'',action:'',dateFrom:'',dateTo:''};
historyOffset=0;
lHistory();
}

function histPrev(){
historyOffset=Math.max(0,historyOffset-historyLimit);
lHistory();
}

function histNext(){
historyOffset+=historyLimit;
lHistory();
}

/* ============ BATCH GENERATION ============ */
var batchData={offers:[],articles:[],categories:[],tags:[]};
var batchSelected={offers:[],articles:[],categories:[],tags:[]};

function lBatch(){
Promise.all([
ap('/offers'),
ap('/articles'),
ap('/categories'),
ap('/tags')
]).then(([offers,articles,categories,tags])=>{
batchData={offers:offers||[],articles:articles||[],categories:categories||[],tags:tags||[]};
batchSelected={offers:[],articles:[],categories:[],tags:[]};

var h='<h2 class="text-xl font-bold mb-6">🤖 Пакетная автогенерация текстов и SEO</h2>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">';
h+='<p class="text-blue-700 text-sm"><strong>ℹ️ Как работает:</strong> Выберите сущности и поля для генерации. Система использует YandexGPT для создания уникальных текстов. Генерация происходит последовательно с паузами, чтобы не превысить лимиты API.</p>';
h+='</div>';

// Выбор типа сущности
h+='<div class="bg-white rounded-xl border p-6 mb-6">';
h+='<h3 class="font-bold mb-4">1. Выберите тип сущности</h3>';
h+='<div class="flex flex-wrap gap-3">';
h+='<button onclick="batchShowEntity(\'offers\')" id="batch-btn-offers" class="px-4 py-2 rounded-lg border-2 border-blue-500 bg-blue-50 text-blue-700 font-medium">📋 Предложения ('+offers.length+')</button>';
h+='<button onclick="batchShowEntity(\'articles\')" id="batch-btn-articles" class="px-4 py-2 rounded-lg border-2 border-gray-200 hover:border-blue-300">📰 Статьи ('+articles.length+')</button>';
h+='<button onclick="batchShowEntity(\'categories\')" id="batch-btn-categories" class="px-4 py-2 rounded-lg border-2 border-gray-200 hover:border-blue-300">📂 Категории ('+categories.length+')</button>';
h+='<button onclick="batchShowEntity(\'tags\')" id="batch-btn-tags" class="px-4 py-2 rounded-lg border-2 border-gray-200 hover:border-blue-300">🏷️ Теги ('+tags.length+')</button>';
h+='</div>';
h+='</div>';

// Выбор полей
h+='<div class="bg-white rounded-xl border p-6 mb-6">';
h+='<h3 class="font-bold mb-4">2. Выберите поля, которые нужно заполнить</h3>';
h+='<div id="batch-fields" class="flex flex-wrap gap-3">';
h+=batchFieldsHtml('offers');
h+='</div>';
h+='<div class="mt-4 space-y-2"><label class="flex items-center gap-2 text-sm"><input type="checkbox" id="batch-overwrite" class="w-4 h-4"> Перезаписать уже заполненные поля</label><p class="text-xs text-gray-500">SEO-мета = <strong>meta title</strong> и <strong>meta description</strong>. Если галочку не ставить, уже заполненные поля останутся без изменений.</p></div>';
h+='</div>';

// Список сущностей
h+='<div class="bg-white rounded-xl border p-6 mb-6">';
h+='<div class="flex justify-between items-center mb-4">';
h+='<h3 class="font-bold">3. Выберите элементы <span id="batch-count" class="text-gray-400 font-normal">(0 выбрано)</span></h3>';
h+='<div class="flex gap-2">';
h+='<button onclick="batchSelectAll()" class="text-sm text-blue-600 hover:underline">Выбрать все</button>';
h+='<button onclick="batchSelectNone()" class="text-sm text-gray-500 hover:underline">Снять выбор</button>';
h+='<button onclick="batchSelectEmpty()" id="batch-empty-btn" class="text-sm text-orange-600 hover:underline">Только с пустыми SEO-мета</button>';
h+='</div>';
h+='</div>';
h+='<div id="batch-list" class="max-h-96 overflow-y-auto space-y-2">';
h+=batchListHtml('offers');
h+='</div>';
h+='</div>';

// Кнопка запуска
h+='<div class="bg-white rounded-xl border p-6">';
h+='<div class="flex items-center justify-between">';
h+='<div>';
h+='<p class="text-sm text-gray-500">Выбрано элементов: <strong id="batch-selected-count">0</strong></p>';
h+='<p class="text-xs text-gray-400 mt-1">Примерное время: <span id="batch-time">0 сек</span></p>';
h+='</div>';
h+='<button onclick="batchRun()" id="batch-run-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold disabled:opacity-50" disabled>🚀 Запустить генерацию</button>';
h+='</div>';
h+='<div id="batch-progress" class="hidden mt-4">';
h+='<div class="w-full bg-gray-200 rounded-full h-3"><div id="batch-progress-bar" class="bg-purple-600 h-3 rounded-full transition-all" style="width:0%"></div></div>';
h+='<p id="batch-progress-text" class="text-sm text-gray-600 mt-2">Подготовка...</p>';
h+='</div>';
h+='<div id="batch-results" class="hidden mt-4"></div>';
h+='</div>';

document.getElementById('p-batch').innerHTML=h;
batchShowEntity('offers');
});}

function batchFieldsHtml(entity){
var fields={
offers:[{id:'description',label:'📝 Описание'},{id:'seo_keywords',label:'🔑 SEO ключевые'}],
articles:[{id:'meta_title',label:'🏷️ Meta Title'},{id:'meta_description',label:'📄 Meta Description'}],
categories:[{id:'meta_title',label:'🏷️ Meta Title'},{id:'meta_description',label:'📄 Meta Description'},{id:'h1',label:'📌 H1'},{id:'description',label:'📝 Описание'},{id:'seo_text',label:'📰 SEO текст'}],
tags:[{id:'meta_title',label:'🏷️ Meta Title'},{id:'meta_description',label:'📄 Meta Description'},{id:'description',label:'📝 Описание'}]
};
var h='';
(fields[entity]||[]).forEach(function(f,i){
h+='<label class="flex items-center gap-2 px-3 py-2 rounded-lg border '+(i<2?'bg-blue-50 border-blue-200':'bg-white border-gray-200')+'">';
h+='<input type="checkbox" class="batch-field w-4 h-4" value="'+f.id+'"'+(i<2?' checked':'')+'> '+f.label;
h+='</label>';
});
return h;
}

function batchItemState(entity,item){
if(entity==='offers'){
  var hasDesc=!!(item.description&&String(item.description).trim());
  var hasSeoKeys=!!(item.seo_keywords&&String(item.seo_keywords).trim());
  return {
    isFilled: hasDesc&&hasSeoKeys,
    label: hasDesc&&hasSeoKeys ? 'Описание и SEO ✓' : (!hasDesc&&!hasSeoKeys ? 'Описание и SEO пустые' : (hasDesc ? 'Нет SEO-ключей' : 'Нет описания')),
    okClass: 'bg-green-100 text-green-600',
    emptyClass: 'bg-orange-100 text-orange-600'
  };
}
if(entity==='articles' || entity==='categories' || entity==='tags'){
  var hasMeta=!!(item.meta_title||item.meta_description);
  return {
    isFilled: hasMeta,
    label: hasMeta ? 'SEO-мета ✓' : 'SEO-мета пустые',
    okClass: 'bg-green-100 text-green-600',
    emptyClass: 'bg-orange-100 text-orange-600'
  };
}
return {isFilled:false,label:'Нет данных',okClass:'bg-green-100 text-green-600',emptyClass:'bg-orange-100 text-orange-600'};
}

function batchEmptyButtonLabel(entity){
if(entity==='offers') return 'Только без описания / SEO';
return 'Только с пустыми SEO-мета';
}

function batchListHtml(entity){
var list=batchData[entity]||[];
var h='';
if(!list.length){
h='<p class="text-gray-400 text-sm">Нет элементов</p>';
return h;
}
list.forEach(function(item){
var name=item.title||item.name||'ID '+item.id;
var state=batchItemState(entity,item);
var badge='<span class="text-xs px-2 py-0.5 rounded '+(state.isFilled?state.okClass:state.emptyClass)+'">'+state.label+'</span>';
h+='<label class="flex items-center gap-3 p-3 rounded-lg border hover:bg-gray-50 cursor-pointer">';
h+='<input type="checkbox" class="batch-item w-4 h-4" data-id="'+item.id+'" data-entity="'+entity+'">';
h+='<span class="flex-1">'+e(name)+'</span>';
h+=badge;
h+='</label>';
});
return h;
}

function batchShowEntity(entity){
// Update buttons
['offers','articles','categories','tags'].forEach(function(ent){
var btn=document.getElementById('batch-btn-'+ent);
if(btn){
btn.className=ent===entity?'px-4 py-2 rounded-lg border-2 border-blue-500 bg-blue-50 text-blue-700 font-medium':'px-4 py-2 rounded-lg border-2 border-gray-200 hover:border-blue-300';
}
});
// Update fields
document.getElementById('batch-fields').innerHTML=batchFieldsHtml(entity);
// Update list
batchSelected[entity]=[];
document.getElementById('batch-list').innerHTML=batchListHtml(entity);
var emptyBtn=document.getElementById('batch-empty-btn');if(emptyBtn)emptyBtn.textContent=batchEmptyButtonLabel(entity);
batchUpdateCount(entity);
// Store current entity
document.getElementById('batch-run-btn').dataset.entity=entity;
}

function batchSelectAll(){
var entity=document.getElementById('batch-run-btn').dataset.entity;
batchSelected[entity]=batchData[entity].map(function(i){return i.id;});
document.querySelectorAll('.batch-item').forEach(function(cb){cb.checked=true;});
batchUpdateCount(entity);
}

function batchSelectNone(){
var entity=document.getElementById('batch-run-btn').dataset.entity;
batchSelected[entity]=[];
document.querySelectorAll('.batch-item').forEach(function(cb){cb.checked=false;});
batchUpdateCount(entity);
}

function batchSelectEmpty(){
var entity=document.getElementById('batch-run-btn').dataset.entity;
batchSelected[entity]=[];
document.querySelectorAll('.batch-item').forEach(function(cb){
var id=parseInt(cb.dataset.id);
var item=batchData[entity].find(function(i){return i.id===id;});
var itemState=item?batchItemState(entity,item):{isFilled:false};
cb.checked=!itemState.isFilled;
if(!itemState.isFilled)batchSelected[entity].push(id);
});
batchUpdateCount(entity);
}

function batchUpdateCount(entity){
var count=0;
document.querySelectorAll('.batch-item:checked').forEach(function(){count++;});
batchSelected[entity]=[];
document.querySelectorAll('.batch-item:checked').forEach(function(cb){
batchSelected[entity].push(parseInt(cb.dataset.id));
});
document.getElementById('batch-count').textContent='('+count+' выбрано)';
document.getElementById('batch-selected-count').textContent=count;
document.getElementById('batch-time').textContent=count<=0?'0 сек':(count*2)+'-'+(count*4)+' сек';
document.getElementById('batch-run-btn').disabled=count<=0;
}

// Event delegation for checkboxes
document.addEventListener('change',function(e){
if(e.target.classList.contains('batch-item')){
var entity=document.getElementById('batch-run-btn').dataset.entity;
batchUpdateCount(entity);
}
});

function batchRun(){
var entity=document.getElementById('batch-run-btn').dataset.entity;
var ids=batchSelected[entity]||[];
if(!ids.length){alert('Выберите хотя бы один элемент');return;}

var fields=[];
document.querySelectorAll('.batch-field:checked').forEach(function(cb){fields.push(cb.value);});
if(!fields.length){alert('Выберите хотя бы одно поле для генерации');return;}

var overwrite=document.getElementById('batch-overwrite').checked;

// UI
var btn=document.getElementById('batch-run-btn');
btn.disabled=true;
btn.textContent='⏳ Генерация...';
document.getElementById('batch-progress').classList.remove('hidden');
document.getElementById('batch-results').classList.add('hidden');

ap('/batch-generate',{
method:'POST',
body:JSON.stringify({entity:entity,ids:ids,fields:fields,overwrite:overwrite})
}).then(function(d){
btn.disabled=false;
btn.textContent='🚀 Запустить генерацию';
document.getElementById('batch-progress').classList.add('hidden');

var res=document.getElementById('batch-results');
res.classList.remove('hidden');

var h='<div class="p-4 rounded-lg '+(d.errors?'bg-yellow-50 border border-yellow-200':'bg-green-50 border border-green-200')+'">';
h+='<p class="font-semibold '+(d.errors?'text-yellow-700':'text-green-700')+'">✅ Завершено: '+d.success+' успешно, '+d.skipped+' пропущено'+(d.errors?', '+d.errors+' ошибок':'')+'</p>';
if(d.details&&d.details.length){
h+='<div class="mt-3 max-h-48 overflow-y-auto text-sm">';
d.details.forEach(function(det){
var icon=det.status==='ok'?'✅':det.status==='skipped'?'⏭️':'❌';
h+='<div class="flex items-center gap-2 py-1">';
h+='<span>'+icon+'</span>';
h+='<span class="flex-1">'+e(det.name)+'</span>';
if(det.fields)h+='<span class="text-xs text-gray-400">'+det.fields.join(', ')+'</span>';
if(det.reason)h+='<span class="text-xs text-gray-400">'+e(det.reason)+'</span>';
h+='</div>';
});
h+='</div>';
}
h+='</div>';
res.innerHTML=h;

// Обновляем данные
lBatch();
}).catch(function(err){
btn.disabled=false;
btn.textContent='🚀 Запустить генерацию';
document.getElementById('batch-progress').classList.add('hidden');
alert('Ошибка: '+err.message);
});
}
var siteSettings={};
function lSet(){ap('/settings').then(d=>{
siteSettings=d.settings||{};
var h='<h2 class="text-xl font-bold mb-6">⚙️ Настройки сайта</h2>';
h+='<form onsubmit="return setSave(event)" class="space-y-6">';

// Основные
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">🌐 Основные настройки</h3>';
h+='<div class="grid md:grid-cols-2 gap-4">';
h+='<div><label class="block text-sm font-medium mb-1">Название сайта</label><input type="text" id="set-name" class="input-f" value="'+e(siteSettings.site_name||'Космозайм')+'" placeholder="Название сайта"></div>';
h+='<div><label class="block text-sm font-medium mb-1">URL сайта</label><input type="url" id="set-url" class="input-f" value="'+e(siteSettings.site_url||'')+'" placeholder="https://example.com"></div>';
h+='</div>';

// Логотип
h+='<div class="mt-4"><label class="block text-sm font-medium mb-2">Логотип сайта</label>';
h+='<div class="flex items-center gap-4">';
if(siteSettings.site_logo){
h+='<div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border"><img src="'+siteSettings.site_logo+'" class="max-w-full max-h-full object-contain"></div>';
}else{
h+='<div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center border text-2xl">🚀</div>';
}
h+='<div><input type="file" id="set-logo-file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="text-sm" onchange="setUploadLogo(this)"><p class="text-xs text-gray-500 mt-1">PNG, JPG, SVG или WebP. Рекомендуемый размер: <strong>200×60 px</strong> (или пропорционально). Макс. 2 МБ.</p></div>';
h+='</div></div>';

// Favicon
h+='<div class="mt-4"><label class="block text-sm font-medium mb-2">Favicon сайта</label>';
h+='<div class="flex items-center gap-4">';
if(siteSettings.site_favicon){
h+='<div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden border"><img src="'+siteSettings.site_favicon+'" class="max-w-full max-h-full object-contain"></div>';
}else{
h+='<div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center border text-lg">🌐</div>';
}
h+='<div><input type="file" id="set-favicon-file" accept="image/png,image/svg+xml,image/x-icon" class="text-sm" onchange="setUploadFavicon(this)"><p class="text-xs text-gray-500 mt-1">PNG, SVG или ICO. Рекомендуемый размер: <strong>32×32</strong> или <strong>64×64 px</strong>. Макс. 1 МБ.</p></div>';
h+='</div></div>';
h+='</div>';

// AI провайдеры — настройки перенесены
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">🤖 AI провайдеры</h3>';
h+='<p class="text-gray-600 mb-4">Все настройки AI (ключи, модели, приоритеты) перенесены в отдельный раздел.</p>';
h+='<button type="button" onclick="sw(\'aiproviders\')" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold">🤖 Перейти к настройкам AI провайдеров →</button>';
h+='<div class="mt-4"><label class="block text-sm font-medium mb-1">Шаблон промпта для картинок статей</label><input type="text" id="set-article-image-prompt" class="input-f" value="'+e(siteSettings.article_image_prompt_template||'нарисуй 16:9 {title}')+'" placeholder="нарисуй 16:9 {title}"><p class="text-xs text-gray-500 mt-1">Используйте <code>{title}</code> для подстановки заголовка</p></div>';
h+='</div></div>';
h+='</div>';
h+='<div class="bg-white rounded-xl border p-6 mt-6"><h3 class="font-bold mb-4">🔗 Leads.su (партнёрская сеть)</h3>';
h+='<div><label class="block text-sm font-medium mb-1">API токен leads.su</label><input type="text" id="set-leads-su-token" class="input-f font-mono text-sm" value="'+e(siteSettings.leads_su_api_token_masked||siteSettings.leads_su_api_token||'')+'" placeholder="Вставьте токен из webmaster.leads.su"><p class="text-xs text-gray-500 mt-1">Получить: <a href="https://webmaster.leads.su/account/default" target="_blank" class="text-blue-600 hover:underline">webmaster.leads.su → Аккаунт</a></p></div>';
h+='</div>';
h+='<p class="text-xs text-gray-500 mt-2">Получить ключи: <a href="https://console.cloud.yandex.ru/" target="_blank" class="text-blue-600 hover:underline">console.cloud.yandex.ru</a> → Сервисные аккаунты → API-ключи</p>';
h+='</div>';

// Analytics
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">📊 Аналитика</h3>';
h+='<div class="grid md:grid-cols-2 gap-4">';
h+='<div><label class="block text-sm font-medium mb-1">Яндекс.Метрика ID</label><input type="text" id="set-metrika" class="input-f" value="'+e(siteSettings.yandex_metrika_id||'')+'" placeholder="12345678"></div>';
h+='<div><label class="block text-sm font-medium mb-1">Google Analytics ID</label><input type="text" id="set-ga" class="input-f" value="'+e(siteSettings.google_analytics_id||'')+'" placeholder="G-XXXXXXXXXX"></div>';
h+='</div>';
h+='<p class="text-xs text-gray-500 mt-2">Коды счётчиков автоматически добавятся на все страницы сайта</p>';
h+='</div>';

// Обратная связь
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">📬 Обратная связь</h3>';
h+='<div><label class="block text-sm font-medium mb-1">Email для обратной связи</label><input type="email" id="set-contact-email" class="input-f" value="'+e(siteSettings.contact_email||'')+'" placeholder="info@kosmozaim.ru"></div>';
h+='<p class="text-xs text-gray-500 mt-2">На этот адрес будут приходить сообщения из <a href="/contact" target="_blank" class="text-blue-600 hover:underline">формы обратной связи</a></p>';
h+='</div>';

// SMTP
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">📧 Настройки почты (SMTP)</h3>';
h+='<div class="mb-3"><label class="flex items-center gap-2"><input type="checkbox" id="set-smtp-enabled" '+(siteSettings.smtp_enabled?'checked':'')+' class="w-4 h-4"><span class="text-sm font-medium">Использовать SMTP</span></label><p class="text-xs text-gray-500 mt-1">Если выключено — используется стандартный mail() (может попадать в спам)</p></div>';
h+='<div class="grid md:grid-cols-2 gap-3">';
h+='<div><label class="block text-xs font-medium mb-1">SMTP сервер</label><input type="text" id="set-smtp-host" class="input-f" value="'+e(siteSettings.smtp_host||'')+'" placeholder="smtp.yandex.ru"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Порт</label><input type="number" id="set-smtp-port" class="input-f" value="'+(siteSettings.smtp_port||465)+'" placeholder="465"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Логин</label><input type="text" id="set-smtp-user" class="input-f" value="'+e(siteSettings.smtp_user||'')+'" placeholder="info@kosmozaim.ru"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Пароль</label><input type="password" id="set-smtp-pass" class="input-f" value="'+e(siteSettings.smtp_pass||'')+'" placeholder="пароль"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Шифрование</label><select id="set-smtp-secure" class="sel-f"><option value="ssl"'+((siteSettings.smtp_secure||'ssl')==='ssl'?' selected':'')+'>SSL</option><option value="tls"'+((siteSettings.smtp_secure)==='tls'?' selected':'')+'>TLS</option><option value=""'+((siteSettings.smtp_secure)==='none'||siteSettings.smtp_secure===''?' selected':'')+'>Нет</option></select></div>';
h+='<div><label class="block text-xs font-medium mb-1">Email отправителя</label><input type="text" id="set-mail-from" class="input-f" value="'+e(siteSettings.mail_from||'')+'" placeholder="info@kosmozaim.ru"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Имя отправителя</label><input type="text" id="set-mail-from-name" class="input-f" value="'+e(siteSettings.mail_from_name||'')+'" placeholder="Космозайм"></div>';
h+='<div class="flex items-end"><button type="button" onclick="testMail()" class="btn-p text-sm w-full">📧 Тест отправки</button></div>';
h+='</div>';
h+='<div id="smtp-test-result" class="mt-3"></div>';
h+='</div>';

h+='<div class="flex gap-3"><button type="submit" class="btn-p">💾 Сохранить настройки</button></div>';
h+='</form>';

// Подсказка
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6"><p class="text-yellow-800 text-sm"><strong>⚠️ Важно:</strong> После изменения настроек рекомендуется сбросить кэш страниц (кнопка в шапке). Изменение API-ключей вступает в силу сразу.</p></div>';

document.getElementById('p-settings').innerHTML=h;
});}

function setSave(ev){if(ev)ev.preventDefault();
var data={
site_name:document.getElementById('set-name').value,
site_url:document.getElementById('set-url').value,
// (moved to AI providers tab)
// (moved to AI providers tab)
article_image_prompt_template:document.getElementById('set-article-image-prompt').value,
article_image_provider:siteSettings.article_image_provider||'yandex',
// (moved to AI providers tab)
leads_su_api_token:document.getElementById('set-leads-su-token').value,
// (moved to AI providers tab)
// (moved to AI providers tab)
yandex_metrika_id:document.getElementById('set-metrika').value,
google_analytics_id:document.getElementById('set-ga').value,
contact_email:document.getElementById('set-contact-email').value,
smtp_enabled:document.getElementById('set-smtp-enabled').checked,
smtp_host:document.getElementById('set-smtp-host').value,
smtp_port:parseInt(document.getElementById('set-smtp-port').value)||465,
smtp_user:document.getElementById('set-smtp-user').value,
smtp_pass:document.getElementById('set-smtp-pass').value,
smtp_secure:document.getElementById('set-smtp-secure').value,
mail_from:document.getElementById('set-mail-from').value,
mail_from_name:document.getElementById('set-mail-from-name').value
};
ap('/settings',{method:'POST',body:JSON.stringify(data)}).then(d=>{
if(d.success){alert('✅ Настройки сохранены!\n\nРекомендуем сбросить кэш страниц.');lSet();}
else alert('❌ '+(d.error||'Ошибка'));
});return false;}

function setUploadFavicon(input){
if(!input.files||!input.files[0])return;
var file=input.files[0];
if(file.size>1*1024*1024){alert('Файл слишком большой (макс. 1 МБ)');return;}
var fd=new FormData();
fd.append('favicon',file);
fetch(A+'/settings',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
if(d.success){alert('✅ Favicon загружен! Обновите страницу.');lSet();}
else alert('❌ '+(d.error||'Ошибка'));
}).catch(()=>alert('Ошибка загрузки'));}

function setUploadLogo(input){
if(!input.files||!input.files[0])return;
var file=input.files[0];
if(file.size>2*1024*1024){alert('Файл слишком большой (макс. 2 МБ)');return;}
var fd=new FormData();
fd.append('logo',file);
fetch(A+'/settings',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
if(d.success){alert('✅ Логотип загружен');lSet();}
else alert('❌ '+(d.error||'Ошибка'));
}).catch(()=>alert('Ошибка загрузки'));}



function agNewTopics(){
var cat=document.getElementById('ag-c').value||'займы';
var btn=document.getElementById('ag-newtopics');
var st=document.getElementById('ag-newtopics-status');
btn.textContent='⏳ Генерация...';btn.disabled=true;
st.textContent='';
ap('/generate-article',{method:'POST',body:JSON.stringify({action:'generate-topics',category:cat})}).then(d=>{
btn.textContent='🔄 Сгенерировать 10 новых тем через AI';btn.disabled=false;
if(d.success&&d.topics){
st.textContent='✅ Добавлено '+d.topics.length+' тем!';
// Обновляем список тем
aTopics=[];cm();setTimeout(()=>{aGen();},200);
}else{st.textContent='❌ '+(d.error||'Ошибка');}
}).catch(()=>{btn.textContent='🔄 Сгенерировать новые темы';btn.disabled=false;st.textContent='❌ Ошибка';});}

/* ============ USERS ============ */
function lUsers(){Promise.all([ap('/users'),ap('/bonuses?action=history'),ap('/bonuses?action=requests')]).then(([users,bonusHistory,withdrawRequests])=>{
var h='<div class="flex items-center justify-between mb-6"><h2 class="text-xl font-bold">👥 Пользователи ('+users.length+')</h2><span class="text-xs text-gray-400">КосмоБонус: 1 бонус = 1 ₽</span></div>';
if(!users.length){h+='<p class="text-gray-500 text-center py-8">Нет зарегистрированных пользователей</p>';}
else{
h+='<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-3 text-left">Email</th><th class="p-3 text-left">Имя</th><th class="p-3 text-left">Бонусы</th><th class="p-3 text-left">Заявки</th><th class="p-3 text-left">Одобрено</th><th class="p-3 text-left">Последний IP</th><th class="p-3 text-left">Регистрация</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
users.forEach(u=>{
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="p-3 font-mono text-xs">'+e(u.email)+'</td>';
h+='<td class="p-3">'+e(u.name||'—')+'</td>';
h+='<td class="p-3"><span class="px-2 py-1 rounded-lg bg-amber-50 text-amber-700 font-bold">'+(u.bonus_balance||0)+' ₽</span></td>';
h+='<td class="p-3 font-semibold">'+(u.app_count||0)+'</td>';
h+='<td class="p-3 text-green-600 font-semibold">'+(u.approved_count||0)+'</td>';
h+='<td class="p-3 font-mono text-xs text-gray-500">'+e(u.last_login_ip||'—')+'</td>';
h+='<td class="p-3 text-xs text-gray-500">'+new Date(u.created_at).toLocaleDateString('ru-RU')+'</td>';
h+='<td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(u.is_verified?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700')+'">'+(u.is_verified?'Подтверждён':'Не подтв.')+'</span></td>';
h+='<td class="p-3 text-right"><button onclick="bonusAccrualForm('+u.id+',\''+e((u.name||u.email)).replace(/'/g,"\\'")+'\')" class="text-sm text-green-600 hover:underline mr-3">Начислить бонусы</button><button onclick="bonusWithdrawForm('+u.id+',\''+e((u.name||u.email)).replace(/'/g,"\\'")+'\','+(u.bonus_balance||0)+')" class="text-sm text-blue-600 hover:underline">Списать бонусы</button></td>';
h+='</tr>';});
h+='</tbody></table></div>';}

h+='<div class="bg-white rounded-xl border mt-6"><div class="p-4 border-b"><h3 class="font-bold text-gray-900">🎁 Последние бонусные операции</h3></div>';
if(bonusHistory&&bonusHistory.length){
h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Пользователь</th><th class="p-3 text-left">Оффер</th><th class="p-3 text-right">Сумма</th><th class="p-3 text-left">Тип</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
bonusHistory.forEach(function(b){
var typeLabel=b.type==='withdrawal'?'Вывод':(b.type==='accrual'?'Начисление':(b.type==='manual'?'Ручное начисление':b.type));
var statusLabel=b.status==='confirmed'?(b.type==='withdrawal'?'Оплачено':'Подтверждено'):(b.status==='pending'?'Ожидание':'Отменено');
var statusClass=b.status==='confirmed'?'text-green-600':(b.status==='pending'?'text-yellow-600':'text-red-500');
var amountClass=Number(b.amount)>=0?'text-amber-600':'text-red-600';
h+='<tr class="border-t"><td class="p-3 text-xs text-gray-500">'+new Date(b.created_at).toLocaleString('ru-RU')+'</td><td class="p-3">'+e(b.name||b.email||'—')+'</td><td class="p-3">'+e(b.offer_title||'—')+'</td><td class="p-3 text-right font-semibold '+amountClass+'">'+(Number(b.amount)>0?'+':'')+b.amount+' ₽</td><td class="p-3">'+e(typeLabel)+'</td><td class="p-3 '+statusClass+'">'+e(statusLabel)+'</td></tr>';
});
h+='</tbody></table></div>';
}else{h+='<p class="p-4 text-gray-400">Пока нет бонусных операций</p>';}
h+='</div>';

h+='<div class="bg-white rounded-xl border mt-6"><div class="p-4 border-b"><h3 class="font-bold text-gray-900">💸 Заявки на вывод бонусов</h3></div>';
if(withdrawRequests&&withdrawRequests.length){
h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Пользователь</th><th class="p-3 text-left">Банк</th><th class="p-3 text-left">Телефон</th><th class="p-3 text-left">Владелец</th><th class="p-3 text-right">Сумма</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
withdrawRequests.forEach(function(r){
var st=r.status==='paid'?'<span class="text-green-600">Выплачено</span>':(r.status==='pending'?'<span class="text-yellow-600">Ожидает</span>':'<span class="text-red-500">Отклонено</span>');
var actions=r.status==='pending'?'<button onclick="bonusProcessRequest('+r.id+',\'paid\')" class="text-green-600 hover:underline text-sm mr-2">Выплачено</button><button onclick="bonusProcessRequest('+r.id+',\'rejected\')" class="text-red-500 hover:underline text-sm">Отклонить</button>':(r.status==='paid'?'<button onclick="bonusSendConfirmation('+r.id+')" class="text-blue-600 hover:underline text-sm">📧 Отправить подтверждение</button>':'—');
h+='<tr class="border-t"><td class="p-3 text-xs text-gray-500">'+new Date(r.created_at).toLocaleString('ru-RU')+'</td><td class="p-3">'+e(r.name||r.email||'—')+'</td><td class="p-3">'+e(r.bank_name||'—')+'</td><td class="p-3">'+e(r.phone||'—')+'</td><td class="p-3">'+e(r.cardholder_name||'—')+'</td><td class="p-3 text-right font-semibold text-amber-600">'+r.amount+' ₽</td><td class="p-3">'+st+'</td><td class="p-3 text-right">'+actions+'</td></tr>';
});
h+='</tbody></table></div>';
}else{h+='<p class="p-4 text-gray-400">Нет заявок на вывод</p>';}
h+='</div>';

document.getElementById('p-users').innerHTML=h;loadBonusWithdrawAlert();});}

function bonusProcessRequest(requestId,action){
var comment=prompt(action==='paid'?'Комментарий к выплате (необязательно):':'Причина отклонения (необязательно):','')||'';
ap('/bonuses?action=process-request',{method:'POST',body:JSON.stringify({request_id:requestId,process_action:action,admin_comment:comment})}).then(function(r){
if(r.error){alert(r.error);return;}
alert(action==='paid'?'✅ Заявка отмечена как выплаченная':'⚠️ Заявка отклонена');
loadBonusWithdrawAlert();
lUsers();
}).catch(function(){alert('Ошибка обработки заявки');});
}

function bonusSendConfirmation(requestId){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📧 Отправить подтверждение перевода</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form id="bonus-confirm-form" onsubmit="return bonusSendConfirmationSubmit(event,'+requestId+')"><div class="space-y-3">'+
'<div><label class="block text-xs font-medium mb-1">Комментарий (необязательно)</label><input id="bc-comment" class="input-f" placeholder="Например: Перевод выполнен через СБП"></div>'+
'<div><label class="block text-xs font-medium mb-1">Скрин или PDF транзакции</label><input type="file" id="bc-file" accept="image/*,.pdf" class="input-f"></div>'+
'<p class="text-xs text-gray-400">Допустимые форматы: PNG, JPG, PDF. Макс. размер: 5 МБ.</p>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">📧 Отправить письмо</button></div></form>');
}

function bonusSendConfirmationSubmit(ev,requestId){
ev.preventDefault();
var btn=ev.target.querySelector('button[type="submit"]');
if(btn){btn.disabled=true;btn.textContent='Отправка...';}
var fd=new FormData();
fd.append('request_id',requestId);
fd.append('comment',document.getElementById('bc-comment').value||'');
var fileInput=document.getElementById('bc-file');
if(fileInput&&fileInput.files[0])fd.append('attachment',fileInput.files[0]);
fetch(A+'/bonus-send-confirmation',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
if(d.error){alert('Ошибка: '+d.error);return;}
alert('✅ '+(d.message||'Письмо отправлено'));
cm();
}).catch(function(){alert('Ошибка отправки');}).finally(function(){if(btn){btn.disabled=false;btn.textContent='📧 Отправить письмо';}});
return false;
}

function bonusAccrualForm(userId,name){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🎁 Начислить бонусы</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return bonusAccrualSave(event,'+userId+')"><div class="space-y-3">'+
'<p class="text-sm text-gray-600">Пользователь: <strong>'+e(name)+'</strong></p>'+
'<div><label class="block text-xs font-medium mb-1">Сумма начисления</label><input id="ba-amount" type="number" class="input-f" min="1" value="100" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Комментарий</label><input id="ba-desc" class="input-f" placeholder="Например: Тестовое начисление бонусов"></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Начислить</button></div></form>');
}

function bonusAccrualSave(ev,userId){
ev.preventDefault();
var amount=parseInt(document.getElementById('ba-amount').value)||0;
var description=document.getElementById('ba-desc').value||'';
ap('/bonuses?action=accrue',{method:'POST',body:JSON.stringify({user_id:userId,amount:amount,description:description})}).then(function(r){
if(r.error){alert(r.error);return;}
alert('✅ Начислено '+amount+' ₽. Новый баланс: '+(r.new_balance||0)+' ₽');
cm();lUsers();
}).catch(function(){alert('Ошибка начисления бонусов');});
return false;}

function bonusWithdrawForm(userId,name,balance){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🎁 Списание бонусов</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return bonusWithdrawSave(event,'+userId+')"><div class="space-y-3">'+
'<p class="text-sm text-gray-600">Пользователь: <strong>'+e(name)+'</strong></p>'+
'<p class="text-sm text-gray-600">Доступно бонусов: <strong>'+balance+' ₽</strong></p>'+
'<div><label class="block text-xs font-medium mb-1">Сумма списания</label><input id="bw-amount" type="number" class="input-f" min="1" max="'+balance+'" value="'+balance+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Комментарий</label><input id="bw-desc" class="input-f" placeholder="Например: Выплачено на карту"></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Списать</button></div></form>');
}

function bonusWithdrawSave(ev,userId){
ev.preventDefault();
var amount=parseInt(document.getElementById('bw-amount').value)||0;
var description=document.getElementById('bw-desc').value||'';
ap('/bonuses?action=withdraw',{method:'POST',body:JSON.stringify({user_id:userId,amount:amount,description:description})}).then(function(r){
if(r.error){alert(r.error);return;}
alert('✅ Списано '+amount+' ₽. Новый баланс: '+(r.new_balance||0)+' ₽');
cm();lUsers();
}).catch(function(){alert('Ошибка списания бонусов');});
return false;}


/* ============ SECURITY ============ */
function secAp(action,opts){return fetch(A+'/security?action='+action,{headers:{'Content-Type':'application/json'},...opts}).then(r=>r.json());}
function lSec(){secAp('overview').then(d=>{
var h='<h2 class="text-xl font-bold mb-6">🔒 Безопасность</h2>';

// Текущий IP
h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center justify-between"><div><span class="text-blue-700 font-semibold">Ваш IP:</span> <span class="font-mono text-blue-900">'+d.currentIp+'</span></div><button onclick="secAddIp(\''+d.currentIp+'\',\'Мой IP\')" class="text-sm text-blue-600 hover:underline">+ В белый список</button></div>';

// Счётчики
h+='<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">'+d.successToday+'</p><p class="text-xs text-gray-500">Успешных входов</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-red-600">'+d.failedToday+'</p><p class="text-xs text-gray-500">Неудачных сегодня</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-orange-600">'+d.blockedIps.length+'</p><p class="text-xs text-gray-500">Заблокировано IP</p></div>';
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">'+d.whitelist.length+'</p><p class="text-xs text-gray-500">В белом списке</p></div></div>';

// Заблокированные IP
if(d.blockedIps.length){
h+='<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6"><h3 class="font-semibold text-red-800 mb-3">⛔ Заблокированные IP (10+ неудачных попыток за 15 мин)</h3><div class="space-y-2">';
d.blockedIps.forEach(b=>{h+='<div class="flex items-center justify-between bg-white rounded-lg p-3 border border-red-100"><span class="font-mono text-sm">'+e(b.ip)+' <span class="text-red-500">('+b.fails+' попыток)</span></span><button onclick="secUnblock(\''+b.ip+'\')" class="text-sm text-blue-600 hover:underline">Разблокировать</button></div>';});
h+='</div></div>';}

// IP Whitelist
h+='<div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-bold text-gray-900 mb-1">🛡️ Белый список IP</h3><p class="text-sm text-gray-500 mb-4">Если список пуст — доступ разрешён всем. Если добавлен хотя бы один IP — только они смогут войти в админку.</p>';
if(d.whitelist.length){h+='<div class="space-y-2 mb-4">';d.whitelist.forEach(w=>{h+='<div class="flex items-center justify-between bg-gray-50 rounded-lg p-3 border"><div><span class="font-mono text-sm font-semibold">'+e(w.ip)+'</span>';if(w.note)h+=' <span class="text-gray-400 text-xs ml-2">'+e(w.note)+'</span>';h+='</div><button onclick="secRemoveIp('+w.id+')" class="text-red-500 hover:underline text-sm">Удалить</button></div>';});h+='</div>';}
h+='<div class="flex gap-2"><input id="sec-ip" class="input-f flex-1" placeholder="IP адрес (напр. 31.163.64.147)"><input id="sec-note" class="input-f flex-1" placeholder="Заметка (необязательно)"><button onclick="secAddIpForm()" class="btn-p text-sm">+ Добавить</button></div>';
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-3 text-xs text-yellow-700">⚠️ Будьте осторожны! Если добавите IP и ваш текущий IP не в списке — потеряете доступ к админке.</div></div>';

// Лог входов
h+='<div class="bg-white rounded-xl border mb-6"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold text-gray-900">📋 Лог входов (последние 30)</h3><button onclick="secClearLog()" class="text-xs text-gray-400 hover:text-red-500">Очистить старые</button></div>';
if(d.loginLog.length){h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Время</th><th class="p-3 text-left">Логин</th><th class="p-3 text-left">IP</th><th class="p-3 text-left">Результат</th></tr></thead><tbody>';
d.loginLog.forEach(l=>{h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 text-xs text-gray-500">'+new Date(l.created_at).toLocaleString('ru-RU')+'</td><td class="p-3 font-mono text-xs">'+e(l.username||'—')+'</td><td class="p-3 font-mono text-xs">'+e(l.ip)+'</td><td class="p-3">'+(l.success?'<span class="text-green-600 text-xs font-semibold">✅ Успех</span>':'<span class="text-red-600 text-xs font-semibold">❌ Отказ</span>')+'</td></tr>';});
h+='</tbody></table></div>';}else{h+='<p class="p-4 text-gray-500 text-sm">Нет записей</p>';}
h+='</div>';

// Рекомендации
h+='<div class="bg-gray-50 rounded-xl border p-4"><h4 class="font-semibold text-sm text-gray-700 mb-2">💡 Рекомендации по безопасности</h4><ul class="text-xs text-gray-500 space-y-1 list-disc pl-4"><li>Смените стандартный пароль admin123</li><li>Добавьте свой IP в белый список</li><li>Регулярно проверяйте лог входов</li><li>Используйте сложный пароль (буквы, цифры, символы)</li></ul></div>';

document.getElementById('p-security').innerHTML=h;});}
function secAddIp(ip,note){secAp('add-ip',{method:'POST',body:JSON.stringify({ip:ip,note:note})}).then(()=>lSec());}
function secAddIpForm(){var ip=document.getElementById('sec-ip').value.trim();if(!ip){alert('Введите IP');return;}secAddIp(ip,document.getElementById('sec-note').value.trim());}
function secRemoveIp(id){if(confirm('Удалить IP из белого списка?'))secAp('remove-ip',{method:'POST',body:JSON.stringify({id:id})}).then(()=>lSec());}
function secUnblock(ip){secAp('unblock-ip',{method:'POST',body:JSON.stringify({ip:ip})}).then(()=>lSec());}
function secClearLog(){if(confirm('Удалить записи старше 30 дней?'))secAp('clear-log',{method:'POST'}).then(()=>lSec());}

function goHealthFix(tab, itemType, itemId){
  try{ window.scrollTo({top:0,behavior:'smooth'}); }catch(e){ window.scrollTo(0,0); }
  if(itemType==='offer' && itemId){
    sw('offers');
    setTimeout(function(){
      fetch(A+'/offers/'+itemId).then(function(r){return r.json();}).then(function(row){
        if(row && !row.error) oForm(row);
      }).catch(function(){});
    }, 100);
    return;
  }
  if(itemType==='tag' && itemId){
    sw('tags');
    setTimeout(function(){
      ap('/tags').then(function(list){
        var tag=(list||[]).find(function(t){return String(t.id)===String(itemId);});
        if(tag) tForm(tag);
      });
    }, 100);
    return;
  }
  if(itemType==='article' && itemId){
    sw('articles');
    setTimeout(function(){
      ap('/articles').then(function(list){
        var art=(list||[]).find(function(a){return String(a.id)===String(itemId);});
        if(art) aForm(art);
      });
    }, 100);
    return;
  }
  if(itemType==='category' && itemId){
    sw('cats');
    setTimeout(function(){
      ap('/categories').then(function(list){
        var cat=(list||[]).find(function(c){return String(c.id)===String(itemId);});
        if(cat) catForm(cat);
      });
    }, 100);
    return;
  }
  if(itemType==='cityseo'){
    sw('cityseo');
    return;
  }
  sw(tab);
}

/* ============ HEALTH CHECK ============ */
function seoDupFix(scope){
if(!confirm('Запустить автоисправление SEO дублей ('+scope+')? Будут обновлены meta title и/или description через YandexGPT или шаблонный fallback.')) return;
fetch(A+'/seo-duplicates/fix',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({scope:scope})})
.then(r=>r.json())
.then(function(d){
  if(d.error){ alert('Ошибка: '+d.error); return; }
  alert('Готово! Исправлено title: '+(d.fixed_titles||0)+'; description: '+(d.fixed_descriptions||0)+'; режим: '+(d.scope||scope)+'; источник: '+(d.provider||'—'));
  lHealth();
})
.catch(function(err){ alert('Ошибка: '+(err&&err.message?err.message:'Неизвестная ошибка')); });
}

function lSeoDuplicates(){
var slot=document.getElementById('seo-dup-slot');
if(!slot) slot=document.getElementById('p-health');
if(!slot) return;
var shell='';
shell+='<div id="seo-dup-shell" class="bg-white rounded-xl border p-6 mt-6">';
shell+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"><h3 class="text-lg font-bold">🔍 SEO: Дубли title и description</h3><div class="flex flex-wrap gap-2"><button type="button" onclick="seoDupFix(&#39;titles&#39;)" class="bg-white border border-red-200 text-red-700 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-red-50">🤖 Исправить title</button><button type="button" onclick="seoDupFix(&#39;descriptions&#39;)" class="bg-white border border-yellow-200 text-yellow-700 px-3 py-2 rounded-lg text-xs font-semibold hover:bg-yellow-50">🤖 Исправить description</button><button type="button" onclick="seoDupFix(&#39;all&#39;)" class="bg-purple-600 text-white px-3 py-2 rounded-lg text-xs font-semibold hover:bg-purple-700">🤖 Исправить всё</button></div></div>';
shell+='<div class="text-xs text-gray-500 mb-4">Исправление использует YandexGPT, если API-ключ настроен. Иначе применяется шаблонный fallback для снятия дублей.</div>';
shell+='<div id="seo-dup-loading"><p class="text-gray-500">⏳ Проверка SEO дублей...</p></div>';
shell+='</div>';
slot.innerHTML=shell;
ap('/seo-duplicates').then(function(d){
var box=document.getElementById('seo-dup-loading');
if(!box)return;
if(d.error){ box.innerHTML='<div class="bg-red-50 rounded-lg p-4"><h4 class="font-semibold text-red-700 mb-2">Ошибка проверки SEO</h4><p class="text-sm text-red-600">'+e(d.error)+'</p></div>'; return; }
var totalPages=Number(d.total_pages||0), dupTitles=Number(d.duplicate_titles||0), dupDescriptions=Number(d.duplicate_descriptions||0);
var h='';
h+='<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">';
h+='<div class="bg-blue-50 rounded-lg p-3 text-center"><p class="text-2xl font-bold text-blue-600">'+totalPages+'</p><p class="text-xs text-gray-500">Всего страниц</p></div>';
h+='<div class="'+(dupTitles>0?'bg-red-50':'bg-green-50')+' rounded-lg p-3 text-center"><p class="text-2xl font-bold '+(dupTitles>0?'text-red-600':'text-green-600')+'">'+dupTitles+'</p><p class="text-xs text-gray-500">Дублей title</p></div>';
h+='<div class="'+(dupDescriptions>0?'bg-red-50':'bg-green-50')+' rounded-lg p-3 text-center"><p class="text-2xl font-bold '+(dupDescriptions>0?'text-red-600':'text-green-600')+'">'+dupDescriptions+'</p><p class="text-xs text-gray-500">Дублей description</p></div>';
h+='</div>';
if(d.titles&&d.titles.length){
h+='<div class="mb-4"><h4 class="font-semibold text-red-700 mb-2">⚠️ Дублирующиеся Title ('+d.titles.length+'):</h4>';
d.titles.forEach(function(dup){
h+='<div class="bg-red-50 rounded-lg p-3 mb-2"><p class="text-sm font-medium text-red-800">'+e(dup.title)+'</p><p class="text-xs text-red-600 mt-1">Найдено на '+dup.count+' страницах:</p><ul class="mt-1">';
dup.pages.forEach(function(pg){h+='<li class="text-xs text-gray-600">• <span class="font-mono">'+e(pg.url)+'</span> <span class="text-gray-400">('+pg.type+')</span></li>';});
h+='</ul></div>';});
h+='</div>';}
if(d.descriptions&&d.descriptions.length){
h+='<div><h4 class="font-semibold text-red-700 mb-2">⚠️ Дублирующиеся Description ('+d.descriptions.length+'):</h4>';
d.descriptions.forEach(function(dup){
h+='<div class="bg-yellow-50 rounded-lg p-3 mb-2"><p class="text-sm font-medium text-yellow-800">'+e(dup.description)+'</p><p class="text-xs text-yellow-600 mt-1">Найдено на '+dup.count+' страницах:</p><ul class="mt-1">';
dup.pages.forEach(function(pg){h+='<li class="text-xs text-gray-600">• <span class="font-mono">'+e(pg.url)+'</span> <span class="text-gray-400">('+pg.type+')</span></li>';});
h+='</ul></div>';});
h+='</div>';}
if(!dupTitles&&!dupDescriptions){
h+='<div class="bg-green-50 rounded-lg p-4 text-center"><p class="text-green-700 font-semibold">✅ Дублей не найдено!</p></div>';}
box.innerHTML=h;
}).catch(function(err){
var box=document.getElementById('seo-dup-loading');
if(box)box.innerHTML='<div class="bg-red-50 rounded-lg p-4"><h4 class="font-semibold text-red-700 mb-2">Ошибка проверки SEO</h4><p class="text-sm text-red-600">'+e(err.message||'')+'</p></div>';
});}

function lHealth(){
var el=document.getElementById('p-health');
el.innerHTML='<div id="health-main-loading" class="text-center py-12"><p class="text-gray-500">⏳ Проверка...</p></div><div id="seo-dup-slot"></div>';
setTimeout(function(){try{lSeoDuplicates();}catch(e){}},0);
ap('/health-check').then(d=>{
var s=d.score||0;
var barColor=s>=80?'bg-green-500':s>=50?'bg-yellow-500':'bg-red-500';
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🏥 Здоровье сайта</h2><div class="flex gap-2"><button onclick="lHealth()" class="btn-p text-sm">🔄 Проверить</button><span class="text-xs text-gray-400">'+e(d.timestamp||'')+'</span></div></div>';

h+='<div class="bg-white rounded-2xl border p-6 mb-6 text-center"><p class="text-5xl font-bold '+(s>=80?'text-green-600':s>=50?'text-yellow-600':'text-red-600')+'">'+s+'<span class="text-2xl text-gray-400">/100</span></p><div class="w-full bg-gray-200 rounded-full h-4 mt-4 max-w-md mx-auto"><div class="'+barColor+' rounded-full h-4 transition-all" style="width:'+s+'%"></div></div></div>';

var errors=(d.checks||[]).filter(c=>c.level==='error');
var warnings=(d.checks||[]).filter(c=>c.level==='warning');
var oks=(d.checks||[]).filter(c=>c.level==='ok');
var infos=(d.checks||[]).filter(c=>c.level==='info');

if(errors.length){
h+='<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4"><h3 class="font-bold text-red-800 mb-3">❌ Критичные ('+errors.length+')</h3><div class="space-y-2">';
errors.forEach(c=>{h+='<div class="text-sm text-red-700"><div class="flex items-center justify-between gap-3"><div>• '+e(c.msg)+'</div>'+(c.fixTab?'<button type="button" data-fix-tab="'+e(c.fixTab)+'" data-fix-item-type="'+e(c.fixItemType||'')+'" data-fix-item-id="'+e(String(c.fixFirstId||''))+'" class="health-fix-btn text-xs px-2 py-1 rounded bg-red-100 text-red-700 border border-red-200 hover:bg-red-200">Исправить</button>':'')+'</div>'; if(c.items&&c.items.length){ h+='<ul class="mt-2 ml-5 list-disc text-xs text-red-500 space-y-1">'+c.items.slice(0,10).map(i=>{ if(typeof i==='object'){ return '<li><button type="button" class="underline hover:no-underline text-left" onclick="goHealthFix(\''+c.fixTab+'\',\''+(c.fixItemType||'')+'\',\''+i.id+'\')">'+e(i.title||'—')+'</button></li>'; } return '<li>'+e(i)+'</li>'; }).join('')+'</ul>'; } h+='</div>';});
h+='</div></div>';}

if(warnings.length){
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4"><h3 class="font-bold text-yellow-800 mb-3">⚠️ Предупреждения ('+warnings.length+')</h3><div class="space-y-2">';
warnings.forEach(c=>{h+='<div class="text-sm text-yellow-700"><div class="flex items-center justify-between gap-3"><div>• '+e(c.msg)+'</div>'+(c.fixTab?'<button type="button" data-fix-tab="'+e(c.fixTab)+'" data-fix-item-type="'+e(c.fixItemType||'')+'" data-fix-item-id="'+e(String(c.fixFirstId||''))+'" class="health-fix-btn text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800 border border-yellow-200 hover:bg-yellow-200">Исправить</button>':'')+'</div>'; if(c.items&&c.items.length){ h+='<ul class="mt-2 ml-5 list-disc text-xs text-yellow-600 space-y-1">'+c.items.slice(0,10).map(i=>{ if(typeof i==='object'){ return '<li><button type="button" class="underline hover:no-underline text-left" onclick="goHealthFix(\''+c.fixTab+'\',\''+(c.fixItemType||'')+'\',\''+i.id+'\')">'+e(i.title||'—')+'</button></li>'; } return '<li>'+e(i)+'</li>'; }).join('')+'</ul>'; } h+='</div>';});
h+='</div></div>';}

if(oks.length){
h+='<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4"><h3 class="font-bold text-green-800 mb-3">✅ Всё хорошо ('+oks.length+')</h3><div class="space-y-2">';
oks.forEach(c=>{h+='<div class="text-sm text-green-700">• '+e(c.msg)+'</div>';});
h+='</div></div>';}

if(infos.length){
h+='<div class="bg-gray-50 border border-gray-200 rounded-xl p-4"><h3 class="font-bold text-gray-700 mb-3">ℹ️ Информация</h3><div class="space-y-2">';
infos.forEach(c=>{h+='<div class="text-sm text-gray-600">• '+e(c.msg)+'</div>';});
h+='</div>';}

var hm=document.getElementById('health-main-loading'); if(hm){ hm.outerHTML='<div id="health-main">'+h+'</div>'; } else { el.innerHTML='<div id="health-main">'+h+'</div><div id="seo-dup-slot"></div>'; } document.getElementById('health-main').querySelectorAll('.health-fix-btn').forEach(function(btn){btn.addEventListener('click',function(){goHealthFix(btn.getAttribute('data-fix-tab'), btn.getAttribute('data-fix-item-type')||'', btn.getAttribute('data-fix-item-id')||'');});});});}



/* ============ INDEXING / SEO ============ */
function lIndexing(){
var el=document.getElementById('p-indexing');
el.innerHTML='<div class="bg-white rounded-xl border p-6"><p class="text-gray-500">⏳ Загрузка...</p></div>';

ap('/indexing?action=stats').then(function(d){
if(d.error){ el.innerHTML='<div class="bg-red-50 rounded-xl p-6 text-red-700">'+e(d.error)+'</div>'; return; }

var h='<div class="space-y-6">';

// Заголовок
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
h+='<div><h2 class="text-xl font-bold text-gray-900">🔍 Индексация и SEO</h2><p class="text-sm text-gray-500 mt-1">Ускорение индексации в Яндекс и Google</p></div>';
h+='<div class="flex gap-2"><button onclick="idxSync()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🔄 Синхронизировать URL</button></div>';
h+='</div>';

// Статистика
h+='<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">';
h+='<div class="bg-blue-50 rounded-xl p-4 text-center"><p class="text-3xl font-bold text-blue-600">'+d.total_urls+'</p><p class="text-xs text-gray-500 mt-1">Всего URL</p></div>';
h+='<div class="bg-yellow-50 rounded-xl p-4 text-center"><p class="text-3xl font-bold text-yellow-600">'+d.recently_modified+'</p><p class="text-xs text-gray-500 mt-1">Изменено за 7 дней</p></div>';
h+='<div class="bg-orange-50 rounded-xl p-4 text-center"><p class="text-3xl font-bold text-orange-600">'+d.pending_yandex+'</p><p class="text-xs text-gray-500 mt-1">Ожидают Яндекс</p>'+(d.submitted_yandex?'<p class="text-xs text-green-600 mt-0.5">✓ '+d.submitted_yandex+' отправлено</p>':'')+'</div>';
h+='<div class="bg-green-50 rounded-xl p-4 text-center"><p class="text-3xl font-bold text-green-600">'+d.pending_google+'</p><p class="text-xs text-gray-500 mt-1">Ожидают Google</p>'+(d.submitted_google?'<p class="text-xs text-green-600 mt-0.5">✓ '+d.submitted_google+' отправлено</p>':'')+'</div>';
h+='</div>';

// По типам
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">📊 URL по типам</h3><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">';
var typeLabels={offer:'Офферы',article:'Статьи',city:'Города',city_tag:'Город+Тег',category:'Категории',static:'Статические'};
(d.by_type||[]).forEach(function(t){
  h+='<div class="bg-gray-50 rounded-lg p-3 text-center"><p class="text-lg font-bold text-gray-700">'+t.cnt+'</p><p class="text-xs text-gray-500">'+(typeLabels[t.url_type]||t.url_type)+'</p></div>';
});
h+='</div></div>';

// SEO-файлы (автогенерация)
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">📁 SEO-файлы (автогенерация)</h3>';
h+='<div class="grid sm:grid-cols-3 gap-4">';
h+='<div class="border rounded-lg p-4"><div class="flex items-center justify-between mb-2"><h4 class="font-semibold text-blue-600">📄 sitemap.xml</h4><a href="/sitemap.xml" target="_blank" class="text-xs text-blue-500 hover:underline">Открыть →</a></div><p class="text-xs text-gray-500">Автоматически генерируется из БД</p><div id="sitemap-stats" class="mt-2 text-xs text-gray-400">Загрузка...</div></div>';
h+='<div class="border rounded-lg p-4"><div class="flex items-center justify-between mb-2"><h4 class="font-semibold text-green-600">🤖 robots.txt</h4><a href="/robots.txt" target="_blank" class="text-xs text-green-500 hover:underline">Открыть →</a></div><p class="text-xs text-gray-500">Правила для поисковых роботов</p><button onclick="idxPreviewRobots()" class="mt-2 text-xs text-green-600 hover:underline">Предпросмотр</button></div>';
h+='<div class="border rounded-lg p-4"><div class="flex items-center justify-between mb-2"><h4 class="font-semibold text-purple-600">🧠 llms.txt</h4><a href="/llms.txt" target="_blank" class="text-xs text-purple-500 hover:underline">Открыть →</a></div><p class="text-xs text-gray-500">Для AI-систем (ChatGPT, Claude)</p><button onclick="idxPreviewLlms()" class="mt-2 text-xs text-purple-600 hover:underline">Предпросмотр</button></div>';
h+='</div></div>';

// SEO-аудит
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔍 SEO-аудит</h3>';
h+='<div class="flex gap-2 mb-3"><button onclick="runSeoAudit()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">▶ Запустить аудит</button></div>';
h+='<div id="seo-audit-result"></div>';
h+='</div>';

// Проверка страниц
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔍 Проверка страниц</h3>';
h+='<p class="text-xs text-gray-500 mb-3">Проверка доступности всех страниц из sitemap. Найдёт 404 и другие ошибки.</p>';
h+='<div class="flex flex-wrap gap-2 mb-3">';
h+='<button onclick="pageCheckSample()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">⚡ Быстрая проверка</button>';
h+='<button onclick="pageCheckFull()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">🔄 Полная проверка</button>';
h+='<div class="flex items-center gap-2"><input id="page-check-url" class="input-f text-sm" placeholder="/karty/kreditnye/moskva" style="width:260px"><button onclick="pageCheckOne()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">Проверить</button></div>';
h+='</div>';
h+='<div id="page-check-result"></div>';
h+='</div>';

// Google Indexing API
h+='<div class="grid lg:grid-cols-2 gap-6">';
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔵 Google Indexing API</h3>';
h+='<div id="google-idx-status"><p class="text-gray-500 text-sm">⏳ Проверка...</p></div>';
h+='</div>';
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔴 Яндекс.Вебмастер API</h3>';
h+='<div id="yandex-wm-status"><p class="text-gray-500 text-sm">⏳ Проверка...</p></div>';
h+='</div>';
h+='</div>';

// Изменения контента
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold text-gray-900">📝 Последние изменения контента</h3><select id="idx-changes-days" onchange="idxLoadChanges()" class="text-sm border rounded px-2 py-1"><option value="7">7 дней</option><option value="14">14 дней</option><option value="30">30 дней</option></select></div><div id="idx-changes" class="p-4"><p class="text-gray-500 text-sm">⏳ Загрузка...</p></div></div>';

// Экспорт URL
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">📤 Экспорт URL для переобхода</h3>';
h+='<div class="grid sm:grid-cols-2 gap-4">';

// Яндекс
h+='<div class="border rounded-lg p-4"><h4 class="font-semibold text-red-600 mb-2">🔴 Яндекс.Вебмастер</h4>';
h+='<p class="text-xs text-gray-500 mb-3">Скопируйте URL и вставьте в раздел "Переобход страниц"</p>';
h+='<div class="space-y-2">';
h+='<button onclick="idxExport(&#39;yandex&#39;,&#39;pending&#39;)" class="w-full bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm font-medium">📋 Ожидающие индексации ('+d.pending_yandex+')</button>';
h+='<button onclick="idxExport(&#39;yandex&#39;,&#39;recent&#39;)" class="w-full bg-red-50 hover:bg-red-100 text-red-700 px-3 py-2 rounded-lg text-sm font-medium">📋 Изменённые за 7 дней ('+d.recently_modified+')</button>';
h+='</div></div>';

// Google
h+='<div class="border rounded-lg p-4"><h4 class="font-semibold text-blue-600 mb-2">🔵 Google Search Console</h4>';
h+='<p class="text-xs text-gray-500 mb-3">Скопируйте URL и используйте "Проверка URL"</p>';
h+='<div class="space-y-2">';
h+='<button onclick="idxExport(&#39;google&#39;,&#39;pending&#39;)" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium">📋 Ожидающие индексации ('+d.pending_google+')</button>';
h+='<button onclick="idxExport(&#39;google&#39;,&#39;recent&#39;)" class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-sm font-medium">📋 Изменённые за 7 дней</button>';
h+='</div></div>';

h+='</div></div>';

// Недавние URL
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold text-gray-900">🕐 Недавно изменённые URL</h3><button onclick="idxLoadRecent()" class="text-sm text-blue-600 hover:underline">Обновить</button></div><div id="idx-recent" class="p-4"><p class="text-gray-500 text-sm">Нажмите "Обновить" для загрузки</p></div></div>';

// Лог
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b"><h3 class="font-bold text-gray-900">📜 Лог отправок</h3></div>';
if(d.recent_logs&&d.recent_logs.length){
h+='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Сервис</th><th class="p-3 text-left">Действие</th><th class="p-3 text-left">URL</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
d.recent_logs.forEach(function(log){
  var svc=log.service==='yandex'?'🔴 Яндекс':'🔵 Google';
  var status=log.status==='success'?'<span class="text-green-600">✓</span>':'<span class="text-red-600">✗</span>';
  h+='<tr class="border-t"><td class="p-3 text-xs text-gray-500">'+new Date(log.created_at).toLocaleString('ru-RU')+'</td><td class="p-3">'+svc+'</td><td class="p-3">'+e(log.action)+'</td><td class="p-3">'+(log.urls_success?log.urls_success+'/':'')+log.urls_count+'</td><td class="p-3">'+status+'</td></tr>';
});
h+='</tbody></table></div>';
}else{
h+='<p class="p-4 text-gray-500 text-sm">Нет записей</p>';
}
h+='</div>';

h+='</div>';
el.innerHTML=h;
setTimeout(function(){ idxLoadSeoStats(); idxLoadChanges(); gIdxLoadStatus(); ywmLoadStatus(); }, 300);
});
}

function idxSync(){
if(!confirm('Синхронизировать все URL из базы данных?')) return;
ap('/indexing?action=sync',{method:'POST'}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
alert('✓ Добавлено: '+(d.added||0)+', обновлено: '+(d.updated||0)+', без изменений: '+(d.unchanged||0));
lIndexing();
});
}

function idxExport(service,type){
ap('/indexing?action=export-urls&service='+service+'&type='+type).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
if(d.count===0){ alert('Нет URL для экспорта'); return; }

// Показываем модальное окно с URL
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📤 URL для '+(service==='yandex'?'Яндекс.Вебмастера':'Google Search Console')+' ('+d.count+')</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="mb-4"><p class="text-sm text-gray-500 mb-2">Скопируйте список URL:</p>';
h+='<textarea id="idx-urls-textarea" class="input-f w-full h-64 font-mono text-xs" readonly>'+e(d.text)+'</textarea></div>';
h+='<div class="flex gap-3"><button onclick="idxCopyUrls()" class="btn-p">📋 Скопировать</button>';
if(service==='yandex'){
h+='<a href="https://webmaster.yandex.ru/" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">→ Открыть Яндекс.Вебмастер</a>';
}else{
h+='<a href="https://search.google.com/search-console" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">→ Открыть Google Search Console</a>';
}
h+='<button onclick="idxMarkSubmitted(&#39;'+service+'&#39;)" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">✓ Отметить отправленными</button></div>';
modal(h);

// Сохраняем URL для отметки
window._idxExportedUrls=d.urls.map(function(u){ return u.replace(SITE_URL,''); });
});
}

function idxCopyUrls(){
var ta=document.getElementById('idx-urls-textarea');
ta.select();
document.execCommand('copy');
alert('✓ Скопировано!');
}

function idxMarkSubmitted(service){
var urls=window._idxExportedUrls||[];
if(!urls.length){ alert('Нет URL'); return; }
if(!confirm('Отметить '+urls.length+' URL как отправленные в '+(service==='yandex'?'Яндекс':'Google')+'?')) return;

ap('/indexing?action=mark-submitted',{method:'POST',body:JSON.stringify({service:service,urls:urls})}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
alert('✓ Отмечено: '+d.marked);
cm();
lIndexing();
});
}

function idxLoadRecent(){
var box=document.getElementById('idx-recent');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Загрузка...</p>';

ap('/indexing?action=recent&days=7').then(function(urls){
if(!urls||!urls.length){ box.innerHTML='<p class="text-gray-500 text-sm">Нет изменений за последние 7 дней</p>'; return; }

var h='<div class="space-y-1 max-h-64 overflow-y-auto">';
urls.slice(0,50).forEach(function(u){
var yIcon=u.indexed_yandex?'🟢':'🔴';
var gIcon=u.indexed_google?'🟢':'🔵';
h+='<div class="flex items-center justify-between py-1 border-b border-gray-100 last:border-0">';
h+='<span class="text-sm font-mono text-gray-600 truncate flex-1">'+e(u.url)+'</span>';
h+='<span class="text-xs text-gray-400 ml-2">'+new Date(u.last_modified).toLocaleDateString('ru-RU')+'</span>';
h+='<span class="ml-2" title="Яндекс/Google">'+yIcon+gIcon+'</span>';
h+='</div>';
});
if(urls.length>50) h+='<p class="text-xs text-gray-400 mt-2">...и ещё '+(urls.length-50)+' URL</p>';
h+='</div>';
box.innerHTML=h;
});
}


function idxLoadSeoStats(){
ap('/indexing?action=seo-files').then(function(d){
var box=document.getElementById('sitemap-stats');
if(!box) return;
if(d.error){ box.innerHTML='<span class="text-red-500">Ошибка</span>'; return; }
if(d.sitemap){
box.innerHTML='URL: <strong>'+d.sitemap.total_urls+'</strong> (офферы: '+d.sitemap.offers+', статьи: '+d.sitemap.articles+', город+тег: '+d.sitemap.city_tag_pages+')';
}
}).catch(function(){ var b=document.getElementById('sitemap-stats'); if(b) b.innerHTML='—'; });
}

function idxPreviewLlms(){
ap('/indexing?action=preview-llms').then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧠 llms.txt</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto"><pre class="text-xs text-gray-700 whitespace-pre-wrap">'+e(d.content)+'</pre></div><div class="flex justify-end mt-4"><a href="/llms.txt" target="_blank" class="btn-p">Открыть →</a></div>');
}).catch(function(){ alert('Ошибка загрузки'); });
}

function idxPreviewRobots(){
ap('/indexing?action=preview-robots').then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🤖 robots.txt</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><div class="bg-gray-50 rounded-lg p-4"><pre class="text-sm text-gray-700 whitespace-pre-wrap">'+e(d.content)+'</pre></div><div class="flex justify-end mt-4"><a href="/robots.txt" target="_blank" class="btn-p">Открыть →</a></div>');
}).catch(function(){ alert('Ошибка загрузки'); });
}

function idxLoadChanges(){
var sel=document.getElementById('idx-changes-days');
var days=sel?sel.value:7;
var box=document.getElementById('idx-changes');
if(!box) return;
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Загрузка...</p>';
ap('/indexing?action=changes&days='+days).then(function(d){
if(!d.changes||!d.changes.length){ box.innerHTML='<p class="text-gray-500 text-sm">Нет изменений за выбранный период</p>'; return; }
var typeIcons={offer:'📋',article:'📰',tag:'🏷️',city_seo:'🏙️',city_tag_seo:'🗺️'};
var typeNames={offer:'Оффер',article:'Статья',tag:'Тег',city_seo:'SEO города',city_tag_seo:'Город+тег'};
var h='<div class="space-y-2 max-h-64 overflow-y-auto">';
d.changes.forEach(function(c){
h+='<div class="flex items-center gap-3 py-2 border-b border-gray-100">';
h+='<span class="text-lg">'+(typeIcons[c.type]||'📄')+'</span>';
h+='<div class="flex-1 min-w-0"><p class="text-sm font-medium text-gray-900 truncate">'+e(c.title)+'</p>';
h+='<p class="text-xs text-gray-500">'+(typeNames[c.type]||c.type)+' • '+new Date(c.updated_at).toLocaleString("ru-RU")+'</p></div></div>';
});
h+='</div>';
box.innerHTML=h;
}).catch(function(){ box.innerHTML='<p class="text-gray-500 text-sm">Ошибка загрузки</p>'; });
}


/* ============ FAQ ============ */
function faqGen(offerId, offerTitle){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">❓ FAQ для '+e(offerTitle)+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><div id="faq-modal-content"><p class="text-gray-500">⏳ Загрузка текущих FAQ...</p></div>');
faqLoadModal(offerId, offerTitle);
}

function faqLoadModal(offerId, offerTitle){
var box=document.getElementById('faq-modal-content');
ap('/faq?offer_id='+offerId).then(function(list){
var h='';
if(list&&list.length){
h+='<div class="space-y-3 mb-4 max-h-64 overflow-y-auto">';
list.forEach(function(f){
h+='<div class="bg-gray-50 rounded-lg p-3"><div class="flex justify-between items-start gap-2"><p class="text-sm font-semibold text-gray-900 flex-1">'+e(f.question)+'</p><div class="flex gap-1"><button onclick="faqDel('+f.id+','+offerId+',&#39;'+e(offerTitle).replace(/'/g,"")+'&#39;)" class="text-red-400 hover:text-red-600 text-xs">✕</button></div></div><p class="text-xs text-gray-600 mt-1">'+e(f.answer)+'</p><p class="text-xs text-gray-400 mt-1">'+f.generated_by+'</p></div>';
});
h+='</div>';
h+='<p class="text-sm text-gray-500 mb-4">Вопросов: '+list.length+'</p>';
}else{
h+='<p class="text-sm text-gray-500 mb-4">FAQ ещё не сгенерированы</p>';
}

h+='<div class="flex flex-wrap gap-2">';
h+='<button onclick="faqDoGen('+offerId+',&#39;'+e(offerTitle).replace(/'/g,"")+'&#39;)" id="faq-gen-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🤖 '+(list&&list.length?'Перегенерировать':'Сгенерировать')+' FAQ</button>';
h+='<a href="/offer/'+offerId+'" target="_blank" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">👁 Предпросмотр</a>';
h+='</div>';

box.innerHTML=h;
}).catch(function(){ box.innerHTML='<p class="text-red-500">Ошибка загрузки</p>'; });
}

function faqDoGen(offerId, offerTitle){
var btn=document.getElementById('faq-gen-btn');
if(btn){ btn.disabled=true; btn.textContent='⏳ Генерация...'; }

ap('/faq/generate',{method:'POST',body:JSON.stringify({offer_id:offerId})}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); if(btn){btn.disabled=false;btn.textContent='🤖 Сгенерировать FAQ';} return; }
alert('✅ Сгенерировано '+d.count+' вопросов ('+d.provider+')');
faqLoadModal(offerId, offerTitle);
}).catch(function(){ alert('Ошибка'); if(btn){btn.disabled=false;btn.textContent='🤖 Сгенерировать FAQ';} });
}

function faqDel(id, offerId, offerTitle){
if(!confirm('Удалить вопрос?')) return;
ap('/faq/delete',{method:'DELETE',body:JSON.stringify({id:id})}).then(function(d){
if(d.success) faqLoadModal(offerId, offerTitle);
else alert('Ошибка');
});
}

function faqBulkGen(){
if(!confirm('Сгенерировать FAQ для всех офферов без FAQ? Будет использован шаблон.')) return;
ap('/faq/bulk-generate',{method:'POST'}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
alert('✅ FAQ сгенерированы для '+d.generated+' офферов');
}).catch(function(){ alert('Ошибка'); });
}


/* ============ CITIES ============ */
function lCities(){
var el=document.getElementById('p-cities');
el.innerHTML='<p class="text-gray-500">⏳ Загрузка...</p>';

ap('/cities?action=list').then(function(list){
if(!Array.isArray(list)){ el.innerHTML='<p class="text-red-500">Ошибка загрузки</p>'; return; }

var h='<div class="space-y-6">';

// Заголовок
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
h+='<div><h2 class="text-xl font-bold">🏘️ Города ('+list.length+')</h2><p class="text-sm text-gray-500 mt-1">Управление списком городов для SEO-страниц</p></div>';
h+='<div class="flex gap-2"><button onclick="cityForm()" class="btn-p text-sm">+ Добавить город</button></div>';
h+='</div>';

// Поиск
h+='<div><input id="city-search" class="input-f" placeholder="🔍 Поиск города..." oninput="cityFilter()"></div>';

// Таблица
h+='<div class="bg-white rounded-xl border overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-sm" id="cities-table"><thead class="bg-gray-50"><tr>';
h+='<th class="px-4 py-3 text-left">Город</th>';
h+='<th class="px-4 py-3 text-left">Slug</th>';
h+='<th class="px-4 py-3 text-left">Регион</th>';
h+='<th class="px-4 py-3 text-left">Предложный</th>';
h+='<th class="px-4 py-3 text-right">Действия</th>';
h+='</tr></thead><tbody>';

list.forEach(function(c){
h+='<tr class="border-t hover:bg-gray-50 city-row" data-search="'+(c.name+' '+c.slug+' '+c.region+' '+c.prep).toLowerCase()+'">';
h+='<td class="px-4 py-3 font-medium">'+e(c.name)+'</td>';
h+='<td class="px-4 py-3 font-mono text-xs text-gray-500">'+e(c.slug)+'</td>';
h+='<td class="px-4 py-3 text-gray-600">'+e(c.region)+'</td>';
h+='<td class="px-4 py-3 text-gray-600">в '+e(c.prep)+'</td>';
h+='<td class="px-4 py-3 text-right space-x-2">';
h+='<button onclick="cityForm({name:&#39;'+e(c.name).replace(/'/g,'')+'&#39;,slug:&#39;'+e(c.slug)+'&#39;,region:&#39;'+e(c.region).replace(/'/g,'')+'&#39;,prep:&#39;'+e(c.prep).replace(/'/g,'')+'&#39;})" class="text-blue-600 hover:underline text-sm">Ред.</button>';
h+='<button onclick="cityDel(&#39;'+e(c.slug)+'&#39;,&#39;'+e(c.name).replace(/'/g,'')+'&#39;)" class="text-red-500 hover:underline text-sm">Уд.</button>';
h+='</td></tr>';
});

h+='</tbody></table></div></div>';
h+='</div>';
el.innerHTML=h;
}).catch(function(err){ el.innerHTML='<p class="text-red-500">Ошибка: '+err.message+'</p>'; });
}

function cityFilter(){
var q=(document.getElementById('city-search').value||'').toLowerCase();
document.querySelectorAll('.city-row').forEach(function(row){
row.style.display=(!q||row.dataset.search.indexOf(q)>=0)?'':'none';
});
}

function cityForm(c){
var isEdit=!!c;
var f=c||{name:'',slug:'',region:'',prep:''};
var oldSlug=f.slug;

var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(isEdit?'Редактировать город':'Новый город')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="space-y-3">';
h+='<div><label class="block text-xs font-medium mb-1">Название *</label><input id="city-name" class="input-f" value="'+e(f.name)+'" placeholder="Новосибирск"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Slug * (латиница, для URL)</label><input id="city-slug" class="input-f font-mono" value="'+e(f.slug)+'" placeholder="novosibirsk"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Регион</label><input id="city-region" class="input-f" value="'+e(f.region)+'" placeholder="Новосибирская область"></div>';
h+='<div><label class="block text-xs font-medium mb-1">Предложный падеж * (в ...)</label><input id="city-prep" class="input-f" value="'+e(f.prep)+'" placeholder="Новосибирске"></div>';
h+='<p class="text-xs text-gray-400">Предложный падеж используется в текстах: «Займы в <strong>Новосибирске</strong>»</p>';
h+='</div>';
h+='<div class="flex justify-end gap-3 mt-4"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="citySave('+(isEdit?"true":"false")+',&#39;'+e(oldSlug)+'&#39;)" class="btn-p">Сохранить</button></div>';
modal(h);
}

function citySave(isEdit, oldSlug){
var name=document.getElementById('city-name').value.trim();
var slug=document.getElementById('city-slug').value.trim();
var region=document.getElementById('city-region').value.trim();
var prep=document.getElementById('city-prep').value.trim();

if(!name||!slug||!prep){ alert('Заполните название, slug и предложный падеж'); return; }

var url=isEdit?'/cities?action=update':'/cities?action=add';
var body={name:name,slug:slug,region:region,prep:prep};
if(isEdit) body.old_slug=oldSlug;

ap(url,{method:isEdit?'PUT':'POST',body:JSON.stringify(body)}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
cm();
lCities();
}).catch(function(){ alert('Ошибка сохранения'); });
}

function cityDel(slug, name){
if(!confirm('Удалить город «'+name+'»? SEO-тексты для этого города останутся в БД.')) return;
ap('/cities?action=delete',{method:'POST',body:JSON.stringify({slug:slug})}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
lCities();
}).catch(function(){ alert('Ошибка'); });
}


/* ============ PAGE CHECKER ============ */
function pageCheckSample(){
var box=document.getElementById('page-check-result');
box.innerHTML='<p class="text-gray-500 text-sm mt-2">⏳ Быстрая проверка ключевых страниц...</p>';
ap('/page-checker?action=check-sample').then(function(d){
renderPageCheckResult(box, d);
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+err.message+'</p>'; });
}

function pageCheckFull(){
if(!confirm('Полная проверка может занять 1-2 минуты. Продолжить?')) return;
var box=document.getElementById('page-check-result');
box.innerHTML='<p class="text-gray-500 text-sm mt-2">⏳ Полная проверка всех страниц из sitemap... Это может занять время.</p>';
ap('/page-checker?action=check').then(function(d){
renderPageCheckResult(box, d);
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+err.message+'</p>'; });
}

function pageCheckOne(){
var url=document.getElementById('page-check-url').value.trim();
if(!url){ alert('Введите URL'); return; }
if(!url.startsWith('/')) url='/'+url;
var box=document.getElementById('page-check-result');
box.innerHTML='<p class="text-gray-500 text-sm mt-2">⏳ Проверка '+e(url)+'...</p>';
ap('/page-checker?action=check-url&url='+encodeURIComponent(url)).then(function(d){
var color=d.status===200?'green':d.status===301||d.status===302?'yellow':'red';
var h='<div class="mt-2 p-3 bg-'+color+'-50 border border-'+color+'-200 rounded-lg">';
h+='<p class="text-sm font-medium text-'+color+'-700">'+e(d.url)+' — HTTP '+d.status+'</p>';
if(d.is_redirect) h+='<p class="text-xs text-'+color+'-600 mt-1">Редирект на: '+e(d.final_url)+'</p>';
h+='</div>';
box.innerHTML=h;
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}

function renderPageCheckResult(box, d){
var h='<div class="mt-2">';
h+='<div class="flex gap-4 mb-3">';
h+='<span class="text-sm"><strong>'+d.total+'</strong> проверено</span>';
h+='<span class="text-sm text-green-600"><strong>'+d.ok+'</strong> ✅ OK</span>';
h+='<span class="text-sm '+(d.broken_count>0?'text-red-600 font-bold':'text-gray-400')+'"><strong>'+d.broken_count+'</strong> ❌ ошибок</span>';
h+='</div>';

if(d.broken_count>0){
h+='<div class="space-y-1 max-h-64 overflow-y-auto">';
d.broken.forEach(function(b){
var color=b.status===404?'red':b.status===500?'red':b.status===301||b.status===302?'yellow':'gray';
h+='<div class="flex items-center justify-between py-1.5 px-3 bg-'+color+'-50 rounded border border-'+color+'-200">';
h+='<span class="text-sm font-mono text-'+color+'-700 truncate flex-1">'+e(b.url)+'</span>';
h+='<span class="text-xs font-bold text-'+color+'-600 ml-2">HTTP '+b.status+'</span>';
h+='</div>';
});
h+='</div>';
}else{
h+='<p class="text-green-600 text-sm font-medium">✅ Все страницы доступны!</p>';
}
h+='</div>';
box.innerHTML=h;
}


function csFilterCity(){
var sel=document.getElementById('cs-city-filter');
var search=document.getElementById('cs-city-search');
var filterVal=sel?sel.value:'';
var searchVal=search?(search.value||'').toLowerCase():'';
document.querySelectorAll('.cs-row').forEach(function(row){
var city=row.dataset.city||'';
var matchFilter=!filterVal||city===filterVal;
var matchSearch=!searchVal||city.indexOf(searchVal)>=0;
row.style.display=(matchFilter&&matchSearch)?'':'none';
});
}


/* ============ GIVEAWAY ============ */
function lGiveaway(){
var el=document.getElementById('p-giveaway');
el.innerHTML='<p class="text-gray-500">⏳ Загрузка...</p>';
ap('/giveaway?action=list').then(function(list){
if(!Array.isArray(list)){ el.innerHTML='<p class="text-red-500">Ошибка</p>'; return; }
var h='<div class="space-y-6">';
h+='<div class="flex justify-between items-center"><h2 class="text-xl font-bold">🎁 Розыгрыши</h2><button onclick="gwForm()" class="btn-p text-sm">+ Создать розыгрыш</button></div>';
if(!list.length){
h+='<div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-500">Нет розыгрышей. Создайте первый!</p></div>';
}else{
list.forEach(function(g){
var stColor=g.status==='active'?'green':g.status==='finished'?'blue':g.status==='cancelled'?'red':'yellow';
var stLabel={planned:'Запланирован',active:'Активен',drawing:'Идёт розыгрыш',finished:'Завершён',cancelled:'Отменён'}[g.status]||g.status;
var prize=Number(g.prize_amount||0).toLocaleString('ru-RU');
h+='<div class="bg-white rounded-xl border p-6">';
h+='<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">';
h+='<div><h3 class="text-lg font-bold">'+e(g.title)+'</h3>';
h+='<p class="text-sm text-gray-500 mt-1">'+new Date(g.start_at).toLocaleDateString("ru-RU")+' — '+new Date(g.end_at).toLocaleDateString("ru-RU")+(g.offer_title?' • <span class="text-blue-600">'+e(g.offer_title)+'</span>':'  • <span class="text-gray-400">все офферы</span>')+'</p></div>';
h+='<span class="px-3 py-1 rounded-full text-sm font-semibold bg-'+stColor+'-100 text-'+stColor+'-700">'+stLabel+'</span></div>';
h+='<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">';
h+='<div class="bg-yellow-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-yellow-600">'+prize+' ₽</p><p class="text-xs text-gray-500">Призовой фонд</p></div>';
h+='<div class="bg-blue-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-blue-600">'+Number(g.total_conversions_amount||0).toLocaleString("ru-RU")+' ₽</p><p class="text-xs text-gray-500">Сумма конверсий</p></div>';
h+='<div class="bg-green-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-green-600">'+g.prize_percent+'%</p><p class="text-xs text-gray-500">Процент приза</p></div>';
h+='<div class="bg-purple-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-purple-600">'+(g.entries_count||0)+'</p><p class="text-xs text-gray-500">Участников</p></div>';
h+='</div>';
if(g.draw_at) h+='<p class="text-sm text-gray-600 mb-3">🎬 Розыгрыш в прямом эфире: <strong>'+new Date(g.draw_at).toLocaleString("ru-RU")+'</strong></p>';
if(g.status==='finished'&&g.winner_id){
  var wStmt=ap('/giveaway?action=entries&id='+g.id);
  h+='<div id="gw-winner-'+g.id+'" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-3"><p class="text-green-700 font-semibold">🏆 Загрузка победителя...</p></div>';
}
h+='<div class="flex flex-wrap gap-2">';
h+='<button onclick="gwEntries('+g.id+')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">👥 Участники</button>';
h+='<button onclick="gwStats('+g.id+')" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg text-sm font-semibold">📊 Статистика</button>';
h+='<a href="/api/admin/giveaway?action=export-csv&id='+g.id+'" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold inline-block">📥 CSV</a>';
h+='<button onclick="gwRecalc('+g.id+')" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg text-sm font-semibold">🔄 Пересчитать</button>';
if(g.status==='active'||g.status==='drawing') h+='<button onclick="gwDraw('+g.id+')" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">🎰 Запустить розыгрыш</button>';
h+='<button onclick="gwForm('+JSON.stringify(g).replace(/&#39;/g,"").replace(/"/g,"&quot;")+')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">✏️ Ред.</button>';
h+='<button onclick="gwDel('+g.id+')" class="text-red-500 hover:underline text-sm px-2 py-2">Удалить</button>';
h+='</div></div>';
// Загрузим победителя если есть
if(g.status==='finished'&&g.winner_id){
  setTimeout(function(){ gwLoadWinner(g.id, g.winner_id); }, 200);
}
});
}
h+='</div>';
el.innerHTML=h;
}).catch(function(err){ el.innerHTML='<p class="text-red-500">Ошибка: '+err.message+'</p>'; });
}

function gwForm(g){
var f=g||{title:'',description:'',prize_percent:10,start_at:'',end_at:'',draw_at:'',status:'planned'};
var isEdit=!!g;
var startVal=f.start_at?f.start_at.replace(' ','T').substring(0,16):'';
var endVal=f.end_at?f.end_at.replace(' ','T').substring(0,16):'';
var drawVal=f.draw_at?f.draw_at.replace(' ','T').substring(0,16):'';
var statuses='<option value="planned"'+(f.status==='planned'?' selected':'')+'>Запланирован</option><option value="active"'+(f.status==='active'?' selected':'')+'>Активен</option><option value="drawing"'+(f.status==='drawing'?' selected':'')+'>Идёт розыгрыш</option><option value="finished"'+(f.status==='finished'?' selected':'')+'>Завершён</option><option value="cancelled"'+(f.status==='cancelled'?' selected':'')+'>Отменён</option>';
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(isEdit?'Редактировать':'Новый розыгрыш')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>'+
'<div class="space-y-3">'+
'<div><label class="block text-xs font-medium mb-1">Название *</label><input id="gw-title" class="input-f" value="'+e(f.title)+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Описание (видно на плашке)</label><textarea id="gw-desc" class="input-f" rows="2">'+e(f.description||'')+'</textarea></div>'+
'<div><label class="block text-xs font-medium mb-1">Подзаголовок страницы розыгрыша</label><input id="gw-subtitle" class="input-f" value="'+e(f.page_subtitle||'Оформи и получи одобрение любого партнера на сайте и получи приз!')+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Шаги участия (каждый с новой строки)</label><textarea id="gw-steps" class="input-f" rows="4" placeholder="Зарегистрируйтесь на сайте\nОформите любой продукт\nПолучите одобрение\nЖдите розыгрыш!">'+e(f.page_steps||'')+'</textarea></div>'+
'<div><label class="block text-xs font-medium mb-1">Обязательные условия (каждое с новой строки)</label><textarea id="gw-rules" class="input-f" rows="4" placeholder="Регистрация на сайте\nОдобренная заявка через сайт">'+e(f.page_rules||'')+'</textarea></div>'+
'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Процент приза от конверсий</label><input id="gw-percent" type="number" step="0.1" min="1" max="100" class="input-f" value="'+(f.prize_percent||10)+'"></div><div><label class="block text-xs font-medium mb-1">Статус</label><select id="gw-status" class="sel-f">'+statuses+'</select></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Оффер (пусто = все офферы)</label><select id="gw-offer" class="sel-f"><option value="">Все офферы</option></select></div>'+
'<div class="grid grid-cols-3 gap-3"><div><label class="block text-xs font-medium mb-1">Начало *</label><input id="gw-start" type="datetime-local" class="input-f" value="'+startVal+'"></div><div><label class="block text-xs font-medium mb-1">Окончание *</label><input id="gw-end" type="datetime-local" class="input-f" value="'+endVal+'"></div><div><label class="block text-xs font-medium mb-1">Розыгрыш (прямой эфир)</label><input id="gw-draw" type="datetime-local" class="input-f" value="'+drawVal+'"></div></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="gwSave('+(isEdit?f.id:0)+')" class="btn-p">Сохранить</button></div>');
setTimeout(function(){ _gwLoadOffers(f.offer_id||''); }, 100);
}

function _gwLoadOffers(selectedId){
ap('/offers').then(function(list){
var sel=document.getElementById('gw-offer');
if(!sel) return;
(list||[]).forEach(function(o){
var opt=document.createElement('option');
opt.value=o.id;
opt.textContent=o.title+' ('+({microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'}[o.category]||o.category)+')';
if(selectedId && Number(o.id)===Number(selectedId)) opt.selected=true;
sel.appendChild(opt);
});
});
}

function gwSave(id){
var offerSel=document.getElementById('gw-offer');var body={title:document.getElementById('gw-title').value,description:document.getElementById('gw-desc').value,page_subtitle:document.getElementById('gw-subtitle').value,page_steps:document.getElementById('gw-steps').value,page_rules:document.getElementById('gw-rules').value,offer_id:offerSel?offerSel.value:'',
prize_percent:parseFloat(document.getElementById('gw-percent').value)||10,
start_at:document.getElementById('gw-start').value.replace('T',' '),
end_at:document.getElementById('gw-end').value.replace('T',' '),
draw_at:document.getElementById('gw-draw').value?document.getElementById('gw-draw').value.replace('T',' '):'',
status:document.getElementById('gw-status').value};
if(!body.title||!body.start_at||!body.end_at){ alert('Заполните название и даты'); return; }
if(id) body.id=id;
ap('/giveaway?action='+(id?'update':'create'),{method:'POST',body:JSON.stringify(body)}).then(function(d){
if(d.error){ alert(d.error); return; } cm(); lGiveaway();
});
}

function gwDel(id){ if(!confirm('Удалить розыгрыш и всех участников?')) return;
ap('/giveaway?action=delete',{method:'POST',body:JSON.stringify({id:id})}).then(function(){ lGiveaway(); }); }

function gwRecalc(id){
ap('/giveaway?action=recalc',{method:'POST',body:JSON.stringify({id:id})}).then(function(d){
if(d.error){ alert(d.error); return; }
alert('Пересчитано!\nОбщая сумма конверсий: '+Number(d.total_amount).toLocaleString('ru-RU')+' ₽\nПризовой фонд: '+Number(d.prize_amount).toLocaleString('ru-RU')+' ₽\nУчастников добавлено: '+d.entries);
lGiveaway();
});
}

function gwEntries(id){
ap('/giveaway?action=entries&id='+id).then(function(list){
if(!list||!list.length){ alert('Нет участников'); return; }
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">👥 Участники ('+list.length+')</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
h+='<div class="overflow-x-auto max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr><th class="p-2 text-left">Имя</th><th class="p-2 text-left">Email</th><th class="p-2 text-left">Оффер</th><th class="p-2 text-right">Сумма</th><th class="p-2 text-left">IP</th><th class="p-2 text-left">Дата</th></tr></thead><tbody>';
list.forEach(function(en){
h+='<tr class="border-t"><td class="p-2 font-medium">'+e(en.user_name)+'</td><td class="p-2 text-gray-500 font-mono text-xs">'+e(en.user_email_masked)+'</td><td class="p-2 text-xs">'+e(en.offer_title||'—')+'</td><td class="p-2 text-right text-green-600">'+Number(en.payout||0).toLocaleString('ru-RU')+' ₽</td><td class="p-2 font-mono text-xs text-gray-400">'+e(en.ip_masked)+'</td><td class="p-2 text-xs text-gray-400">'+new Date(en.created_at).toLocaleDateString('ru-RU')+'</td></tr>';
});
h+='</tbody></table></div>';
modal(h);
});
}

function gwDraw(id){
if(!confirm('🎰 Запустить розыгрыш? Будет случайно выбран победитель из участников. Это действие нельзя отменить!')) return;
ap('/giveaway?action=draw',{method:'POST',body:JSON.stringify({id:id})}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
var h='<div class="text-center py-8"><span class="text-6xl block mb-4">🏆</span>';
h+='<h2 class="text-2xl font-bold text-gray-900 mb-2">Победитель определён!</h2>';
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mt-4 max-w-md mx-auto">';
h+='<p class="text-xl font-bold text-gray-900">'+e(d.winner.name)+'</p>';
h+='<p class="text-sm text-gray-500 mt-1">'+e(d.winner.email_masked)+'</p>';
h+='<p class="text-sm text-gray-500 mt-1">Оффер: '+e(d.winner.offer)+'</p>';
h+='<p class="text-2xl font-bold text-green-600 mt-3">'+Number(d.prize_amount).toLocaleString('ru-RU')+' ₽</p>';
h+='<p class="text-xs text-gray-400 mt-2">Участников: '+d.total_entries+'</p>';
h+='</div><button onclick="cm();lGiveaway();" class="btn-p mt-6">Закрыть</button></div>';
modal(h);
});
}

function gwLoadWinner(gwId, winnerId){
ap('/giveaway?action=entries&id='+gwId).then(function(list){
var box=document.getElementById('gw-winner-'+gwId);
if(!box) return;
var w=list.find(function(en){ return en.id===winnerId; });
if(w){
box.innerHTML='<p class="text-green-700 font-semibold">🏆 Победитель: <strong>'+e(w.user_name)+'</strong> ('+e(w.user_email_masked)+') — оффер: '+e(w.offer_title||'—')+'</p>';
}else{
box.innerHTML='<p class="text-green-700 font-semibold">🏆 Победитель определён (ID: '+winnerId+')</p>';
}
});
}


/* ============ GOOGLE INDEXING ============ */
function gIdxLoadStatus(){
ap('/google-indexing?action=status').then(function(d){
var box=document.getElementById('google-idx-status');
if(!box) return;
var h='';
if(!d.available){
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-3">';
h+='<p class="text-sm text-yellow-700 font-medium">⚠️ Сервисный аккаунт Google не настроен</p>';
h+='<p class="text-xs text-yellow-600 mt-1">Загрузите JSON-ключ сервисного аккаунта из Google Cloud Console</p>';
h+='<div class="mt-3"><textarea id="gidx-key" class="input-f text-xs font-mono" rows="4" placeholder="Вставьте содержимое JSON-файла сервисного аккаунта..."></textarea>';
h+='<button onclick="gIdxUploadKey()" class="btn-p text-sm mt-2">📤 Загрузить ключ</button></div>';
h+='</div>';
}else{
h+='<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">';
h+='<p class="text-sm text-green-700">✅ Подключено: <span class="font-mono text-xs">'+e(d.service_account)+'</span></p>';
h+='</div>';
h+='<div class="flex flex-wrap gap-2 mb-3">';
h+='<button onclick="gIdxTest()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">🔑 Тест авторизации</button>';
h+='<button onclick="gIdxSubmitNew()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🚀 Отправить новые URL</button>';
h+='<div class="flex items-center gap-2"><input id="gidx-url" class="input-f text-sm" placeholder="/karty/kreditnye/moskva" style="width:240px"><button onclick="gIdxSubmitOne()" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg text-sm font-semibold">Отправить</button></div>';
h+='</div>';
h+='<div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500"><strong>Лимит:</strong> ~200 URL в день. Отправляйте только новые и важные страницы.</div>';
}
h+='<div id="gidx-result" class="mt-3"></div>';
box.innerHTML=h;
}).catch(function(){ var b=document.getElementById('google-idx-status'); if(b) b.innerHTML='<p class="text-red-500 text-sm">Ошибка загрузки</p>'; });
}

function gIdxUploadKey(){
var key=(document.getElementById('gidx-key')?.value||'').trim();
if(!key){ alert('Вставьте JSON-ключ'); return; }
ap('/google-indexing?action=upload-key',{method:'POST',body:JSON.stringify({key:key})}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
alert('✅ Ключ загружен: '+d.email);
gIdxLoadStatus();
}).catch(function(){ alert('Ошибка'); });
}

function gIdxTest(){
var box=document.getElementById('gidx-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Тестирование...</p>';
ap('/google-indexing?action=test').then(function(d){
box.innerHTML=d.success?'<p class="text-green-600 text-sm">✅ Авторизация успешна! Можно отправлять URL.</p>':'<p class="text-red-500 text-sm">❌ Ошибка авторизации. Проверьте ключ и настройки.</p>';
});
}

function gIdxSubmitNew(){
if(!confirm('Отправить все новые/обновлённые URL в Google Indexing API? (макс. 50)')) return;
var box=document.getElementById('gidx-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Отправка URL в Google...</p>';
ap('/google-indexing?action=submit-new',{method:'POST'}).then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+e(d.error)+'</p>'; return; }
if(d.total===0){ box.innerHTML='<p class="text-gray-500 text-sm">Нет URL для отправки — все уже отправлены.</p>'; return; }
var h='<div class="bg-blue-50 rounded-lg p-3"><p class="text-sm font-medium text-blue-700">Отправлено: '+d.success+' из '+d.total+' (ошибок: '+d.failed+')</p>';
if(d.results&&d.results.length){
h+='<div class="mt-2 max-h-40 overflow-y-auto space-y-1">';
d.results.forEach(function(r){
h+='<div class="text-xs flex items-center gap-2">'+(r.success?'<span class="text-green-600">✅</span>':'<span class="text-red-500">❌ '+r.status+'</span>')+'<span class="font-mono truncate">'+e(r.url)+'</span></div>';
});
h+='</div>';
}
h+='</div>';
box.innerHTML=h;
lIndexing();
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка отправки</p>'; });
}

function gIdxSubmitOne(){
var url=(document.getElementById('gidx-url')?.value||'').trim();
if(!url){ alert('Введите URL'); return; }
if(!url.startsWith('/')) url='/'+url;
var box=document.getElementById('gidx-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Отправка '+e(url)+'...</p>';
ap('/google-indexing?action=submit',{method:'POST',body:JSON.stringify({urls:[url]})}).then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+e(d.error)+'</p>'; return; }
var r=d.results&&d.results[0];
box.innerHTML=r&&r.success?'<p class="text-green-600 text-sm">✅ '+e(r.url)+' — отправлено в Google!</p>':'<p class="text-red-500 text-sm">❌ Ошибка '+(r?r.status:'')+'</p>';
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}



/* ============ YANDEX WEBMASTER ============ */
function ywmLoadStatus(){
ap('/yandex-webmaster?action=status').then(function(d){
var box=document.getElementById('yandex-wm-status');
if(!box) return;
var h='';
if(!d.available){
h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-3">';
h+='<p class="text-sm text-yellow-700 font-medium">⚠️ Яндекс.Вебмастер не настроен</p>';
h+='<p class="text-xs text-yellow-600 mt-1">Сохраните OAuth-токен, user_id и host_id</p>';
h+='<div class="grid gap-2 mt-3">';
h+='<input id="ywm-client-id" class="input-f text-xs font-mono" placeholder="Client ID (необязательно)">';
h+='<input id="ywm-user-id" class="input-f text-xs font-mono" placeholder="User ID, например 1193116462">';
h+='<input id="ywm-host-id" class="input-f text-xs font-mono" placeholder="Host ID, например https:kosmozaim.ru:443">';
h+='<textarea id="ywm-token" class="input-f text-xs font-mono" rows="3" placeholder="OAuth токен Яндекс.Вебмастера..."></textarea>';
h+='<button onclick="ywmSaveConfig()" class="btn-p text-sm">💾 Сохранить настройки</button>';
h+='</div></div>';
}else{
h+='<div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-3">';
h+='<p class="text-sm text-green-700">✅ Подключено: user_id <span class="font-mono">'+e(String(d.user_id||''))+'</span></p>';
h+='<p class="text-xs text-green-600 mt-1">host_id: <span class="font-mono">'+e(d.host_id||'')+'</span></p>';
h+='</div>';
h+='<div class="flex flex-wrap gap-2 mb-3">';
h+='<button onclick="ywmTest()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">🔑 Тест авторизации</button>';
h+='<button onclick="ywmSubmitNew()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🚀 Переобход новых URL</button>';
h+='<div class="flex items-center gap-2"><input id="ywm-url" class="input-f text-sm" placeholder="/karty/kreditnye/moskva" style="width:240px"><button onclick="ywmSubmitOne()" class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded-lg text-sm font-semibold">Отправить</button></div>';
h+='</div>';
h+='<div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-500"><strong>Важно:</strong> Яндекс позволяет ставить URL в очередь переобхода. Используйте для новых и обновлённых страниц.</div>';
}
h+='<div id="ywm-result" class="mt-3"></div>';
box.innerHTML=h;
}).catch(function(){ var b=document.getElementById('yandex-wm-status'); if(b) b.innerHTML='<p class="text-red-500 text-sm">Ошибка загрузки</p>'; });
}

function ywmSaveConfig(){
var body={
client_id:(document.getElementById('ywm-client-id')?.value||'').trim(),
user_id:(document.getElementById('ywm-user-id')?.value||'').trim(),
host_id:(document.getElementById('ywm-host-id')?.value||'').trim(),
oauth_token:(document.getElementById('ywm-token')?.value||'').trim()
};
if(!body.oauth_token||!body.user_id||!body.host_id){ alert('Заполните OAuth токен, user_id и host_id'); return; }
ap('/yandex-webmaster?action=save-config',{method:'POST',body:JSON.stringify(body)}).then(function(d){
if(d.error){ alert('Ошибка: '+d.error); return; }
alert('✅ Настройки Яндекс.Вебмастера сохранены');
ywmLoadStatus();
}).catch(function(){ alert('Ошибка'); });
}

function ywmTest(){
var box=document.getElementById('ywm-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Тестирование...</p>';
ap('/yandex-webmaster?action=test').then(function(d){
if(d.success){
  var hosts=(d.data&&d.data.hosts)?d.data.hosts.length:0;
  box.innerHTML='<p class="text-green-600 text-sm">✅ Авторизация успешна! Найдено хостов: '+hosts+'</p>';
}else{
  box.innerHTML='<p class="text-red-500 text-sm">❌ Ошибка авторизации'+(d.status?' ('+d.status+')':'')+'</p>';
}
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}

function ywmSubmitNew(){
if(!confirm('Отправить новые/обновлённые URL в Яндекс.Вебмастер? (макс. 20)')) return;
var box=document.getElementById('ywm-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Отправка URL в Яндекс...</p>';
ap('/yandex-webmaster?action=submit-new',{method:'POST'}).then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+e(d.error)+'</p>'; return; }
if(d.total===0){ box.innerHTML='<p class="text-gray-500 text-sm">Нет URL для отправки.</p>'; return; }
var h='<div class="bg-red-50 rounded-lg p-3"><p class="text-sm font-medium text-red-700">Отправлено: '+d.success+' из '+d.total+' (ошибок: '+d.failed+')</p>';
if(d.results&&d.results.length){
h+='<div class="mt-2 max-h-40 overflow-y-auto space-y-1">';
d.results.forEach(function(r){
h+='<div class="text-xs flex items-center gap-2">'+(r.success?'<span class="text-green-600">✅</span>':'<span class="text-red-500">❌ '+r.status+'</span>')+'<span class="font-mono truncate">'+e(r.url)+'</span></div>';
});
h+='</div>';
}
h+='</div>';
box.innerHTML=h;
lIndexing();
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка отправки</p>'; });
}

function ywmSubmitOne(){
var url=(document.getElementById('ywm-url')?.value||'').trim();
if(!url){ alert('Введите URL'); return; }
if(!url.startsWith('/')) url='/'+url;
var box=document.getElementById('ywm-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Отправка '+e(url)+'...</p>';
ap('/yandex-webmaster?action=submit',{method:'POST',body:JSON.stringify({urls:[url]})}).then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+e(d.error)+'</p>'; return; }
var r=d.results&&d.results[0];
box.innerHTML=r&&r.success?'<p class="text-green-600 text-sm">✅ '+e(r.url)+' — поставлен в очередь переобхода!</p>':'<p class="text-red-500 text-sm">❌ Ошибка '+(r?r.status:'')+'</p>';
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}


/* ============ POSITIONS ============ */
var _posDays=7;
var _posQuery='';
function lPositions(){
var el=document.getElementById('p-positions');
el.innerHTML='<p class="text-gray-500">⏳ Загрузка позиций...</p>';

var params='days='+_posDays+'&limit=100';
if(_posQuery) params+='&query='+encodeURIComponent(_posQuery);

ap('/positions?action=combined&'+params).then(function(d){
var h='<div class="space-y-6">';
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
h+='<h2 class="text-xl font-bold">📊 Позиции в поиске</h2>';
h+='<div class="flex flex-wrap gap-2 items-center">';
h+='<select id="pos-days" onchange="_posDays=Number(this.value);lPositions()" class="sel-f text-sm w-auto">';
[7,14,28,30,60,90].forEach(function(v){ h+='<option value="'+v+'"'+(_posDays===v?' selected':'')+'>'+v+' дней</option>'; });
h+='</select>';
h+='<input id="pos-query" class="input-f text-sm" placeholder="🔍 Фильтр по запросу..." value="'+e(_posQuery)+'" style="width:220px">';
h+='<button onclick="_posQuery=document.getElementById(&#39;pos-query&#39;).value;lPositions()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold">Найти</button>';
h+='</div></div>';

// Яндекс
h+='<div class="bg-white rounded-xl border p-4">';
h+='<h3 class="font-bold text-gray-900 mb-3">🔴 Яндекс <span class="text-xs text-gray-400 font-normal">'+d.days+' дней</span></h3>';
if(!d.yandex){ h+='<p class="text-yellow-600 text-sm">⚠️ Яндекс.Вебмастер не настроен</p>'; }
else if(!d.yandex.length){ h+='<p class="text-gray-500 text-sm">Нет данных за выбранный период</p>'; }
else{
h+='<div class="overflow-x-auto max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Запрос</th>';
h+='<th class="px-3 py-2 text-right">Позиция</th>';
h+='<th class="px-3 py-2 text-right">Показы</th>';
h+='<th class="px-3 py-2 text-right">Клики</th>';
h+='<th class="px-3 py-2 text-right">CTR</th>';
h+='</tr></thead><tbody>';
d.yandex.forEach(function(q){
var posColor=q.position<=3?'text-green-600 font-bold':q.position<=10?'text-blue-600 font-medium':q.position<=30?'text-yellow-600':'text-gray-500';
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 max-w-xs truncate" title="'+e(q.query)+'">'+e(q.query)+'</td>';
h+='<td class="px-3 py-2 text-right '+posColor+'">'+q.position+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+q.shows+'</td>';
h+='<td class="px-3 py-2 text-right font-medium">'+q.clicks+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-500">'+q.ctr+'%</td>';
h+='</tr>';
});
h+='</tbody></table></div>';
h+='<p class="text-xs text-gray-400 mt-2">Всего запросов: '+d.yandex.length+'</p>';
}
h+='</div>';

// Google
h+='<div class="bg-white rounded-xl border p-4">';
h+='<h3 class="font-bold text-gray-900 mb-3">🔵 Google <span class="text-xs text-gray-400 font-normal">'+d.days+' дней</span></h3>';
if(!d.google){ h+='<p class="text-yellow-600 text-sm">⚠️ Google Search Console не настроен</p>'; }
else if(!d.google.length){ h+='<p class="text-gray-500 text-sm">Нет данных за выбранный период</p>'; }
else{
h+='<div class="overflow-x-auto max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Запрос</th>';
h+='<th class="px-3 py-2 text-right">Позиция</th>';
h+='<th class="px-3 py-2 text-right">Показы</th>';
h+='<th class="px-3 py-2 text-right">Клики</th>';
h+='<th class="px-3 py-2 text-right">CTR</th>';
h+='</tr></thead><tbody>';
d.google.forEach(function(q){
var posColor=q.position<=3?'text-green-600 font-bold':q.position<=10?'text-blue-600 font-medium':q.position<=30?'text-yellow-600':'text-gray-500';
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 max-w-xs truncate" title="'+e(q.query)+'">'+e(q.query)+'</td>';
h+='<td class="px-3 py-2 text-right '+posColor+'">'+q.position+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+q.shows+'</td>';
h+='<td class="px-3 py-2 text-right font-medium">'+q.clicks+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-500">'+q.ctr+'%</td>';
h+='</tr>';
});
h+='</tbody></table></div>';
h+='<p class="text-xs text-gray-400 mt-2">Всего запросов: '+d.google.length+'</p>';
}
h+='</div>';

h+='</div>';
el.innerHTML=h;
}).catch(function(err){ el.innerHTML='<p class="text-red-500">Ошибка: '+err.message+'</p>'; });
}


/* ============ SYSTEM MONITOR ============ */
function lMonitor(){
var el=document.getElementById('p-monitor');
el.innerHTML='<p class="text-gray-500">⏳ Загрузка мониторинга...</p>';
ap('/system-monitor?action=overview').then(function(d){
var h='<div class="space-y-6">';
h+='<h2 class="text-xl font-bold">🖥️ Системный мониторинг</h2>';

// Почта
var ml=d.mail||{};
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">📧 Почта</h3>';
h+='<div class="grid sm:grid-cols-2 gap-3">';
h+='<div class="p-3 rounded-lg '+(ml.smtp_enabled?'bg-green-50':'bg-yellow-50')+'"><p class="text-sm font-medium">'+(ml.smtp_enabled?'✅ SMTP':'⚠️ mail()')+'</p><p class="text-xs text-gray-500">'+(ml.smtp_host||'Стандартная отправка')+'</p></div>';
h+='<div class="p-3 rounded-lg bg-gray-50"><p class="text-xs text-gray-500">От: <strong>'+e(ml.mail_from||'—')+'</strong></p><p class="text-xs text-gray-500">Обратная связь: <strong>'+e(ml.contact_email||'не указан')+'</strong></p></div>';
h+='</div>';
if(!ml.smtp_enabled) h+='<p class="text-xs text-yellow-600 mt-2">💡 Рекомендуем настроить SMTP в Настройках, чтобы письма не попадали в спам</p>';
h+='</div>';

// Сервисы
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔌 Внешние сервисы</h3>';
h+='<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">';
var svc=d.services||{};
h+='<div class="p-3 rounded-lg '+(svc.yandex_gpt?'bg-green-50':'bg-red-50')+'"><span class="text-lg">'+(svc.yandex_gpt?'✅':'❌')+'</span><p class="text-sm font-medium mt-1">YandexGPT</p></div>';
h+='<div class="p-3 rounded-lg '+(svc.google_indexing?'bg-green-50':'bg-yellow-50')+'"><span class="text-lg">'+(svc.google_indexing?'✅':'⚠️')+'</span><p class="text-sm font-medium mt-1">Google Indexing</p></div>';
h+='<div class="p-3 rounded-lg '+(svc.yandex_webmaster?'bg-green-50':'bg-yellow-50')+'"><span class="text-lg">'+(svc.yandex_webmaster?'✅':'⚠️')+'</span><p class="text-sm font-medium mt-1">Яндекс.Вебмастер</p></div>';
h+='</div>';
h+='<div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">ℹ️ Google sitemap ping намеренно отключён: Google официально прекратил поддержку <code>/ping?sitemap=</code>. Для Google в проекте используются Search Console и Indexing API.</div>';
h+='</div>';

// SEO-интеграции
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔎 SEO-интеграции</h3>';
h+='<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">';
h+='<div class="p-3 rounded-lg '+(svc.google_search_console?'bg-green-50':'bg-yellow-50')+'"><span class="text-lg">'+(svc.google_search_console?'✅':'⚠️')+'</span><p class="text-sm font-medium mt-1">Google Search Console</p></div>';
h+='<div class="p-3 rounded-lg '+(svc.google_indexing?'bg-green-50':'bg-yellow-50')+'"><span class="text-lg">'+(svc.google_indexing?'✅':'⚠️')+'</span><p class="text-sm font-medium mt-1">Google Indexing API</p></div>';
h+='<div class="p-3 rounded-lg '+(svc.yandex_webmaster?'bg-green-50':'bg-yellow-50')+'"><span class="text-lg">'+(svc.yandex_webmaster?'✅':'⚠️')+'</span><p class="text-sm font-medium mt-1">Яндекс.Вебмастер API</p></div>';
h+='<div class="p-3 rounded-lg '+(svc.yandex_gpt?'bg-green-50':'bg-red-50')+'"><span class="text-lg">'+(svc.yandex_gpt?'✅':'❌')+'</span><p class="text-sm font-medium mt-1">YandexGPT / YandexART</p></div>';
h+='<div class="p-3 rounded-lg bg-gray-50 lg:col-span-2"><p class="text-xs text-gray-500">Провайдер картинок статей</p><p class="text-sm font-semibold text-gray-900 mt-1">'+e((svc.article_image_provider||'yandex').toString())+'</p><p class="text-[11px] text-gray-400 mt-1">Промпт: '+e((svc.article_image_prompt_template||'').toString())+'</p></div>';
h+='</div>';
h+='<div class="mt-3 bg-white border border-gray-200 rounded-lg p-3"><div class="flex items-center justify-between gap-3 mb-2"><h4 class="font-semibold text-sm text-gray-800">🖼️ Последние генерации картинок</h4><span class="text-[11px] text-gray-400">Настройки AI → вкладка AI провайдеры</span></div>';
if(svc.article_image_recent&&svc.article_image_recent.length){
  h+='<div class="space-y-2 max-h-56 overflow-y-auto">';
  svc.article_image_recent.forEach(function(it){
    h+='<div class="rounded-lg '+(it.success?'bg-green-50 border border-green-200':'bg-red-50 border border-red-200')+' p-2 text-xs">';
    h+='<div class="flex flex-wrap items-center gap-2"><span class="font-medium">'+e(it.time||'')+'</span><span>запрошен: <strong>'+e(it.requested_provider||'')+'</strong></span><span>факт: <strong>'+e(it.actual_provider||'—')+'</strong></span></div>';
    h+='<div class="mt-1 text-gray-600">prompt: '+e(it.prompt||'')+'</div>';
    if(it.error){ h+='<div class="mt-1 text-red-600 whitespace-pre-wrap">'+e(it.error)+'</div>'; }
    h+='</div>';
  });
  h+='</div>';
}else{
  h+='<p class="text-sm text-gray-400">Пока нет записей. Сначала сгенерируйте статью или протестируйте провайдера картинок.</p>';
}
h+='</div>';
h+='</div>';

// Безопасность
var sec=d.security||{};
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🔒 Безопасность</h3>';
h+='<p class="text-sm">2FA: '+(sec['2fa_enabled']?'<span class="text-green-600 font-semibold">✅ Включена</span>':'<span class="text-red-600 font-semibold">❌ Выключена</span>')+'</p>';
h+='</div>';

// Планировщик
var sch=d.scheduler||{};
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">⏰ Планировщик</h3>';
h+='<div class="grid sm:grid-cols-2 gap-4 mb-3">';
h+='<div class="p-3 rounded-lg '+(sch.reviews_enabled?'bg-green-50':'bg-gray-50')+'"><p class="text-sm font-medium">Отзывы: '+(sch.reviews_enabled?'<span class="text-green-600">вкл</span>':'<span class="text-gray-400">выкл</span>')+'</p><p class="text-xs text-gray-500">Сегодня: '+sch.reviews_today+' / '+sch.reviews_target+'</p>'+(sch.last_review?'<p class="text-xs text-gray-400">Последний: '+sch.last_review+'</p>':'')+'</div>';
h+='<div class="p-3 rounded-lg '+(sch.articles_enabled?'bg-green-50':'bg-gray-50')+'"><p class="text-sm font-medium">Статьи: '+(sch.articles_enabled?'<span class="text-green-600">вкл</span>':'<span class="text-gray-400">выкл</span>')+'</p><p class="text-xs text-gray-500">Сегодня: '+sch.articles_today+' / '+sch.articles_target+'</p>'+(sch.last_article?'<p class="text-xs text-gray-400">Последняя: '+sch.last_article+'</p>':'')+'</div>';
h+='</div>';
if(sch.recent_log&&sch.recent_log.length){
h+='<details class="mt-2"><summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">📋 Лог запусков (последние 10)</summary><div class="mt-2 bg-gray-50 rounded-lg p-3 max-h-40 overflow-y-auto font-mono text-xs text-gray-600">';
sch.recent_log.forEach(function(l){ h+='<div>'+e(l)+'</div>'; });
h+='</div></details>';
}
h+='</div>';

// Бэкапы
var bk=d.backups||{};
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">💾 Бэкапы</h3>';
h+='<div class="flex gap-4">';
h+='<div><p class="text-sm">Всего: <strong>'+bk.count+'</strong></p></div>';
h+='<div><p class="text-sm">Последний: <strong>'+(bk.last_backup||'никогда')+'</strong></p></div>';
if(bk.days_since_backup!==null){
h+='<div><p class="text-sm '+(bk.days_since_backup>7?'text-red-600':'text-green-600')+'">'+bk.days_since_backup+' дней назад</p></div>';
}
h+='</div>';
if(bk.days_since_backup===null||bk.days_since_backup>3){
h+='<p class="text-xs text-yellow-600 mt-2">⚠️ Рекомендуется делать бэкап не реже 1 раза в 3 дня</p>';
}
h+='</div>';

// БД и PHP
var dbInfo=d.database||{};
var phpInfo=d.php||{};
h+='<div class="grid sm:grid-cols-2 gap-6">';
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">🗄️ База данных</h3>';
h+='<p class="text-sm">Размер: <strong>'+(dbInfo.size_mb||0)+' МБ</strong></p>';
h+='<p class="text-sm">Таблиц: <strong>'+(dbInfo.tables||0)+'</strong></p>';
h+='</div>';
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">⚙️ PHP</h3>';
h+='<p class="text-sm">Версия: <strong>'+(phpInfo.version||'?')+'</strong></p>';
h+='<p class="text-sm">Память: <strong>'+(phpInfo.memory_limit||'?')+'</strong></p>';
h+='<p class="text-sm">cURL: '+(phpInfo.curl?'✅':'❌')+' | OpenSSL: '+(phpInfo.openssl?'✅':'❌')+' | mbstring: '+(phpInfo.mbstring?'✅':'❌')+'</p>';
h+='</div></div>';

// Кэш
var cache=d.cache||{};
h+='<div class="bg-white rounded-xl border p-4"><h3 class="font-bold text-gray-900 mb-3">📦 Кэш</h3>';
h+='<p class="text-sm">Файлов: <strong>'+cache.page_cache_files+'</strong> | Размер: <strong>'+Math.round((cache.page_cache_size||0)/1024)+' КБ</strong></p>';
h+='</div>';

// Ошибки
var errors=d.recent_errors||[];
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b flex justify-between items-center"><h3 class="font-bold text-gray-900">🐛 Последние ошибки ('+(d.error_count||0)+')</h3><button onclick="monClearErrors()" class="text-xs text-gray-400 hover:text-red-500">Очистить</button></div>';
if(errors.length){
h+='<div class="divide-y max-h-64 overflow-y-auto">';
errors.forEach(function(er){
var color=er.level==='critical'?'red':er.level==='error'?'red':er.level==='warning'?'yellow':'gray';
h+='<div class="px-4 py-2 text-xs"><div class="flex items-center gap-2"><span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-'+color+'-100 text-'+color+'-700">'+e(er.level)+'</span><span class="text-gray-400">'+e(er.time)+'</span><span class="text-gray-500 font-medium">'+e(er.source)+'</span></div><p class="text-gray-700 mt-1">'+e(er.message)+'</p></div>';
});
h+='</div>';
}else{
h+='<p class="p-4 text-gray-500 text-sm">Нет ошибок ✅</p>';
}
h+='</div>';

h+='</div>';
el.innerHTML=h;
}).catch(function(err){ el.innerHTML='<p class="text-red-500">Ошибка: '+err.message+'</p>'; });
}

function monClearErrors(){
if(!confirm('Очистить лог ошибок?')) return;
ap('/system-monitor?action=clear-errors',{method:'POST'}).then(function(d){
if(d.success) alert('Очищено: '+d.cleared);
lMonitor();
});
}



function testImageProvider(provider){
var box=document.getElementById('image-provider-test-result');
if(box) box.innerHTML='<p class="text-gray-500 text-sm">⏳ Тест провайдера '+provider+'...</p>';
var title='тест картинки';
fetch(A+'/image-provider-test',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({provider:provider,title:title})})
.then(r=>r.json()).then(function(d){
  if(!box) return;
  if(d.success){
    box.innerHTML='<div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700">✅ '+(d.provider||provider)+' работает корректно</div>';
  } else {
    box.innerHTML='<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700"><strong>❌ '+(d.provider||provider)+' не сработал</strong><div class="mt-1 text-xs whitespace-pre-wrap">'+e(d.error||'Неизвестная ошибка')+'</div></div>';
  }
}).catch(function(){ if(box) box.innerHTML='<div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">❌ Ошибка запроса</div>'; });
}

function testMail(){
var box=document.getElementById('smtp-test-result');
var email=document.getElementById('set-contact-email').value||document.getElementById('set-smtp-user').value||document.getElementById('set-mail-from').value;
if(!email){ email=prompt('На какой email отправить тестовое письмо?'); }
if(!email) return;
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Отправка тестового письма на '+e(email)+'...</p>';
ap('/test-mail',{method:'POST',body:JSON.stringify({email:email})}).then(function(d){
if(d.ok) box.innerHTML='<p class="text-green-600 text-sm">✅ Письмо отправлено через <strong>'+e(d.method||'?')+'</strong>. Проверьте почту!</p>';
else box.innerHTML='<p class="text-red-500 text-sm">❌ Ошибка: '+e(d.error||'неизвестная')+(d.smtp_error?' (SMTP: '+e(d.smtp_error)+')':'')+'</p>';
}).catch(function(){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка отправки</p>'; });
}


function gwStats(id){
ap('/giveaway?action=stats&id='+id).then(function(d){
if(d.error){ alert(d.error); return; }
var g=d.giveaway||{};
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📊 Статистика: '+e(g.title||'')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';

// Общие показатели
h+='<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">';
h+='<div class="bg-purple-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-purple-600">'+d.unique_users+'</p><p class="text-xs text-gray-500">Уникальных</p></div>';
h+='<div class="bg-yellow-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-yellow-600">'+Number(g.prize_amount||0).toLocaleString("ru-RU")+' ₽</p><p class="text-xs text-gray-500">Призовой фонд</p></div>';
h+='<div class="bg-blue-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-blue-600">'+Number(g.total_conversions_amount||0).toLocaleString("ru-RU")+' ₽</p><p class="text-xs text-gray-500">Конверсии</p></div>';
h+='<div class="bg-green-50 rounded-lg p-3 text-center"><p class="text-xl font-bold text-green-600">'+g.prize_percent+'%</p><p class="text-xs text-gray-500">Процент</p></div>';
h+='</div>';

// По офферам
if(d.by_offer&&d.by_offer.length){
h+='<div class="mb-4"><h4 class="font-semibold text-sm text-gray-700 mb-2">По офферам</h4>';
h+='<div class="space-y-1">';
d.by_offer.forEach(function(o){
h+='<div class="flex justify-between bg-gray-50 rounded px-3 py-1.5 text-sm"><span>'+e(o.offer_title||'—')+'</span><span><strong>'+o.cnt+'</strong> участников • '+Number(o.total_payout||0).toLocaleString("ru-RU")+' ₽</span></div>';
});
h+='</div></div>';
}

// По дням
if(d.by_day&&d.by_day.length){
h+='<div class="mb-4"><h4 class="font-semibold text-sm text-gray-700 mb-2">По дням</h4>';
h+='<div class="space-y-1">';
d.by_day.forEach(function(day){
h+='<div class="flex justify-between bg-gray-50 rounded px-3 py-1.5 text-sm"><span>'+day.dt+'</span><span><strong>'+day.cnt+'</strong> участников</span></div>';
});
h+='</div></div>';
}

// Дубликаты
if(d.duplicates&&d.duplicates.length){
h+='<div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3"><h4 class="font-semibold text-sm text-yellow-700 mb-2">⚠️ Пользователи с несколькими заявками</h4>';
h+='<div class="space-y-1">';
d.duplicates.forEach(function(dup){
h+='<div class="text-xs text-yellow-700">'+e(dup.user_name)+' ('+e(dup.user_email)+') — <strong>'+dup.entries_count+' заявок</strong></div>';
});
h+='</div></div>';
}

// Подозрительные IP
if(d.suspicious_ips&&d.suspicious_ips.length){
h+='<div class="bg-red-50 border border-red-200 rounded-lg p-3"><h4 class="font-semibold text-sm text-red-700 mb-2">🚨 Подозрительные IP (разные пользователи)</h4>';
h+='<div class="space-y-1">';
d.suspicious_ips.forEach(function(s){
h+='<div class="text-xs text-red-700">IP '+e(s.ip)+' — <strong>'+s.user_count+' пользователей</strong>: '+e(s.names)+'</div>';
});
h+='</div></div>';
}

modal(h);
});
}


/* ============ SEO AUDIT ============ */

function seoAuditRunFix(action){
if(action==='faq_bulk_generate'){
  if(!confirm('Сгенерировать FAQ для всех офферов без FAQ?')) return;
  ap('/faq/bulk-generate',{method:'POST'}).then(function(d){
    if(d.error){ alert('Ошибка: '+d.error); return; }
    alert('✅ FAQ сгенерированы для '+(d.generated||0)+' офферов');
    runSeoAudit();
  }).catch(function(){ alert('Ошибка генерации FAQ'); });
  return;
}
alert('Неизвестное действие');
}

function runSeoAudit(){
var box=document.getElementById('seo-audit-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Анализ SEO...</p>';
ap('/seo-audit').then(function(d){
var h='';

// Score
var scoreColor=d.score>=80?'green':d.score>=60?'yellow':'red';
h+='<div class="flex items-center gap-4 mb-4">';
h+='<div class="w-16 h-16 rounded-full border-4 border-'+scoreColor+'-500 flex items-center justify-center"><span class="text-xl font-bold text-'+scoreColor+'-600">'+d.score+'</span></div>';
h+='<div><p class="font-bold text-gray-900">SEO Score: '+d.score+'/100</p>';
h+='<p class="text-xs text-gray-500">Ошибок: '+d.errors+' • Предупреждений: '+d.warnings+' • Инфо: '+d.info+'</p></div>';
h+='</div>';

// Issues
var levels={error:'❌',warning:'⚠️',info:'ℹ️',ok:'✅'};
var colors={error:'red',warning:'yellow',info:'blue',ok:'green'};

['error','warning','info','ok'].forEach(function(level){
var items=(d.issues||[]).filter(function(i){ return i.level===level; });
if(!items.length) return;
h+='<div class="mb-3">';
items.forEach(function(issue){
h+='<div class="flex items-start gap-2 py-1.5 border-b border-gray-100 last:border-0">';
h+='<span class="text-sm flex-shrink-0">'+levels[level]+'</span>';
h+='<div class="flex-1 min-w-0"><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"><p class="text-sm text-gray-800">'+e(issue.msg)+'</p>'+(issue.fix_action?'<button type="button" onclick="seoAuditRunFix(&#39;'+e(issue.fix_action)+'&#39;)" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded-lg text-xs font-semibold self-start">'+e(issue.fix_label||'Исправить')+'</button>':'')+'</div>';
if(issue.items&&issue.items.length){
h+='<details class="mt-1"><summary class="text-xs text-gray-400 cursor-pointer">Подробнее ('+issue.items.length+')</summary>';
h+='<ul class="mt-1 text-xs text-gray-500 list-disc pl-4">';
issue.items.slice(0,10).forEach(function(it){ h+='<li>'+e(it)+'</li>'; });
if(issue.items.length>10) h+='<li>...и ещё '+(issue.items.length-10)+'</li>';
h+='</ul></details>';
}
h+='</div></div>';
});
h+='</div>';
});

box.innerHTML=h;
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка: '+err.message+'</p>'; });
}



/* ============ CONTENT QUALITY ============ */
function cqBadgeStyle(score){
  score=Number(score||0);
  if(score>=90) return 'background:#d1fae5;color:#065f46;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center';
  if(score>=80) return 'background:#dcfce7;color:#166534;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center';
  if(score>=60) return 'background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center';
  return 'background:#fee2e2;color:#991b1b;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:600;display:inline-flex;align-items:center';
}

function cqSetInlineScore(prefix, score){
  var badge=document.getElementById(prefix+'-quality-badge');
  var scoreEl=document.getElementById(prefix+'-quality-score');
  score=Number(score||0);
  if(scoreEl) scoreEl.textContent=String(score);
  if(badge){
    badge.setAttribute('style', cqBadgeStyle(score));
    badge.textContent='score '+score;
  }
}

function cqCollect(prefix){
  var data={
    title:(document.getElementById(prefix+'-t')||document.getElementById(prefix+'-title')||document.getElementById(prefix+'-name')||{}).value||'',
    h1:(document.getElementById(prefix+'-h1')||{}).value||'',
    description:(document.getElementById(prefix+'-desc')||document.getElementById(prefix+'-ex')||document.getElementById(prefix+'-de')||{}).value||'',
    content:(document.getElementById(prefix+'-co')||document.getElementById(prefix+'-content')||{}).value||'',
    category:(document.getElementById(prefix+'-c')||{}).value||'',
    amountMin:(document.getElementById(prefix+'-am1')||{}).value||'',
    amountMax:(document.getElementById(prefix+'-am2')||{}).value||'',
    termMinDays:(document.getElementById(prefix+'-t1')||{}).value||'',
    termMaxDays:(document.getElementById(prefix+'-t2')||{}).value||'',
    rate:(document.getElementById(prefix+'-r')||{}).value||'',
    rateUnit:(document.getElementById(prefix+'-ru')||{}).value||'',
    freeTermDays:(document.getElementById(prefix+'-fr')||{}).value||''
  };
  if(prefix==='of' && data.category==='debit_cards'){
    data.amountMin=''; data.amountMax=''; data.termMinDays=''; data.termMaxDays=''; data.rate=''; data.rateUnit=''; data.freeTermDays='';
  }
  return data;
}

function cqAnalyzeForm(prefix, entity){
  var data=cqCollect(prefix);
  var content=data.content || data.description;
  if(!content.trim()){ alert('Нет текста для анализа'); return; }
  ap('/content-quality',{method:'POST',body:JSON.stringify({action:'analyze',entity:entity,title:data.title||data.h1,description:data.description,content:content})}).then(function(d){
    if(d.error){ alert(d.error); return; }
    var a=d.analysis||{};
    var score=a.score||0;
    var scoreColor=score>=80?'green':score>=60?'yellow':'red';
    var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🧪 Качество контента</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>';
    h+='<div class="flex items-center gap-4 mb-4"><div class="w-16 h-16 rounded-full border-4 border-'+scoreColor+'-500 flex items-center justify-center"><span class="text-xl font-bold text-'+scoreColor+'-600">'+score+'</span></div><div><p class="font-bold text-gray-900">Score: '+score+'/100</p><p class="text-xs text-gray-500">Слов: '+(a.stats?.words||0)+' • Предложений: '+(a.stats?.sentences||0)+' • Абзацев: '+(a.stats?.paragraphs||0)+'</p></div></div>';
    if(a.issues&&a.issues.length){
      h+='<div class="space-y-2 mb-4">';
      a.issues.forEach(function(it){
        var icon=it.level==='error'?'❌':it.level==='warning'?'⚠️':'ℹ️';
        h+='<div class="text-sm text-gray-700">'+icon+' '+e(it.msg)+'</div>';
      });
      h+='</div>';
    } else {
      h+='<p class="text-green-600 text-sm font-medium mb-4">✅ Явных проблем не найдено</p>';
    }
    if(a.recommendations&&a.recommendations.length){
      h+='<div class="bg-blue-50 rounded-lg p-4"><h4 class="font-semibold text-blue-700 mb-2">Рекомендации</h4><ul class="text-sm text-blue-700 list-disc pl-5">';
      a.recommendations.forEach(function(r){ h+='<li>'+e(r)+'</li>'; });
      h+='</ul></div>';
    }
    cqSetInlineScore(prefix, score);
    modal(h);
  }).catch(function(){ alert('Ошибка анализа'); });
}

function cqApplyImprovedText(prefix, field, improved, original){
  if(field==='description'){
    var descEl=document.getElementById(prefix+'-desc')||document.getElementById(prefix+'-ex')||document.getElementById(prefix+'-de');
    if(descEl) descEl.value=improved||original;
  } else {
    var contentEl=document.getElementById(prefix+'-co')||document.getElementById(prefix+'-content');
    if(contentEl) contentEl.value=improved||original;
  }
}

function cqImproveField(prefix, entity, field, targetScore, maxPasses){
  var data=cqCollect(prefix);
  var sourceField = field==='description' ? data.description : data.content;
  if(!sourceField.trim()){ alert('Нет текста для улучшения'); return; }
  var untilMode=typeof targetScore==='number' && targetScore>0;
  var msg=untilMode ? ('Улучшать текст до score '+targetScore+'+ (до '+(maxPasses||3)+' проходов)?') : 'Улучшить текст через AI/шаблонный редактор?';
  if(!confirm(msg)) return;
  var action=untilMode ? 'improve_until' : 'improve';
  ap('/content-quality',{method:'POST',body:JSON.stringify({action:action,entity:entity,field:field,title:data.title||data.h1,description:data.description,content:sourceField,targetScore:targetScore||80,maxPasses:maxPasses||3,context:{category:data.category,amountMin:data.amountMin,amountMax:data.amountMax,termMinDays:data.termMinDays,termMaxDays:data.termMaxDays,rate:data.rate,rateUnit:data.rateUnit,freeTermDays:data.freeTermDays}})}).then(function(d){
    if(d.error){ alert(d.error); return; }
    cqApplyImprovedText(prefix, field, d.improved, sourceField);
    var before=d.analysis_before?.score||0, after=d.analysis_after?.score||0;
    var extra='';
    if(d.passes&&d.passes.length){ extra='\nПроходы: '+d.passes.map(function(p){return '#'+p.pass+': '+p.score;}).join(', '); }
    if(untilMode){ extra+='\nЦель '+(d.reached_target?'достигнута':'не достигнута')+': '+(d.target_score||targetScore)+'+'; }
    cqSetInlineScore(prefix, after);
    alert('✅ Текст улучшен ('+(d.provider||'template')+')\nScore: '+before+' → '+after+extra);
  }).catch(function(){ alert('Ошибка улучшения'); });
}

function cqCleanupOnly(prefix, entity, field){
  var data=cqCollect(prefix);
  var sourceField = field==='description' ? data.description : data.content;
  if(!sourceField.trim()){ alert('Нет текста для очистки'); return; }
  if(!confirm('Убрать только markdown/HTML-мусор без сильного переписывания текста?')) return;
  ap('/content-quality',{method:'POST',body:JSON.stringify({action:'cleanup_only',entity:entity,field:field,title:data.title||data.h1,description:data.description,content:sourceField})}).then(function(d){
    if(d.error){ alert(d.error); return; }
    cqApplyImprovedText(prefix, field, d.improved, sourceField);
    var before=d.analysis_before?.score||0, after=d.analysis_after?.score||0;
    cqSetInlineScore(prefix, after);
    alert('✅ Мусор очищен\nScore: '+before+' → '+after);
  }).catch(function(){ alert('Ошибка очистки'); });
}

function loadFunnelMap(){
var box=document.getElementById('funnel-map-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Загрузка воронки...</p>';
ap('/positions?action=funnel-map&days='+_posDays).then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">'+e(d.error)+'</p>'; return; }
var map=d.map||[];
if(!map.length){ box.innerHTML='<p class="text-gray-400 text-sm">Нет данных за выбранный период</p>'; return; }
var h='<div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr>';
h+='<th class="px-3 py-2 text-left">Оффер</th>';
h+='<th class="px-3 py-2 text-right">Просмотры</th>';
h+='<th class="px-3 py-2 text-right">→ Клики</th>';
h+='<th class="px-3 py-2 text-right">CTR</th>';
h+='<th class="px-3 py-2 text-right">→ Одобрено</th>';
h+='<th class="px-3 py-2 text-right">CR</th>';
h+='<th class="px-3 py-2 text-right">❌</th>';
h+='<th class="px-3 py-2 text-right">Доход</th>';
h+='<th class="px-3 py-2 text-right">EPC</th>';
h+='</tr></thead><tbody>';
map.forEach(function(r){
var ctrColor=r.view_to_click>=5?'text-green-600':r.view_to_click>=2?'text-blue-600':'text-gray-500';
var crColor=r.click_to_conv>=5?'text-green-600 font-bold':r.click_to_conv>=1?'text-blue-600':'text-gray-500';
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 font-medium max-w-[200px] truncate" title="'+e(r.title)+'"><a href="/offer/'+e(r.slug)+'" target="_blank" class="text-primary hover:underline">'+e(r.title)+'</a></td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+r.views+'</td>';
h+='<td class="px-3 py-2 text-right font-medium">'+r.clicks+'</td>';
h+='<td class="px-3 py-2 text-right '+ctrColor+'">'+r.view_to_click+'%</td>';
h+='<td class="px-3 py-2 text-right text-green-600 font-medium">'+r.approved+'</td>';
h+='<td class="px-3 py-2 text-right '+crColor+'">'+r.click_to_conv+'%</td>';
h+='<td class="px-3 py-2 text-right '+(r.rejected>0?'text-red-500':'text-gray-400')+'">'+r.rejected+'</td>';
h+='<td class="px-3 py-2 text-right text-green-600 font-medium">'+Number(r.revenue).toLocaleString("ru-RU")+' ₽</td>';
h+='<td class="px-3 py-2 text-right">'+Number(r.epc).toLocaleString("ru-RU")+' ₽</td>';
h+='</tr>';
});
h+='</tbody></table></div>';
box.innerHTML=h;
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}

function loadByPage(){
var box=document.getElementById('by-page-result');
box.innerHTML='<p class="text-gray-500 text-sm">⏳ Загрузка...</p>';
ap('/positions?action=by-page&days='+_posDays+'&limit=50').then(function(d){
if(d.error){ box.innerHTML='<p class="text-red-500 text-sm">'+e(d.error)+'</p>'; return; }
var pages=d.pages||[];
if(!pages.length){ box.innerHTML='<p class="text-gray-400 text-sm">Нет данных</p>'; return; }
var h='<div class="overflow-x-auto max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead class="bg-gray-50 sticky top-0"><tr>';
h+='<th class="px-3 py-2 text-left">Страница</th>';
h+='<th class="px-3 py-2 text-right">Позиция</th>';
h+='<th class="px-3 py-2 text-right">Показы</th>';
h+='<th class="px-3 py-2 text-right">Клики</th>';
h+='<th class="px-3 py-2 text-right">CTR</th>';
h+='</tr></thead><tbody>';
pages.forEach(function(pg){
var posColor=pg.position<=3?'text-green-600 font-bold':pg.position<=10?'text-blue-600 font-medium':pg.position<=30?'text-yellow-600':'text-gray-500';
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="px-3 py-2 font-mono text-xs max-w-[300px] truncate" title="'+e(pg.url)+'"><a href="'+e(pg.url)+'" target="_blank" class="text-primary hover:underline">'+e(pg.url)+'</a></td>';
h+='<td class="px-3 py-2 text-right '+posColor+'">'+pg.position+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-600">'+pg.shows+'</td>';
h+='<td class="px-3 py-2 text-right font-medium">'+pg.clicks+'</td>';
h+='<td class="px-3 py-2 text-right text-gray-500">'+pg.ctr+'%</td>';
h+='</tr>';
});
h+='</tbody></table></div>';
box.innerHTML=h;
}).catch(function(err){ box.innerHTML='<p class="text-red-500 text-sm">Ошибка</p>'; });
}



// === PWA Stats ===
var pwaChart=null;
function loadPwaStats(p){
  p=p||30;
  var el=document.getElementById('p-pwa');
  el.innerHTML='<div class="text-center py-12"><div class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';
  ap('/pwa-stats?period='+p).then(function(d){
    if(d.message){el.innerHTML='<div class="bg-yellow-50 border border-yellow-200 p-6 rounded-xl text-yellow-800">'+d.message+'</div>';return;}
    var t=d.total,cr=t.prompts_shown>0?((t.installs/t.prompts_shown)*100).toFixed(1):'0';
    var h='<div class="space-y-6">';
    h+='<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3"><h2 class="text-xl font-bold text-gray-900">' + String.fromCodePoint(0x1F4F1) + ' PWA Статистика</h2><select onchange="loadPwaStats(this.value)" class="sel-f" style="width:auto"><option value="7"'+(p==7?' selected':'')+'>7 дней</option><option value="30"'+(p==30?' selected':'')+'>30 дней</option><option value="90"'+(p==90?' selected':'')+'>90 дней</option></select></div>';
    h+='<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
    h+='<div class="bg-green-50 border border-green-100 rounded-xl p-5 text-center"><div class="text-3xl font-bold text-green-600">'+t.installs+'</div><div class="text-sm text-green-700 mt-1">Установок</div></div>';
    h+='<div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-center"><div class="text-3xl font-bold text-blue-600">'+t.visits+'</div><div class="text-sm text-blue-700 mt-1">Визитов</div></div>';
    h+='<div class="bg-purple-50 border border-purple-100 rounded-xl p-5 text-center"><div class="text-3xl font-bold text-purple-600">'+t.standalone_visits+'</div><div class="text-sm text-purple-700 mt-1">Через PWA</div></div>';
    h+='<div class="bg-amber-50 border border-amber-100 rounded-xl p-5 text-center"><div class="text-3xl font-bold text-amber-600">'+cr+'%</div><div class="text-sm text-amber-700 mt-1">Конверсия</div></div>';
    h+='</div>';
    h+='<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><h3 class="font-bold text-gray-900 mb-4">' + String.fromCodePoint(0x1F4C8) + ' Динамика</h3><canvas id="pwaChartCanvas" height="80"></canvas></div>';
    h+='<div class="grid md:grid-cols-2 gap-6">';
    h+='<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><h3 class="font-bold text-gray-900 mb-4">' + String.fromCodePoint(0x1F4CA) + ' Платформы</h3>';
    if(d.platforms.length){h+='<table class="w-full text-sm"><thead><tr class="text-gray-500 border-b"><th class="text-left pb-3 font-medium">Платформа</th><th class="pb-3 font-medium text-center">Визиты</th><th class="pb-3 font-medium text-center">Уст.</th></tr></thead><tbody>';d.platforms.forEach(function(r){h+='<tr class="border-b border-gray-50"><td class="py-3 font-medium">'+(r.platform=="ios"?String.fromCodePoint(0x1F34E)+" iOS":r.platform=="android"?String.fromCodePoint(0x1F916)+" Android":String.fromCodePoint(0x1F4BB)+" Desktop")+'</td><td class="text-center">'+r.visits+'</td><td class="text-center text-green-600 font-semibold">'+r.installs+'</td></tr>';});h+='</tbody></table>';}else{h+='<p class="text-gray-400 text-sm">Нет данных</p>';}
    h+='</div>';
    h+='<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><h3 class="font-bold text-gray-900 mb-4">' + String.fromCodePoint(0x1F4F1) + ' Устройства</h3>';
    if(d.devices.length){h+='<div class="space-y-2 max-h-56 overflow-y-auto">';d.devices.forEach(function(r){h+='<div class="flex justify-between items-center py-2 border-b border-gray-50"><span class="text-sm font-medium">'+(r.platform=="ios"?String.fromCodePoint(0x1F34E):String.fromCodePoint(0x1F916))+' '+e(r.device_model)+'</span><span class="text-sm text-gray-500">'+r.count+(r.installs>0?' <span class="text-green-600 font-semibold">+'+r.installs+'</span>':'')+'</span></div>';});h+='</div>';}else{h+='<p class="text-gray-400 text-sm">Нет данных</p>';}
    h+='</div></div>';
    h+='<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6"><h3 class="font-bold text-gray-900 mb-4">' + String.fromCodePoint(0x1F195) + ' Последние установки</h3>';
    if(d.recent_installs.length){h+='<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">';d.recent_installs.forEach(function(r){h+='<div class="bg-gray-50 rounded-lg p-3 border border-gray-100"><div class="text-sm font-medium">'+(r.platform=="ios"?String.fromCodePoint(0x1F34E):String.fromCodePoint(0x1F916))+' '+(r.device_model||r.browser)+'</div><div class="text-xs text-gray-400 mt-1">'+r.created_at+'</div></div>';});h+='</div>';}else{h+='<p class="text-gray-400 text-sm">Пока нет установок</p>';}
    h+='</div></div>';
    el.innerHTML=h;
    if(d.daily.length){var ctx=document.getElementById('pwaChartCanvas');if(pwaChart)pwaChart.destroy();pwaChart=new Chart(ctx,{type:'line',data:{labels:d.daily.map(function(r){return r.date.slice(5);}),datasets:[{label:'Визиты',data:d.daily.map(function(r){return parseInt(r.visits)||0;}),borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,0.08)',fill:true,tension:0.3},{label:'Установки',data:d.daily.map(function(r){return parseInt(r.installs)||0;}),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.08)',fill:true,tension:0.3},{label:'PWA',data:d.daily.map(function(r){return parseInt(r.standalone)||0;}),borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.08)',fill:true,tension:0.3}]},options:{responsive:true,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}});}
  }).catch(function(){el.innerHTML='<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка загрузки</div>';});
}
var _origSw=sw;sw=function(t){_origSw(t);if(t==='pwa')loadPwaStats(30);};


// === Mobile App Stats ===
var appChart=null;
function loadAppStats(p){
  p=p||30;var el=document.getElementById('p-mobileapp');
  el.innerHTML='<div class="text-center py-12"><div class="animate-spin inline-block w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';
  ap('/app-stats?period='+p).then(function(d){
    var t=d.total;
    var h='<div class="space-y-6">';
    h+='<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3"><h2 class="text-xl font-bold">'+String.fromCodePoint(0x1F4F2)+' Статистика приложения</h2><select onchange="loadAppStats(this.value)" class="sel-f" style="width:auto"><option value="7"'+(p==7?' selected':'')+'>7 дн.</option><option value="30"'+(p==30?' selected':'')+'>30 дн.</option><option value="90"'+(p==90?' selected':'')+'>90 дн.</option></select></div>';
    h+='<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
    h+='<div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center"><div class="text-2xl font-bold text-green-600">'+t.downloads+'</div><div class="text-xs text-green-700 mt-1">Скачиваний</div></div>';
    h+='<div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center"><div class="text-2xl font-bold text-blue-600">'+t.opens+'</div><div class="text-xs text-blue-700 mt-1">Открытий</div></div>';
    h+='<div class="bg-purple-50 border border-purple-100 rounded-xl p-4 text-center"><div class="text-2xl font-bold text-purple-600">'+t.unique_devices+'</div><div class="text-xs text-purple-700 mt-1">Устройств</div></div>';
    h+='<div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center"><div class="text-2xl font-bold text-amber-600">'+t.offer_clicks+'</div><div class="text-xs text-amber-700 mt-1">Кликов</div></div>';
    h+='</div>';
    h+='<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">';
    h+='<div class="bg-white border rounded-xl p-4 text-center"><div class="text-xl font-bold text-gray-800">'+(t.applies||0)+'</div><div class="text-xs text-gray-500">Заявок</div></div>';
    h+='<div class="bg-white border rounded-xl p-4 text-center"><div class="text-xl font-bold text-gray-800">'+(t.article_views||0)+'</div><div class="text-xs text-gray-500">Статей</div></div>';
    h+='<div class="bg-white border rounded-xl p-4 text-center"><div class="text-xl font-bold text-gray-800">'+(t.calc_uses||0)+'</div><div class="text-xs text-gray-500">Калькулятор</div></div>';
    h+='<div class="bg-white border rounded-xl p-4 text-center"><div class="text-xl font-bold text-gray-800">'+(t.favorites||0)+'</div><div class="text-xs text-gray-500">Избранное</div></div>';
    h+='</div>';
    h+='<div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-bold mb-4">'+String.fromCodePoint(0x1F4C8)+' Динамика</h3><canvas id="appChartCanvas" height="80"></canvas></div>';
    h+='<div class="grid md:grid-cols-2 gap-6">';
    h+='<div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-bold mb-4">Популярные офферы</h3>';
    if(d.offers&&d.offers.length){d.offers.forEach(function(o){h+='<div class="flex justify-between items-center py-2 border-b border-gray-50 text-sm gap-2"><span class="font-medium truncate min-w-0 flex-1">'+e(o.offer_title||'#'+o.offer_id)+'</span><span class="text-gray-500 whitespace-nowrap flex-shrink-0">'+o.clicks+' кл. '+(o.applies>0?'<span class="text-green-600">'+o.applies+' заяв.</span>':'')+'</span></div>';});}else{h+='<p class="text-gray-400 text-sm">Нет данных</p>';}
    h+='</div>';
    h+='<div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-bold mb-4">Устройства</h3>';
    if(d.devices&&d.devices.length){h+='<div class="space-y-0 max-h-64 overflow-y-auto">';d.devices.forEach(function(v){h+='<div class="flex justify-between items-center py-2 border-b border-gray-50 text-sm gap-2"><span class="truncate min-w-0 flex-1">'+(v.platform=="ios"?String.fromCodePoint(0x1F34E):v.platform=="android"?String.fromCodePoint(0x1F916):String.fromCodePoint(0x1F4BB))+' '+e(v.device_model)+'</span><span class="text-gray-500 whitespace-nowrap flex-shrink-0">'+v.users+' польз.</span></div>';});h+='</div>';}else{h+='<p class="text-gray-400 text-sm">Нет данных</p>';}
    h+='</div></div>';
    if(d.recent&&d.recent.length){h+='<div class="bg-white rounded-xl shadow-sm border p-6"><h3 class="font-bold mb-4">Последние действия</h3><div class="max-h-80 overflow-y-auto"><table class="w-full text-sm"><thead><tr class="text-gray-500 border-b"><th class="text-left pb-2 font-medium">Действие</th><th class="text-left pb-2 font-medium">Детали</th><th class="text-left pb-2 font-medium">Устройство</th><th class="text-right pb-2 font-medium whitespace-nowrap">Время</th></tr></thead><tbody>';d.recent.forEach(function(r){var labels={app_open:String.fromCodePoint(0x1F4F1)+' Открыл',page_view:String.fromCodePoint(0x1F4C4)+' Страница',offer_click:String.fromCodePoint(0x1F517)+' Клик',offer_apply:String.fromCodePoint(0x2705)+' Заявка',article_view:String.fromCodePoint(0x1F4F0)+' Статья',calculator_use:String.fromCodePoint(0x1F9EE)+' Калькулятор',favorite_add:String.fromCodePoint(0x2764)+' Избранное',review_submit:String.fromCodePoint(0x2B50)+' Отзыв',search:String.fromCodePoint(0x1F50D)+' Поиск',category_view:String.fromCodePoint(0x1F4C2)+' Категория'};var detail=r.offer_title||r.screen_name||'—';var dev=r.device_model||'—';var time=r.created_at?r.created_at.slice(5,16).replace('T',' '):'';h+='<tr class="border-b border-gray-50"><td class="py-2 whitespace-nowrap">'+(labels[r.event_type]||r.event_type)+'</td><td class="py-2 max-w-[200px] truncate" title="'+e(detail)+'">'+e(detail)+'</td><td class="py-2 max-w-[120px] truncate text-gray-500" title="'+e(dev)+'">'+(r.platform=="ios"?String.fromCodePoint(0x1F34E):r.platform=="android"?String.fromCodePoint(0x1F916):String.fromCodePoint(0x1F4BB))+' '+e(dev)+'</td><td class="py-2 text-right text-gray-400 whitespace-nowrap">'+e(time)+'</td></tr>';});h+='</tbody></table></div></div>';}
    if(d.message){h+='<div class="bg-yellow-50 border border-yellow-200 p-4 rounded-xl text-yellow-800 text-sm">'+String.fromCodePoint(0x26A0)+' '+d.message+'</div>';}
    h+='</div>';
    el.innerHTML=h;
    if(d.daily&&d.daily.length){var ctx=document.getElementById('appChartCanvas');if(appChart)appChart.destroy();appChart=new Chart(ctx,{type:'line',data:{labels:d.daily.map(function(r){return r.date.slice(5);}),datasets:[{label:'Скачивания',data:d.daily.map(function(r){return parseInt(r.downloads)||0;}),borderColor:'#22c55e',backgroundColor:'rgba(34,197,94,0.08)',fill:true,tension:0.3},{label:'Открытия',data:d.daily.map(function(r){return parseInt(r.opens)||0;}),borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,0.08)',fill:true,tension:0.3},{label:'Клики',data:d.daily.map(function(r){return parseInt(r.clicks)||0;}),borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,0.08)',fill:true,tension:0.3},{label:'Пользователи',data:d.daily.map(function(r){return parseInt(r.users)||0;}),borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.08)',fill:true,tension:0.3}]},options:{responsive:true,interaction:{mode:'index',intersect:false},plugins:{legend:{position:'bottom'},tooltip:{mode:'index',intersect:false}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});}
  }).catch(function(){el.innerHTML='<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">'+String.fromCodePoint(0x274C)+' Ошибка загрузки статистики</div>';});
}
var _sw2=sw;sw=function(t){_sw2(t);if(t==='mobileapp')loadAppStats(30);};

function genOfferInfo(btn){var t=document.getElementById('of-t').value,c=document.getElementById('of-c').value;if(!t){alert('Введите название');return;}var b=btn||null;if(b){b.disabled=true;b.textContent='...';}ap('/offer-contacts-generate',{method:'POST',body:JSON.stringify({title:t,category:c})}).then(function(r){if(r.error){alert('Ошибка: '+r.error+(r.raw?'\n\nОтвет ИИ:\n'+r.raw:''));return;}var d=r.contacts||{};var filled=0;if(document.getElementById('of-phone')){document.getElementById('of-phone').value=d.phone||'';if(d.phone)filled++;}if(document.getElementById('of-license')){document.getElementById('of-license').value=d.license||'';if(d.license)filled++;}if(document.getElementById('of-trademark')){document.getElementById('of-trademark').value=d.trademark||'';if(d.trademark)filled++;}if(document.getElementById('of-address')){document.getElementById('of-address').value=d.address||'';if(d.address)filled++;}if(filled===0){alert('ИИ не заполнил поля.\n\nОтвет ИИ:\n'+(r.raw||'Пусто'));}else{alert('ИИ заполнил полей: '+filled+(r.raw?'\n\nОтвет ИИ:\n'+r.raw:''));}}).catch(function(e){alert('Ошибка: '+(e.message||e));}).finally(function(){if(b){b.disabled=false;b.textContent='🤖 ИИ';}});}
// === Yandex Direct ===
// Загрузка данных Яндекс Директ
function lYD() {
    var el = document.getElementById('p-direct');
    if (!el) return;
    el.innerHTML = '<div class="flex justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';

    ap('/yandex-direct?action=report&days=30').then(function(d) {
        if (d.error) {
            el.innerHTML = '<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка: ' + e(d.error) + '</div>';
            return;
        }

        var h = '<div class="space-y-6">';

        // Заголовок
        h += '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
        h += '<div><h2 class="text-xl font-bold">📣 Яндекс Директ</h2><p class="text-gray-500 text-sm">Инструменты для эффективной рекламы</p></div>';
        h += '<div class="flex gap-2 flex-wrap">';
        h += '<select id="yd-period" class="sel-f" onchange="lYDReport(this.value)">';
        h += '<option value="7">7 дней</option><option value="30" selected>30 дней</option><option value="90">90 дней</option>';
        h += '</select>';
        h += '<button onclick="ydExportCSV()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Экспорт CSV</button>';
        h += '</div></div>';

        // Уведомление
        if (d.notice) {
            h += '<div class="bg-blue-50 border border-blue-200 p-4 rounded-xl text-blue-700 text-sm">ℹ️ ' + e(d.notice) + '</div>';
        }

        // Метрики
        h += '<div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="yd-metrics">';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-blue-600">' + (d.total_clicks || 0) + '</p><p class="text-xs text-gray-500">Кликов с Директа</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-green-600">' + (d.total_conversions || 0) + '</p><p class="text-xs text-gray-500">Конверсий</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-purple-600">' + (d.conversion_rate || 0) + '%</p><p class="text-xs text-gray-500">CR</p></div>';
        h += '<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold text-amber-600">' + Number(d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽</p><p class="text-xs text-gray-500">Доход</p></div>';
        h += '</div>';

        // Вкладки
        h += '<div class="bg-white rounded-xl border overflow-hidden">';
        h += '<div class="flex border-b overflow-x-auto">';
        h += '<button onclick="ydTab(\'generator\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-blue-600 text-blue-600 whitespace-nowrap" data-tab="generator">🎯 Генератор</button>';
        h += '<button onclick="ydTab(\'keywords\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="keywords">🔑 Ключевые слова</button>';
        h += '<button onclick="ydTab(\'analytics\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="analytics">📈 Аналитика</button>';
        h += '<button onclick="ydTab(\'tips\')" class="yd-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap" data-tab="tips">💡 Советы</button>';
        h += '</div>';
        h += '<div id="yd-content" class="p-6"></div>';
        h += '</div>';

        h += '</div>';
        el.innerHTML = h;
        window.ydReportData = d;
        ydTab('generator');
    }).catch(function(err) {
        el.innerHTML = '<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка загрузки: ' + (err && err.message ? err.message : String(err)) + '</div>';
    });
}

function ydTab(tab) {
    document.querySelectorAll('.yd-tab').forEach(function(t) {
        var isActive = t.dataset.tab === tab;
        t.classList.toggle('border-blue-600', isActive);
        t.classList.toggle('text-blue-600', isActive);
        t.classList.toggle('border-transparent', !isActive);
        t.classList.toggle('text-gray-500', !isActive);
    });
    var content = document.getElementById('yd-content');
    if (!content) return;
    if (tab === 'generator') ydShowGenerator(content);
    else if (tab === 'keywords') ydShowKeywords(content);
    else if (tab === 'analytics') ydShowAnalytics(content);
    else if (tab === 'tips') ydShowTips(content);
}

// === ГЕНЕРАТОР ===
function ydShowGenerator(el) {
    el.innerHTML = '<div class="space-y-4">' +
        '<div class="flex flex-wrap gap-4 items-end">' +
        '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-gen-cat" class="sel-f"><option value="">Все</option><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>' +
        '<div><label class="block text-xs font-medium mb-1">Шаблон</label><select id="yd-gen-tpl" class="sel-f"><option value="default">Стандартный</option><option value="urgent">Срочный</option><option value="free">Бесплатный</option><option value="trust">Доверие</option><option value="comparison">Сравнение</option></select></div>' +
        '<button onclick="ydGenerate()" class="btn-p">🔄 Сгенерировать</button>' +
        '</div>' +
        '<div id="yd-gen-list"><p class="text-gray-400 text-center py-6">Нажмите «Сгенерировать» для создания объявлений</p></div>' +
        '</div>';
}

function ydGenerate() {
    var cat = document.getElementById('yd-gen-cat').value;
    var tpl = document.getElementById('yd-gen-tpl').value;
    var list = document.getElementById('yd-gen-list');
    list.innerHTML = '<p class="text-gray-400 text-center py-6">Загрузка...</p>';

    var url = '/yandex-direct?action=generate-ads&template=' + encodeURIComponent(tpl);
    if (cat) url += '&category=' + encodeURIComponent(cat);

    ap(url).then(function(d) {
        if (!d.ads || !d.ads.length) { list.innerHTML = '<p class="text-gray-400 text-center py-6">Нет активных предложений</p>'; return; }

        var h = '<p class="text-sm text-gray-500 mb-4">Сгенерировано ' + d.ads.length + ' объявлений</p>';
        d.ads.forEach(function(item) {
            var adJson = JSON.stringify(item.ad).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            h += '<div class="bg-gray-50 rounded-xl p-4 border mb-4">';
            h += '<div class="flex justify-between items-start mb-3"><div><span class="font-bold">' + e(item.offer_title) + '</span></div>';
            h += '<button onclick="ydCopyAd(this)" class="text-blue-600 hover:text-blue-800 text-sm" data-ad="' + adJson + '">📋 Копировать</button></div>';

            // Превью
            h += '<div class="bg-white rounded-lg p-4 border mb-3">';
            h += '<p class="text-blue-700 font-medium text-base">' + e(item.ad.title1) + '</p>';
            h += '<p class="text-blue-600 text-sm">' + e(item.ad.title2) + '</p>';
            h += '<p class="text-sm text-gray-700 mt-1">' + e(item.ad.text) + '</p>';
            h += '<p class="text-xs text-green-700 mt-2">kosmozaim.ru/offer/' + e(item.offer_slug) + '</p>';
            h += '</div>';

            h += '<div class="flex flex-wrap gap-2 text-xs">';
            (item.ad.sitelinks||[]).forEach(function(sl) { h += '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded">' + e(sl.title) + '</span>'; });
            (item.ad.clarifications||[]).forEach(function(cl) { h += '<span class="bg-gray-200 text-gray-600 px-2 py-1 rounded">' + e(cl) + '</span>'; });
            h += '</div></div>';
        });
        list.innerHTML = h;
    }).catch(function(err) {
        list.innerHTML = '<p class="text-red-500 text-center py-6">Ошибка: ' + (err&&err.message?err.message:String(err)) + '</p>';
    });
}

function ydCopyAd(btn) {
    var ad = JSON.parse(btn.dataset.ad);
    var text = 'Заголовок 1: ' + ad.title1 + '\nЗаголовок 2: ' + ad.title2 + '\nТекст: ' + ad.text;
    if (ad.sitelinks) text += '\nБыстрые ссылки: ' + ad.sitelinks.map(function(s){return s.title;}).join(', ');
    if (ad.clarifications) text += '\nУточнения: ' + ad.clarifications.join(', ');
    navigator.clipboard.writeText(text).then(function() {
        btn.textContent = '✅ Скопировано'; setTimeout(function(){ btn.textContent = '📋 Копировать'; }, 2000);
    }).catch(function() { prompt('Скопируйте:', text); });
}

// === КЛЮЧЕВЫЕ СЛОВА ===
function ydShowKeywords(el) {
    el.innerHTML = '<div class="space-y-4">' +
        '<div><label class="block text-xs font-medium mb-1">Категория</label><select id="yd-kw-cat" class="sel-f w-auto" onchange="ydLoadKW()"><option value="microloans">Займы</option><option value="credits">Кредиты</option><option value="credit_cards">Кредитные карты</option><option value="debit_cards">Дебетовые карты</option></select></div>' +
        '<div id="yd-kw-data"><p class="text-gray-400 text-center py-6">Загрузка...</p></div></div>';
    ydLoadKW();
}

function ydLoadKW() {
    var cat = document.getElementById('yd-kw-cat').value;
    var content = document.getElementById('yd-kw-data');
    content.innerHTML = '<p class="text-gray-400 text-center py-4">Загрузка...</p>';

    ap('/yandex-direct?action=keywords&category=' + encodeURIComponent(cat)).then(function(d) {
        var h = '<div class="grid md:grid-cols-2 gap-6">';
        // Левая колонка — ключевые слова
        h += '<div class="space-y-4">';
        h += ydKwBlock('🎯 Высокочастотные', d.keywords.high, 'green');
        h += ydKwBlock('📊 Среднечастотные', d.keywords.medium, 'blue');
        h += ydKwBlock('🔍 Длинный хвост', d.keywords.long_tail, 'purple');
        h += '</div>';
        // Правая колонка — минус-слова
        h += '<div>' + ydKwBlock('🚫 Минус-слова', d.minus_words, 'red') + '</div>';
        h += '</div>';
        content.innerHTML = h;
    });
}

function ydKwBlock(title, words, color) {
    var wordsJson = JSON.stringify(words).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    var h = '<div class="bg-' + color + '-50 rounded-lg p-3 border border-' + color + '-100">';
    h += '<div class="flex justify-between items-center mb-2"><h4 class="font-bold text-sm">' + title + '</h4>';
    h += '<button onclick="ydCopyKW(this)" data-kw="' + wordsJson + '" class="text-' + color + '-600 text-xs hover:underline">📋 Копировать</button></div>';
    h += '<ul class="text-sm space-y-0.5 max-h-60 overflow-y-auto">';
    words.forEach(function(w) { h += '<li class="text-gray-700">• ' + e(w) + '</li>'; });
    h += '</ul></div>';
    return h;
}

function ydCopyKW(btn) {
    var kw = JSON.parse(btn.dataset.kw);
    navigator.clipboard.writeText(kw.join('\n')).then(function() {
        btn.textContent = '✅'; setTimeout(function(){ btn.textContent = '📋 Копировать'; }, 2000);
    }).catch(function() { prompt('Скопируйте:', kw.join('\n')); });
}

// === АНАЛИТИКА ===
function ydShowAnalytics(el) {
    var d = window.ydReportData || {};
    var h = '<div class="space-y-6">';

    h += '<div><h4 class="font-bold mb-3">📊 Кампании</h4>';
    if (d.campaigns && d.campaigns.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="text-left py-2 px-3">Кампания</th><th class="text-right py-2 px-3">Клики</th><th class="text-right py-2 px-3">Уникальные</th></tr></thead><tbody>';
        d.campaigns.forEach(function(c) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2 px-3">' + e(c.utm_campaign || '(без имени)') + '</td><td class="text-right py-2 px-3 font-medium">' + c.total_clicks + '</td><td class="text-right py-2 px-3">' + c.unique_visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400 text-sm">Пока нет данных. Запустите рекламу с UTM-метками utm_source=yandex.</p>';
    }
    h += '</div>';

    h += '<div><h4 class="font-bold mb-3">🔑 Топ ключевых слов</h4>';
    if (d.top_keywords && d.top_keywords.length) {
        h += '<div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="text-left py-2 px-3">Ключевое слово</th><th class="text-right py-2 px-3">Клики</th><th class="text-right py-2 px-3">Посетители</th></tr></thead><tbody>';
        d.top_keywords.forEach(function(kw) {
            h += '<tr class="border-b hover:bg-gray-50"><td class="py-2 px-3">' + e(kw.keyword) + '</td><td class="text-right py-2 px-3 font-medium">' + kw.clicks + '</td><td class="text-right py-2 px-3">' + kw.visitors + '</td></tr>';
        });
        h += '</tbody></table></div>';
    } else {
        h += '<p class="text-gray-400 text-sm">Пока нет данных по ключевым словам.</p>';
    }
    h += '</div></div>';
    el.innerHTML = h;
}

// === СОВЕТЫ ===
function ydShowTips(el) {
    var d = window.ydReportData || {};
    var h = '<div class="space-y-4">';

    if (d.tips && d.tips.length) {
        d.tips.forEach(function(tip) {
            var colors = {success:'bg-green-50 border-green-200 text-green-800', warning:'bg-yellow-50 border-yellow-200 text-yellow-800', info:'bg-blue-50 border-blue-200 text-blue-800'};
            var icons = {success:'✅', warning:'⚠️', info:'💡'};
            h += '<div class="p-4 rounded-lg border ' + (colors[tip.type]||colors.info) + '"><p class="font-semibold">' + (icons[tip.type]||'💡') + ' ' + e(tip.title) + '</p><p class="text-sm mt-1">' + e(tip.message) + '</p></div>';
        });
    }

    // Общие советы
    h += '<div class="bg-gray-50 rounded-xl p-6 mt-4 border">';
    h += '<h4 class="font-bold mb-4">📚 Чеклист запуска рекламы в Директе</h4>';
    h += '<div class="space-y-3 text-sm">';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Указать <strong>лицензию ЦБ РФ</strong> в тексте (для прохождения модерации)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить аудиторию <strong>18+</strong></span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Добавить <strong>минус-слова</strong> (вкладка «Ключевые слова»)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>корректировки ставок</strong> по времени (10-14, 18-22)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Увеличить ставку на <strong>мобильные</strong> (+20%)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Исключить <strong>нерелевантные регионы</strong></span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>UTM-метки</strong> (utm_source=yandex&utm_medium=cpc)</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Создать <strong>2-3 варианта объявлений</strong> для A/B теста</span></label>';
    h += '<label class="flex items-start gap-3 cursor-pointer"><input type="checkbox" class="w-4 h-4 mt-0.5 rounded"><span>Настроить <strong>ретаргетинг</strong> на посетителей сайта</span></label>';
    h += '</div></div>';

    h += '</div>';
    el.innerHTML = h;
}

// Экспорт CSV
function ydExportCSV() {
    var cat = document.getElementById('yd-gen-cat') ? document.getElementById('yd-gen-cat').value : '';
    var url = '/api/admin/yandex-direct?action=export-csv';
    if (cat) url += '&category=' + encodeURIComponent(cat);
    window.open(url, '_blank');
}

// Обновление за период
function lYDReport(days) {
    ap('/yandex-direct?action=report&days=' + days).then(function(d) {
        window.ydReportData = d;
        var metrics = document.getElementById('yd-metrics');
        if (metrics) {
            var divs = metrics.querySelectorAll('.text-2xl');
            if (divs[0]) divs[0].textContent = d.total_clicks || 0;
            if (divs[1]) divs[1].textContent = d.total_conversions || 0;
            if (divs[2]) divs[2].textContent = (d.conversion_rate || 0) + '%';
            if (divs[3]) divs[3].textContent = Number(d.total_revenue || 0).toLocaleString('ru-RU') + ' ₽';
        }
    });
}
// === Social Proof Settings ===
// Добавляем секцию в настройки
(function() {
    var origLSet = window.lSet;
    window.lSet = function() {
        if (origLSet) origLSet();
        
        // Ждём загрузки основных настроек и добавляем секцию
        setTimeout(function() {
            var settingsDiv = document.getElementById('p-settings');
            if (!settingsDiv) return;
            
            // Проверяем не добавлена ли уже секция
            if (document.getElementById('sp-settings-section')) return;
            
            // Загружаем настройки виджета
            ap('/social-proof').then(function(sp) {
                var section = document.createElement('div');
                section.id = 'sp-settings-section';
                section.className = 'bg-white rounded-xl border p-6 mt-6';
                section.innerHTML = '<h3 class="text-lg font-bold mb-4">🔔 Виджет социального доказательства</h3>' +
                    '<p class="text-sm text-gray-500 mb-4">Показывает уведомления вида «Иван из Москвы получил 15 000 ₽»</p>' +
                    '<div class="space-y-4">' +
                    '<label class="flex items-center gap-3 cursor-pointer">' +
                    '<input type="checkbox" id="sp-enabled" class="w-5 h-5 rounded" ' + (sp.enabled ? 'checked' : '') + '>' +
                    '<span class="font-medium">Включить виджет</span>' +
                    '</label>' +
                    '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">' +
                    '<div><label class="block text-xs font-medium mb-1">Интервал (мс)</label>' +
                    '<input type="number" id="sp-interval" class="input-f" value="' + (sp.interval || 8000) + '" min="3000" max="30000" step="1000">' +
                    '<p class="text-xs text-gray-400 mt-1">Между показами</p></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Длительность (мс)</label>' +
                    '<input type="number" id="sp-duration" class="input-f" value="' + (sp.duration || 5000) + '" min="2000" max="10000" step="500">' +
                    '<p class="text-xs text-gray-400 mt-1">Показ уведомления</p></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Мин. сумма ₽</label>' +
                    '<input type="number" id="sp-min" class="input-f" value="' + (sp.min_amount || 5000) + '" min="1000" step="1000"></div>' +
                    '<div><label class="block text-xs font-medium mb-1">Макс. сумма ₽</label>' +
                    '<input type="number" id="sp-max" class="input-f" value="' + (sp.max_amount || 30000) + '" min="5000" step="1000"></div>' +
                    '</div>' +
                    '<div><label class="block text-xs font-medium mb-1">Позиция</label>' +
                    '<select id="sp-position" class="sel-f w-auto">' +
                    '<option value="bottom-left"' + (sp.position === 'bottom-left' ? ' selected' : '') + '>Слева внизу</option>' +
                    '<option value="bottom-right"' + (sp.position === 'bottom-right' ? ' selected' : '') + '>Справа внизу</option>' +
                    '</select></div>' +
                    '<button onclick="saveSocialProof()" class="btn-p">💾 Сохранить настройки виджета</button>' +
                    '</div>';
                
                settingsDiv.appendChild(section);
            });
        }, 500);
    };
})();

function saveSocialProof() {
    var data = {
        enabled: document.getElementById('sp-enabled').checked,
        interval: parseInt(document.getElementById('sp-interval').value) || 8000,
        duration: parseInt(document.getElementById('sp-duration').value) || 5000,
        min_amount: parseInt(document.getElementById('sp-min').value) || 5000,
        max_amount: parseInt(document.getElementById('sp-max').value) || 30000,
        position: document.getElementById('sp-position').value
    };
    
    ap('/social-proof', {
        method: 'POST',
        body: JSON.stringify(data)
    }).then(function(res) {
        if (res.success) {
            alert('✅ Настройки виджета сохранены!');
        } else {
            alert('Ошибка: ' + (res.error || 'Неизвестная ошибка'));
        }
    }).catch(function(err) {
        alert('Ошибка: ' + (err.message || err));
    });
}


// === AI Providers (lAIP) ===
// === Перегенерация картинки статьи ===
function aiRegenImage(articleId){
    if(!articleId){alert('Сначала сохраните статью');return;}
    var customPrompt=prompt('Промпт для картинки (пустое = автоматический по заголовку):','');
    if(customPrompt===null)return;
    var btn=document.getElementById('af-ai-img-btn');
    if(btn){btn.disabled=true;btn.textContent='⏳ Генерация...';}
    var body={articleId:articleId};
    if(customPrompt)body.prompt=customPrompt;
    fetch(A+'/article-regenerate-image',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)}).then(function(r){return r.json();}).then(function(d){
        if(btn){btn.disabled=false;btn.textContent='🤖 AI картинка';}
        if(d.success&&d.path){
            var inp=document.getElementById('af-ci');
            if(inp)inp.value=d.path;
            var prev=document.getElementById('af-ci-preview');
            if(prev)prev.innerHTML='<img src="'+d.path+'" class="w-20 h-12 object-cover rounded border">';
            alert('✅ Картинка сгенерирована!\n'+d.provider+'\nПуть: '+d.path);
        }else{
            alert('❌ Ошибка: '+(d.error||'неизвестная'));
        }
    }).catch(function(err){
        if(btn){btn.disabled=false;btn.textContent='🤖 AI картинка';}
        alert('Ошибка: '+err.message);
    });
}

function lAIP(){
    var c=document.getElementById('p-aiproviders');
    c.innerHTML='<p class="text-gray-500">Загрузка...</p>';
    fetch(A+'/ai-providers').then(r=>r.json()).then(function(d){
        var tp=d.status?.text||{},ip=d.status?.image||{},cfg=d.config||{};
        var tPri=d.status?.priority?.text||[],iPri=d.status?.priority?.image||[];
        var actT=d.status?.active?.text||'нет',actI=d.status?.active?.image||'нет';
        var tModels=d.availableTextModels||{},iModels=d.availableImageModels||{};
        function optSel(obj,cur){if(!obj)return '';if(Array.isArray(obj)){return obj.map(function(m){return '<option value="'+m+'"'+(cur===m?' selected':'')+'>'+m+'</option>';}).join('');}return Object.keys(obj).map(function(k){var v=obj[k];return '<option value="'+k+'"'+(cur===k?' selected':'')+'>'+v+'</option>';}).join('');}
        function priItems(list,provs){return list.map(function(p){var pr=provs[p]||{};return '<div class="flex items-center gap-2 bg-gray-50 border p-3 rounded-lg cursor-move hover:bg-gray-100" data-provider="'+p+'"><span class="text-gray-400">☰</span><span class="flex-1 font-medium">'+(pr.name||p)+'</span><span class="text-xs text-gray-500">'+(pr.model||'')+'</span><span>'+(pr.available?'✅':'⚪')+'</span></div>';}).join('');}

        c.innerHTML='<div class="space-y-6">'+
        '<div class="flex items-center justify-between flex-wrap gap-4"><h2 class="text-xl font-bold">🤖 Управление AI провайдерами</h2><div class="flex gap-4 text-sm"><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full">Текст: <b>'+actT+'</b></span><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full">Изображение: <b>'+actI+'</b></span></div></div>'+

        '<div class="bg-white rounded-xl shadow p-6 border-l-4 '+(cfg.odirouter_enabled?'border-green-500':'border-gray-300')+'">'+
        '<div class="flex items-center justify-between mb-4 flex-wrap gap-2"><div class="flex items-center gap-3"><span class="text-3xl">🌐</span><div><h3 class="font-bold text-lg">OdiRouter</h3><p class="text-sm text-gray-500">200+ AI моделей (GPT, Gemini, Claude) + генерация изображений</p></div></div>'+
        '<label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" id="ai_odi_en" '+(cfg.odirouter_enabled?'checked':'')+' class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div></label></div>'+
        '<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">API ключ (LLM)</label><input type="password" id="ai_odi_key" value="'+(cfg.odirouter_api_key_masked||'')+'" placeholder="Ключ для текста" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">API ключ (картинки)</label><input type="password" id="ai_odi_img_key" value="'+(cfg.odirouter_image_api_key_masked||'')+'" placeholder="Отдельный ключ" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Модель текста</label><select id="ai_odi_tm" class="sel-f">'+optSel(tModels.odirouter,cfg.odirouter_text_model)+'</select></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Модель изображений</label><select id="ai_odi_im" class="sel-f">'+optSel(iModels.odirouter,cfg.odirouter_image_model)+'</select></div>'+
        '<div class="flex items-end gap-2"><button onclick="aiTest(\'odirouter\',\'text\')" class="btn-p text-xs py-2">🧪 Тест текста</button><button onclick="aiTest(\'odirouter\',\'image\')" class="btn-p text-xs py-2" style="background:#7c3aed">🖼 Тест картинки</button></div>'+
        '</div></div>'+

        '<div class="bg-white rounded-xl shadow p-6 border-l-4 '+(cfg.yandex_gpt_enabled?'border-yellow-500':'border-gray-300')+'">'+
        '<div class="flex items-center justify-between mb-4 flex-wrap gap-2"><div class="flex items-center gap-3"><span class="text-3xl">🟡</span><div><h3 class="font-bold text-lg">Yandex Cloud</h3><p class="text-sm text-gray-500">YandexGPT (текст) + YandexART (изображения)</p></div></div>'+
        '<div class="flex gap-4"><label class="flex items-center gap-1"><input type="checkbox" id="ai_ygpt_en" '+(cfg.yandex_gpt_enabled?'checked':'')+' class="w-5 h-5"> GPT</label><label class="flex items-center gap-1"><input type="checkbox" id="ai_yart_en" '+(cfg.yandex_art_enabled?'checked':'')+' class="w-5 h-5"> ART</label></div></div>'+
        '<div class="grid md:grid-cols-3 gap-4">'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label><input type="password" id="ai_ygpt_key" value="'+(cfg.yandex_gpt_api_key_masked||'')+'" placeholder="Api-Key" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Folder ID</label><input type="text" id="ai_yfid" value="'+(cfg.yandex_folder_id||'')+'" placeholder="b1g..." class="input-f"></div>'+
        '<div class="flex items-end gap-2"><button onclick="aiTest(\'yandex_gpt\',\'text\')" class="btn-p text-xs py-2">🧪 GPT</button><button onclick="aiTest(\'yandex_art\',\'image\')" class="btn-p text-xs py-2" style="background:#ea580c">🖼 ART</button></div>'+
        '</div></div>'+

        '<div class="bg-white rounded-xl shadow p-6 border-l-4 '+(cfg.gigachat_enabled?'border-blue-500':'border-gray-300')+'">'+
        '<div class="flex items-center justify-between mb-4 flex-wrap gap-2"><div class="flex items-center gap-3"><span class="text-3xl">🔵</span><div><h3 class="font-bold text-lg">GigaChat (Сбер)</h3><p class="text-sm text-gray-500">GigaChat текст + Kandinsky изображения</p></div></div>'+
        '<label class="flex items-center gap-2"><input type="checkbox" id="ai_gc_en" '+(cfg.gigachat_enabled?'checked':'')+' class="w-5 h-5"><span class="font-medium">'+(cfg.gigachat_enabled?'Вкл':'Выкл')+'</span></label></div>'+
        '<div class="grid md:grid-cols-3 gap-4">'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Auth Key (Base64)</label><input type="password" id="ai_gc_key" value="'+(cfg.gigachat_auth_key_masked||'')+'" placeholder="Basic ключ" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Scope</label><select id="ai_gc_scope" class="sel-f"><option value="GIGACHAT_API_PERS"'+(cfg.gigachat_scope==='GIGACHAT_API_PERS'?' selected':'')+'>Личный</option><option value="GIGACHAT_API_CORP"'+(cfg.gigachat_scope==='GIGACHAT_API_CORP'?' selected':'')+'>Корпоративный</option></select></div>'+
        '<div class="flex items-end gap-2"><button onclick="aiTest(\'gigachat\',\'text\')" class="btn-p text-xs py-2">🧪 Текст</button><button onclick="aiTest(\'gigachat\',\'image\')" class="btn-p text-xs py-2" style="background:#0891b2">🖼 Kandinsky</button></div>'+
        '</div></div>'+

        '<div class="bg-white rounded-xl shadow p-6 border-l-4 '+(cfg.stability_enabled?'border-pink-500':'border-gray-300')+'">'+
        '<div class="flex items-center justify-between mb-4 flex-wrap gap-2"><div class="flex items-center gap-3"><span class="text-3xl">🎨</span><div><h3 class="font-bold text-lg">Stability AI</h3><p class="text-sm text-gray-500">Stable Diffusion (только изображения)</p></div></div>'+
        '<label class="flex items-center gap-2"><input type="checkbox" id="ai_st_en" '+(cfg.stability_enabled?'checked':'')+' class="w-5 h-5"><span class="font-medium">'+(cfg.stability_enabled?'Вкл':'Выкл')+'</span></label></div>'+
        '<div class="grid md:grid-cols-2 gap-4">'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">API ключ</label><input type="password" id="ai_st_key" value="'+(cfg.stability_api_key_masked||'')+'" placeholder="sk-..." class="input-f"></div>'+
        '<div class="flex items-end"><button onclick="aiTest(\'stability\',\'image\')" class="btn-p text-xs py-2" style="background:#db2777">🖼 Тест</button></div>'+
        '</div></div>'+

        '<div class="bg-white rounded-xl shadow p-6"><h3 class="font-bold text-lg mb-1">📊 Приоритет провайдеров</h3><p class="text-sm text-gray-500 mb-4">Перетащите для изменения порядка. Система использует первый доступный ✅ провайдер.</p>'+
        '<div class="grid md:grid-cols-2 gap-6">'+
        '<div><h4 class="font-medium mb-2">📝 Текст</h4><div id="ai-tp" class="space-y-2">'+priItems(tPri,tp)+'</div></div>'+
        '<div><h4 class="font-medium mb-2">🖼️ Изображения</h4><div id="ai-ip" class="space-y-2">'+priItems(iPri,ip)+'</div></div>'+
        '</div></div>'+

        '<div class="bg-white rounded-xl shadow p-6 border-l-4 '+(cfg.chat_enabled?'border-emerald-500':'border-gray-300')+'">'+
        '<div class="flex items-center justify-between mb-4 flex-wrap gap-2"><div class="flex items-center gap-3"><span class="text-3xl">💬</span><div><h3 class="font-bold text-lg">AI-чат для посетителей</h3><p class="text-sm text-gray-500">Виджет чата на сайте — отвечает на вопросы посетителей</p></div></div>'+
        '<label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" id="ai_chat_en" '+(cfg.chat_enabled?'checked':'')+' class="sr-only peer"><div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div></label></div>'+
        '<div class="grid md:grid-cols-2 gap-4">'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Модель чата</label><select id="ai_chat_model" class="sel-f">'+optSel(tModels.odirouter||{},cfg.chat_model||"free-gemini-2.5-flash")+'</select></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Лимит запросов/час на IP</label><input type="number" id="ai_chat_limit" value="'+(cfg.chat_rate_limit||30)+'" min="1" max="200" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Заголовок чата</label><input type="text" id="ai_chat_title" value="'+(cfg.chat_title||"")+'" placeholder="Помощник '+siteName+'" class="input-f"></div>'+
        '<div><label class="block text-sm font-medium text-gray-700 mb-1">Приветствие</label><input type="text" id="ai_chat_greeting" value="'+(cfg.chat_greeting||"")+'" placeholder="Здравствуйте! Задайте вопрос..." class="input-f"></div>'+
        '<div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Системный промпт (инструкция для AI)</label><textarea id="ai_chat_prompt" rows="3" class="input-f" placeholder="Ты помощник финансового сайта...">'+(cfg.chat_system_prompt||"")+'</textarea><p class="text-xs text-gray-500 mt-1">Оставьте пустым для промпта по умолчанию</p></div>'+
        '</div></div>'+
        '<div class="flex justify-end"><button onclick="aiSaveAll()" class="btn-p text-base px-8 py-3">💾 Сохранить все настройки</button></div>'+
        '</div>';

        if(typeof Sortable!=='undefined'){
            new Sortable(document.getElementById('ai-tp'),{animation:150,onEnd:aiSavePri});
            new Sortable(document.getElementById('ai-ip'),{animation:150,onEnd:aiSavePri});
        }
    }).catch(function(e){c.innerHTML='<p class="text-red-500">Ошибка загрузки: '+e.message+'</p>';});
}

function aiSaveAll(){
    var data={
        odirouter_enabled:document.getElementById('ai_odi_en')?.checked||false,
        odirouter_api_key:document.getElementById('ai_odi_key')?.value||'',
        odirouter_image_api_key:document.getElementById('ai_odi_img_key')?.value||'',
        odirouter_text_model:document.getElementById('ai_odi_tm')?.value||'',
        odirouter_image_model:document.getElementById('ai_odi_im')?.value||'',
        yandex_gpt_enabled:document.getElementById('ai_ygpt_en')?.checked||false,
        yandex_gpt_api_key:document.getElementById('ai_ygpt_key')?.value||'',
        yandex_folder_id:document.getElementById('ai_yfid')?.value||'',
        yandex_art_enabled:document.getElementById('ai_yart_en')?.checked||false,
        gigachat_enabled:document.getElementById('ai_gc_en')?.checked||false,
        gigachat_auth_key:document.getElementById('ai_gc_key')?.value||'',
        gigachat_scope:document.getElementById('ai_gc_scope')?.value||'GIGACHAT_API_PERS',
        stability_enabled:document.getElementById('ai_st_en')?.checked||false,
        stability_api_key:document.getElementById('ai_st_key')?.value||'',
        chat_enabled:document.getElementById('ai_chat_en')?.checked||false,
        chat_model:document.getElementById('ai_chat_model')?.value||'',
        chat_rate_limit:parseInt(document.getElementById('ai_chat_limit')?.value||'30'),
        chat_title:document.getElementById('ai_chat_title')?.value||'',
        chat_greeting:document.getElementById('ai_chat_greeting')?.value||'',
        chat_system_prompt:document.getElementById('ai_chat_prompt')?.value||'',
        text_provider_priority:[...document.querySelectorAll('#ai-tp [data-provider]')].map(function(el){return el.dataset.provider;}),
        image_provider_priority:[...document.querySelectorAll('#ai-ip [data-provider]')].map(function(el){return el.dataset.provider;})
    };
    fetch(A+'/ai-providers',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(r=>r.json()).then(function(res){
        if(res.success){alert('✅ Настройки AI сохранены');lAIP();}
        else alert('Ошибка: '+(res.error||''),true);
    }).catch(function(e){alert('Ошибка: '+e.message,true);});
}

function aiSavePri(){
    var data={
        chat_enabled:document.getElementById('ai_chat_en')?.checked||false,
        chat_model:document.getElementById('ai_chat_model')?.value||'',
        chat_rate_limit:parseInt(document.getElementById('ai_chat_limit')?.value||'30'),
        chat_title:document.getElementById('ai_chat_title')?.value||'',
        chat_greeting:document.getElementById('ai_chat_greeting')?.value||'',
        chat_system_prompt:document.getElementById('ai_chat_prompt')?.value||'',
        text_provider_priority:[...document.querySelectorAll('#ai-tp [data-provider]')].map(function(el){return el.dataset.provider;}),
        image_provider_priority:[...document.querySelectorAll('#ai-ip [data-provider]')].map(function(el){return el.dataset.provider;})
    };
    fetch(A+'/ai-providers',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(r=>r.json()).then(function(){alert('Приоритет сохранён');}).catch(function(){});
}

function aiTest(provider,type){
    alert('Тестирование '+provider+'...');
    fetch(A+'/ai-providers',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'test',provider:provider,type:type})}).then(r=>r.json()).then(function(res){
        if(res.result?.success){
            if(type==='image'&&res.result.path){alert('✅ Изображение: '+res.result.path);window.open(res.result.path,'_blank');}
            else if(res.result.text){alert('✅ '+res.result.text.substring(0,80)+'...');}
            else alert('✅ Провайдер работает');
        }else{alert('❌ '+(res.result?.error||'Ошибка'),true);}
    }).catch(function(e){alert('Ошибка: '+e.message,true);});
}
</script>
<?php include __DIR__ . "/patch-email-funnel.php"; ?>
<?php include __DIR__ . "/patch-leads-su.php"; ?>
<?php include __DIR__ . "/patch-article-inline-cta-stats.php"; ?>
</body>
</html>

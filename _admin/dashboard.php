<?php
require_once __DIR__ . '/../includes/minify.php';
ob_start('minifyHtmlOutput');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Админ-панель — Космозайм</title>
<script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
<script>tailwind.config={theme:{extend:{colors:{primary:'#1a56db','primary-dark':'#1244af',accent:'#059669',danger:'#dc2626'}}}}</script>
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
<div class="bg-gray-900 text-white"><div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between"><div class="flex items-center space-x-3"><span class="text-2xl">⚙️</span><h1 class="text-lg font-bold">Админ-панель Космозайм</h1></div><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm mr-4">🔑 Сменить пароль</button><button onclick="clearCache()" class="text-gray-300 hover:text-white text-sm mr-4">🗑 Сбросить кэш</button><button onclick="clearApiCache()" class="text-gray-300 hover:text-white text-sm mr-4">⚡ API-кэш</button><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти →</button></div></div>
<div class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-4"><div class="flex space-x-4 overflow-x-auto">
<button onclick="sw('settings')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="settings">⚙️ Настройки</button>
<button onclick="sw('offers')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="offers">📋 Предложения</button>
<button onclick="sw('articles')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="articles">📰 Статьи</button>
<button onclick="sw('reviews')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="reviews">⭐ Отзывы</button>
<button onclick="sw('tags')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="tags">🏷️ Теги</button>
<button onclick="sw('geo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="geo">🌍 Гео-редиректы</button>
<button onclick="sw('cityseo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="cityseo">🏙️ SEO городов</button>
<button onclick="sw('stats')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="stats">📊 Статистика</button>
<button onclick="sw('conversions')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="conversions">💰 Конверсии</button>
<button onclick="sw('ab')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="ab">🧪 A/B тесты</button>
<button onclick="sw('subs')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="subs">📬 Подписчики</button>
<button onclick="sw('scheduler')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="scheduler">⏰ Планировщик</button>
<button onclick="sw('backup')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="backup">💾 Бэкап</button>
<button onclick="sw('users')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="users">👥 Пользователи</button>
<button onclick="sw('security')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="security">🔒 Безопасность</button>
</div></div></div>
<div class="bg-gray-50 border-b"><div class="max-w-7xl mx-auto px-4 py-3 text-sm text-gray-500" id="admin-breadcrumb">Админка</div></div>
<div class="max-w-7xl mx-auto px-4 py-8">
<div id="p-settings" class="tp hidden"></div>
<div id="p-offers" class="tp"></div>
<div id="p-articles" class="tp hidden"></div>
<div id="p-reviews" class="tp hidden"></div>
<div id="p-tags" class="tp hidden"></div>
<div id="p-geo" class="tp hidden"></div>
<div id="p-cityseo" class="tp hidden"></div>
<div id="p-stats" class="tp hidden"></div>
<div id="p-conversions" class="tp hidden"></div>
<div id="p-ab" class="tp hidden"></div>
<div id="p-subs" class="tp hidden"></div>
<div id="p-scheduler" class="tp hidden"></div>
<div id="p-backup" class="tp hidden"></div>
<div id="p-users" class="tp hidden"></div>
<div id="p-security" class="tp hidden"></div>
</div>
<div id="M"></div>
<script>
const A='/api/admin';
function ap(u,o){return fetch(A+u,{headers:{'Content-Type':'application/json'},...o}).then(r=>r.json());}
function e(s){if(!s)return'';let d=document.createElement('div');d.textContent=s;return d.innerHTML;}
const TAB_LABELS={settings:'Настройки',offers:'Предложения',articles:'Статьи',reviews:'Отзывы',tags:'Теги',geo:'Гео-редиректы',cityseo:'SEO городов',stats:'Статистика',conversions:'Конверсии',ab:'A/B тесты',subs:'Подписчики и рассылки',scheduler:'Планировщик',backup:'Бэкап',users:'Пользователи',security:'Безопасность'};
function sw(t){document.querySelectorAll('.tp').forEach(x=>x.classList.add('hidden'));document.getElementById('p-'+t).classList.remove('hidden');document.querySelectorAll('.tb').forEach(b=>{let a=b.dataset.t===t;b.classList.toggle('border-blue-600',a);b.classList.toggle('text-blue-600',a);b.classList.toggle('border-transparent',!a);b.classList.toggle('text-gray-500',!a);});var bc=document.getElementById('admin-breadcrumb');if(bc)bc.innerHTML='<a href="/admin" class="hover:text-blue-600">Админка</a> → <span class="text-gray-700">'+(TAB_LABELS[t]||t)+'</span>';({settings:lSet,offers:lO,articles:lA,reviews:lR,tags:lT,geo:lG,cityseo:lCS,stats:lS,conversions:lConv,ab:lAB,subs:lSu,scheduler:lSch,backup:lB,users:lUsers,security:lSec})[t]?.();}
function clearCache(){fetch('/admin/clear-cache').then(r=>r.json()).then(d=>{if(d.success)alert('✓ Кэш очищен');else alert('Ошибка');}).catch(()=>alert('Ошибка'));}
function clearApiCache(){fetch(A+'/clear-api-cache',{method:'POST'}).then(r=>r.json()).then(d=>{if(d.success)alert('✓ API-кэш очищен: '+d.cleared);else alert(d.error||'Ошибка');}).catch(()=>alert('Ошибка'));}
function logout(){fetch(A+'/logout',{method:'POST'}).then(()=>location.href='/admin/login');}
function modal(h){document.getElementById('M').innerHTML='<div class="modal-bg" onclick="if(event.target===this)cm()"><div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-2xl">'+h+'</div></div>';}
function cm(){document.getElementById('M').innerHTML='';}
const CL={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
const BL={any:'Любой',employed:'Работающий',unemployed:'Безработный',pensioner:'Пенсионер',student:'Студент',self_employed:'Самозанятый'};

/* ============ OFFERS ============ */
function lO(){ap('/offers').then(list=>{let h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">Предложения ('+list.length+')</h2><button onclick="oForm()" class="btn-p">+ Добавить</button></div>';
h+='<div class="bg-gray-50 rounded-lg p-2 mb-4 text-xs text-gray-500">💡 Перетаскивайте строки за ☰ для изменения порядка</div>';
h+='<div id="offers-sortable" class="space-y-2">';
list.forEach(o=>{h+='<div class="bg-white rounded-xl border p-4 flex items-center gap-4 cursor-move hover:shadow-sm transition-shadow" data-id="'+o.id+'"><span class="text-gray-300 cursor-grab drag-handle text-lg">☰</span>';
if(o.logo_url){var lg=o.logo_url;if(lg.indexOf("/public/")===0)lg=lg.substring(7);h+='<div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0"><img src="'+lg+'" class="w-full h-full object-contain p-0.5"></div>';}else{h+='<div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center flex-shrink-0">🏦</div>';}
h+='<div class="flex-1 min-w-0"><p class="font-semibold text-gray-900 text-sm">'+e(o.title)+'</p><p class="text-xs text-gray-500">'+(CL[o.category]||o.category)+' • '+o.rate+'% • '+Number(o.amount_min).toLocaleString()+'—'+Number(o.amount_max).toLocaleString()+' ₽</p></div>';
h+='<span class="px-2 py-0.5 rounded text-xs font-semibold '+(o.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(o.is_active?'Вкл':'Выкл')+'</span>';
h+='<button onclick="event.stopPropagation();oForm('+JSON.stringify(o).replace(/'/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-sm">Ред.</button>';
h+='<button onclick="event.stopPropagation();oD('+o.id+')" class="text-red-500 hover:underline text-sm">Уд.</button></div>';});
h+='</div>';document.getElementById('p-offers').innerHTML=h;
initSort('offers-sortable','offers');});}

function oForm(o){let f=o||{title:'',category:'microloans',amount_min:1000,amount_max:100000,term_min_days:1,term_max_days:365,psk:'0',rate:'0',free_term_days:0,logo_url:'',affiliate_url:'',borrower_category:'any',description:'',seo_keywords:'',regions:'',is_active:true,sort_order:0};let id=o?o.id:0;
let catOpts='',borOpts='';for(let k in CL)catOpts+='<option value="'+k+'"'+(f.category===k?' selected':'')+'>'+CL[k]+'</option>';for(let k in BL)borOpts+='<option value="'+k+'"'+(f.borrower_category===k?' selected':'')+'>'+BL[k]+'</option>';
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать':'Новое предложение')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div>'+
'<form onsubmit="return oS(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="of-t" class="input-f" value="'+e(f.title)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Категория</label><select id="of-c" class="sel-f">'+catOpts+'</select></div>'+
'<div><label class="block text-xs font-medium mb-1">Заёмщик</label><select id="of-b" class="sel-f">'+borOpts+'</select></div>'+
'<div><label class="block text-xs font-medium mb-1">Сумма от</label><input id="of-am1" type="number" class="input-f" value="'+f.amount_min+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сумма до</label><input id="of-am2" type="number" class="input-f" value="'+f.amount_max+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Срок от (дн)</label><input id="of-t1" type="number" class="input-f" value="'+f.term_min_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Срок до (дн)</label><input id="of-t2" type="number" class="input-f" value="'+f.term_max_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">ПСК %</label><input id="of-psk" type="number" step="0.01" class="input-f" value="'+f.psk+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Ставка %</label><input id="of-r" type="number" step="0.01" class="input-f" value="'+f.rate+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Без % (дн)</label><input id="of-fr" type="number" class="input-f" value="'+f.free_term_days+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сортировка</label><input id="of-so" type="number" class="input-f" value="'+f.sort_order+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">URL логотипа</label><input id="of-lo" class="input-f" value="'+e(f.logo_url||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Партнёрская ссылка *</label><input id="of-af" class="input-f" value="'+e(f.affiliate_url||'')+'" required></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Описание</label><textarea id="of-de" class="input-f" rows="3">'+e(f.description||'')+'</textarea></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO ключевые слова</label><input id="of-sk" class="input-f" value="'+e(f.seo_keywords||'')+'"></div>'+
'<div class="col-span-2"><label class="flex items-center gap-2"><input type="checkbox" id="of-ac" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активно</span></label></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-2">🏷️ Теги</label><div id="of-tags-box" class="flex flex-wrap gap-2"><span class="text-xs text-gray-400">Загрузка...</span></div></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
// Загружаем теги для оффера
Promise.all([ap('/tags'),ap('/tag-links?offerId='+(id||0))]).then(([allTags,linked])=>{
var box=document.getElementById('of-tags-box');if(!box)return;
var linArr=linked.map(Number);
box.innerHTML=allTags.filter(t=>t.category===f.category||!id).map(t=>'<label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer '+(linArr.includes(Number(t.id))?'bg-blue-50 border-blue-300':'bg-white border-gray-200')+' hover:border-blue-400"><input type="checkbox" class="of-tag-cb w-3.5 h-3.5" value="'+t.id+'"'+(linArr.includes(Number(t.id))?' checked':'')+'> '+(t.icon||'🏷️')+' '+e(t.title)+'</label>').join('');
if(!allTags.length)box.innerHTML='<span class="text-xs text-gray-400">Нет тегов. Создайте на вкладке 🏷️ Теги</span>';
});}

function oS(ev,id){ev.preventDefault();let d={title:document.getElementById('of-t').value,category:document.getElementById('of-c').value,amountMin:document.getElementById('of-am1').value,amountMax:document.getElementById('of-am2').value,termMinDays:document.getElementById('of-t1').value,termMaxDays:document.getElementById('of-t2').value,psk:document.getElementById('of-psk').value,rate:document.getElementById('of-r').value,freeTermDays:document.getElementById('of-fr').value,logoUrl:document.getElementById('of-lo').value,affiliateUrl:document.getElementById('of-af').value,borrowerCategory:document.getElementById('of-b').value,description:document.getElementById('of-de').value,seoKeywords:document.getElementById('of-sk').value,isActive:document.getElementById('of-ac').checked,sortOrder:document.getElementById('of-so').value};ap(id?'/offers/'+id:'/offers',{method:id?'PUT':'POST',body:JSON.stringify(d)}).then(r=>{
var oid=id||r.id;
var tagIds=Array.from(document.querySelectorAll('.of-tag-cb:checked')).map(x=>Number(x.value));
return ap('/tag-links',{method:'POST',body:JSON.stringify({offerId:oid,tagIds:tagIds})});
}).then(()=>{cm();lO();});return false;}
function oD(id){if(confirm('Удалить?'))ap('/offers/'+id,{method:'DELETE'}).then(()=>lO());}

/* ============ ARTICLES ============ */
let aTopics=[],aAi={};
function lA(){ap('/generate-article').then(d=>{aTopics=d.topics||[];aAi=d.aiStatus||{};});
ap('/articles').then(list=>{let h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">Статьи ('+list.length+')</h2><div class="flex gap-2"><button onclick="aGen()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">🤖 Автогенерация</button><button onclick="aForm()" class="btn-p">+ Добавить</button></div></div>';
h+='<div class="bg-white rounded-xl shadow-sm border overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3">Заголовок</th><th class="text-left px-4 py-3">Дата</th><th class="text-left px-4 py-3">Статус</th><th class="text-right px-4 py-3">Действия</th></tr></thead><tbody>';
list.forEach(a=>{h+='<tr class="border-b hover:bg-gray-50"><td class="px-4 py-3 font-medium">'+e(a.title)+'</td><td class="px-4 py-3 text-gray-500">'+new Date(a.created_at).toLocaleDateString('ru-RU')+'</td><td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(a.is_published?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700')+'">'+(a.is_published?'Опубликовано':'Черновик')+'</span></td><td class="px-4 py-3 text-right space-x-2"><a href="/articles/'+e(a.slug)+'" target="_blank" class="text-gray-400 hover:text-gray-600">👁</a> <button onclick=\'aForm('+JSON.stringify(a).replace(/\x27/g,"&#39;")+')\' class="text-blue-600 hover:underline text-sm">Ред.</button> <button onclick="aToggle('+a.id+','+(!a.is_published)+')" class="text-blue-500 hover:underline text-sm">'+(a.is_published?'Скрыть':'Опубл.')+'</button> <button onclick="aD('+a.id+')" class="text-red-500 hover:underline text-sm">Удалить</button></td></tr>';});
h+='</tbody></table></div>';document.getElementById('p-articles').innerHTML=h;});}

function aGen(){let cats='<option value="">Случайная</option>';aTopics.forEach(t=>{var avail=t.themes?t.themes.length:0;var total=t.total||avail;var used=t.used||0;var label=t.category.charAt(0).toUpperCase()+t.category.slice(1);if(avail>0)cats+='<option value="'+t.category+'">'+label+' ('+avail+' из '+total+' доступно)</option>';else cats+='<option value="'+t.category+'">'+label+' — темы закончились, AI создаст новые</option>';});
let badges=(aAi.yandexGPT?'<span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded ml-2">YandexGPT</span>':'')+(aAi.yandexART?'<span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded ml-1">YandexART</span>':'');
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🤖 Автогенерация'+badges+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Категория</label><select id="ag-c" class="sel-f" onchange="agUpd()">'+cats+'</select></div><div><label class="block text-sm font-medium mb-1">Тема из списка</label><select id="ag-t" class="sel-f"><option value="">Случайная</option></select></div><div><label class="block text-sm font-medium mb-1">Или своя тема</label><input id="ag-cu" class="input-f" placeholder="Введите свою тему"></div><div class="bg-blue-50 p-3 rounded-lg text-sm text-blue-700"><p class="font-medium mb-1">ℹ️ Как работает:</p><p class="text-xs">Текст: '+(aAi.yandexGPT?'YandexGPT':'шаблон')+' • Картинка: '+(aAi.yandexART?'YandexART':'нет')+' • Черновик • До 90 сек</p><p class="text-xs mt-1">🏦 МФО — обзор организации • Если темы закончились — AI сгенерирует новые автоматически</p></div><div class="bg-green-50 p-3 rounded-lg text-sm"><button type="button" onclick="agNewTopics()" id="ag-newtopics" class="text-green-800 font-semibold hover:underline">🔄 Сгенерировать 10 новых тем через AI</button><span id="ag-newtopics-status" class="ml-2 text-green-600"></span></div></div><div class="flex justify-end gap-3 mt-6"><button onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button onclick="agDo()" id="ag-btn" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-semibold">🚀 Сгенерировать</button></div>');}



function agUpd(){let c=document.getElementById('ag-c').value,s=document.getElementById('ag-t');s.innerHTML='<option value="">Случайная</option>';if(c){let g=aTopics.find(t=>t.category===c);if(g)g.themes.forEach(th=>{s.innerHTML+='<option value="'+th+'">'+th+'</option>';});}}

function agDo(){let cu=document.getElementById('ag-cu').value.trim(),tp=cu||document.getElementById('ag-t').value,ct=document.getElementById('ag-c').value,b=document.getElementById('ag-btn');b.disabled=true;b.textContent='⏳ Генерация...';
ap('/generate-article',{method:'POST',body:JSON.stringify({topic:tp||null,category:ct||null})}).then(d=>{cm();if(d.success){let im=d.hasImage?'\n📷 Обложка: YandexART':'\n📷 Без обложки';alert('Статья "'+d.article.title+'" создана!\n🤖 '+d.aiProvider+im);}else alert('Ошибка: '+(d.error||''));lA();}).catch(()=>{alert('Ошибка');b.disabled=false;b.textContent='🚀 Сгенерировать';});}

function aForm(a){let f=a||{title:'',excerpt:'',content:'',meta_title:'',meta_description:'',cover_image:'',is_published:false};let id=a?a.id:0;
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать статью':'Новая статья')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">✕</button></div><form onsubmit="return aS(event,'+id+')"><div class="space-y-3"><div><label class="block text-xs font-medium mb-1">Заголовок *</label><input id="af-t" class="input-f" value="'+e(f.title)+'" required></div><div><label class="block text-xs font-medium mb-1">Краткое описание</label><textarea id="af-ex" class="input-f" rows="2">'+e(f.excerpt||'')+'</textarea></div><div><label class="block text-xs font-medium mb-1">Содержание *</label><textarea id="af-co" class="input-f" rows="10" required>'+e(f.content)+'</textarea></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Meta Title</label><input id="af-mt" class="input-f" value="'+e(f.meta_title||'')+'"></div><div><label class="block text-xs font-medium mb-1">Обложка URL</label><input id="af-ci" class="input-f" value="'+e(f.cover_image||'')+'"></div></div><div><label class="block text-xs font-medium mb-1">Meta Description</label><textarea id="af-md" class="input-f" rows="2">'+e(f.meta_description||'')+'</textarea></div><div><label class="flex items-center gap-2"><input type="checkbox" id="af-pu" '+(f.is_published?'checked':'')+' class="w-4 h-4"><span class="text-sm">Опубликовать</span></label></div></div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');}

function aS(ev,id){ev.preventDefault();let d={title:document.getElementById('af-t').value,excerpt:document.getElementById('af-ex').value,content:document.getElementById('af-co').value,metaTitle:document.getElementById('af-mt').value,metaDescription:document.getElementById('af-md').value,coverImage:document.getElementById('af-ci').value,isPublished:document.getElementById('af-pu').checked};ap(id?'/articles/'+id:'/articles',{method:id?'PUT':'POST',body:JSON.stringify(d)}).then(()=>{cm();lA();});return false;}
function aToggle(id,v){ap('/articles/'+id,{method:'PUT',body:JSON.stringify({isPublished:v})}).then(()=>lA());}
function aD(id){if(confirm('Удалить?'))ap('/articles/'+id,{method:'DELETE'}).then(()=>lA());}

/* ============ REVIEWS ============ */
function lR(){ap('/reviews').then(list=>{let pend=list.filter(r=>!r.is_approved).length;let h='<div class="flex justify-between mb-6"><div class="flex items-center gap-4"><h2 class="text-xl font-bold">Отзывы ('+list.length+')</h2>'+(pend?'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-sm">'+pend+' на модерации</span>':'')+'</div><button onclick="rGen()" class="btn-p">🤖 Сгенерировать</button></div><div class="space-y-3">';
list.forEach(r=>{let st='';for(let i=1;i<=5;i++)st+='<span class="'+(i<=r.rating?'text-yellow-400':'text-gray-300')+'">★</span>';
h+='<div class="bg-white rounded-xl border p-4 '+(r.is_approved?'':'border-yellow-200 bg-yellow-50/50')+'"><div class="flex justify-between"><div class="flex-1"><div class="flex items-center gap-2 mb-1"><span class="font-semibold">'+e(r.author_name)+'</span><span>'+st+'</span><span class="text-xs text-gray-400">'+new Date(r.created_at).toLocaleDateString('ru-RU')+'</span></div><p class="text-sm text-gray-500">'+e(r.offer_title||'—')+'</p><p class="text-gray-700 mt-1">'+e(r.comment)+'</p></div><div class="flex flex-col gap-1 ml-4">'+(r.is_approved?'<button onclick="rA('+r.id+',false)" class="text-sm bg-gray-100 px-3 py-1 rounded">Скрыть</button>':'<button onclick="rA('+r.id+',true)" class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded">✓ Одобрить</button>')+'<button onclick="rD('+r.id+')" class="text-sm text-red-500">Удалить</button></div></div></div>';});
h+='</div>';document.getElementById('p-reviews').innerHTML=h;});}
function rGen(){ap('/generate-review',{method:'POST'}).then(d=>{if(d.success)alert(d.review.name+' → '+d.review.offer+' ('+d.review.rating+'/5)');lR();});}
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

/* ============ TAGS ============ */
var TG_CAT={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
var TG_CAT_URLS={microloans:'/zajmy',credits:'/kredity',credit_cards:'/karty/kreditnye',debit_cards:'/karty/debetovye'};
function tUrl(t){return (TG_CAT_URLS[t.category]||'/zajmy')+'/type/'+t.slug;}

function lT(){ap('/tags').then(tags=>{
var h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">🏷️ Теги / Типы предложений ('+tags.length+')</h2><button onclick="tForm()" class="btn-p">+ Добавить</button></div>';
if(!tags.length){h+='<p class="text-gray-500 text-center py-8">Нет тегов. Добавьте первый!</p>';}
else{
h+='<div class="bg-gray-50 rounded-lg p-2 mb-4 text-xs text-gray-500">💡 Перетаскивайте за ☰ для изменения порядка</div>';
h+='<div id="tags-sortable" class="space-y-2">';
tags.forEach(t=>{
h+='<div class="bg-white rounded-xl border p-4 flex items-center gap-4 cursor-move hover:shadow-sm transition-shadow" data-id="'+t.id+'">';
h+='<span class="text-gray-300 cursor-grab drag-handle text-lg">☰</span>';
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
h+='<div><span class="text-gray-400">Title:</span> <span class="text-blue-700">'+(e(t.h1)||e(t.title))+' — Космозайм</span></div>';
h+='<div><span class="text-gray-400">Description:</span> <span class="text-green-700">'+(e(t.meta_description)||e(t.description)||'—')+'</span></div>';
h+='<div><span class="text-gray-400">URL:</span> <span class="font-mono text-gray-600">'+url+'</span></div>';
h+='<div><span class="text-gray-400">Статус:</span> '+(t.is_active?'<span class="text-green-600">Активен</span>':'<span class="text-red-500">Выключен</span>')+'</div></div>';
modal(h);}

function tForm(t){
var f=t||{title:'',slug:'',h1:'',description:'',meta_description:'',content:'',icon:'🏷️',category:'microloans',features:'[]',is_active:true,sort_order:0};
var id=t?t.id:0;
var catOpts='';for(var k in TG_CAT)catOpts+='<option value="'+k+'"'+(f.category===k?' selected':'')+'>'+TG_CAT[k]+'</option>';
var feat=f.features||'[]';if(typeof feat==='string')try{feat=JSON.parse(feat);}catch(e){feat=[];}
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать тег':'Новый тег')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return tS(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div><label class="block text-xs font-medium mb-1">Иконка (эмодзи)</label><input id="tg-icon" class="input-f" value="'+e(f.icon||'🏷️')+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Категория</label><select id="tg-cat" class="sel-f">'+catOpts+'</select></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="tg-title" class="input-f" value="'+e(f.title)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Slug (авто)</label><input id="tg-slug" class="input-f" value="'+e(f.slug)+'" placeholder="авто из названия"></div>'+
'<div><label class="block text-xs font-medium mb-1">Порядок</label><input id="tg-sort" type="number" class="input-f" value="'+f.sort_order+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">H1 заголовок</label><input id="tg-h1" class="input-f" value="'+e(f.h1||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Короткое описание</label><input id="tg-desc" class="input-f" value="'+e(f.description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="tg-meta" class="input-f" value="'+e(f.meta_description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO текст</label><textarea id="tg-content" class="input-f" rows="3">'+e(f.content||'')+'</textarea></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Фичи (JSON) <span class="text-gray-400">[{"icon":"⚡","title":"...","text":"..."}]</span></label><textarea id="tg-feat" class="input-f font-mono text-xs" rows="3">'+e(JSON.stringify(feat,null,2))+'</textarea></div>'+
'<div class="col-span-2"><label class="flex items-center gap-2"><input type="checkbox" id="tg-active" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активен</span></label></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-2">📋 Привязанные предложения</label><div id="tg-offers-box" class="flex flex-wrap gap-2"><span class="text-xs text-gray-400">Загрузка...</span></div></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
// Загружаем офферы для тега
Promise.all([ap('/offers'),ap('/tag-links?tagId='+(id||0))]).then(([allOffers,linked])=>{
var box=document.getElementById('tg-offers-box');if(!box)return;
var linArr=linked.map(Number);
var filtered=allOffers.filter(o=>o.category===f.category);
box.innerHTML=filtered.map(o=>'<label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-sm cursor-pointer '+(linArr.includes(Number(o.id))?'bg-green-50 border-green-300':'bg-white border-gray-200')+' hover:border-green-400"><input type="checkbox" class="tg-off-cb w-3.5 h-3.5" value="'+o.id+'"'+(linArr.includes(Number(o.id))?' checked':'')+'> '+e(o.title)+'</label>').join('');
if(!filtered.length)box.innerHTML='<span class="text-xs text-gray-400">Нет предложений этой категории</span>';
});
}

function tS(ev,id){ev.preventDefault();
var feat='[]';try{feat=document.getElementById('tg-feat').value;JSON.parse(feat);}catch(e){alert('Неверный JSON в фичах');return false;}
var body={title:document.getElementById('tg-title').value,slug:document.getElementById('tg-slug').value,h1:document.getElementById('tg-h1').value,description:document.getElementById('tg-desc').value,metaDescription:document.getElementById('tg-meta').value,content:document.getElementById('tg-content').value,icon:document.getElementById('tg-icon').value,category:document.getElementById('tg-cat').value,features:feat,isActive:document.getElementById('tg-active').checked,sortOrder:parseInt(document.getElementById('tg-sort').value)||0};
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
function lCS(){
var cat=_csCat;
ap('/city-seo?category='+cat).then(list=>{
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">🏙️ SEO-тексты для городов</h2><div class="flex gap-2">';
h+='<select id="cs-cat" onchange="_csCat=this.value;lCS()" class="sel-f text-sm w-auto"><option value="microloans"'+(cat==='microloans'?' selected':'')+'>Займы</option><option value="credits"'+(cat==='credits'?' selected':'')+'>Кредиты</option><option value="credit_cards"'+(cat==='credit_cards'?' selected':'')+'>Кредитные карты</option><option value="debit_cards"'+(cat==='debit_cards'?' selected':'')+'>Дебетовые карты</option></select>';
h+='<button onclick="csGen(false)" class="btn-p text-sm" id="cs-gen-btn">⚡ Шаблоны</button>';
h+='<button onclick="csGen(true)" class="bg-purple-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-purple-700" id="cs-gpt-btn">🤖 YandexGPT</button>';
h+='<button onclick="csClean()" class="bg-gray-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold hover:bg-gray-700">🧹 Очистить</button>';
h+='</div></div>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-700">';
h+='<strong>Как работает:</strong> ⚡ Шаблоны — мгновенная генерация из готовых текстов (бесплатно). 🤖 YandexGPT — уникальные AI-тексты (нужен API-ключ в настройках). Тексты автоматически подставляются на страницы городов.';
h+='</div>';

h+='<p class="text-sm text-gray-500 mb-4">Сгенерировано: <strong>'+list.length+'</strong> из 41 города</p>';

if(list.length){
h+='<div class="bg-white rounded-xl border overflow-hidden"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-3 text-left">Город</th><th class="p-3 text-left">H1</th><th class="p-3 text-left w-20">Способ</th><th class="p-3 text-right">Действия</th></tr></thead><tbody>';
list.forEach(s=>{
var badge=s.generated_by==='yandexgpt'?'<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">🤖 GPT</span>':s.generated_by==='manual'?'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">✏️ Ручной</span>':'<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">⚡ Шаблон</span>';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-medium">'+e(s.city_slug)+'</td><td class="p-3 text-gray-600 text-xs">'+e((s.seo_h1||'').substring(0,60))+'...</td><td class="p-3">'+badge+'</td><td class="p-3 text-right"><button onclick="csEdit('+s.id+','+JSON.stringify(s).replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-sm mr-2">Ред.</button><button onclick="csDel('+s.id+')" class="text-red-500 hover:underline text-sm">Уд.</button></td></tr>';
});
h+='</tbody></table></div>';
}else{
h+='<div class="text-center py-12 bg-white rounded-xl border"><p class="text-gray-500">Нет сгенерированных текстов. Нажмите ⚡ Шаблоны для генерации.</p></div>';
}
document.getElementById('p-cityseo').innerHTML=h;
});}

function csGen(useGPT){
var btn=document.getElementById(useGPT?'cs-gpt-btn':'cs-gen-btn');
var oldText=btn.textContent;
btn.disabled=true;btn.textContent='⏳ Генерация...';
ap('/city-seo/generate',{method:'POST',body:JSON.stringify({category:_csCat,useGPT:useGPT,overwrite:false})}).then(d=>{
btn.disabled=false;btn.textContent=oldText;
if(d.success)alert('Сгенерировано: '+d.generated+' из '+d.total);
else alert(d.error||'Ошибка');
lCS();
}).catch(()=>{btn.disabled=false;btn.textContent=oldText;alert('Ошибка');});}

function csClean(){if(!confirm('Очистить все тексты от markdown-мусора (```html``` и пр.)?'))return;ap('/city-seo/clean',{method:'POST'}).then(d=>{if(d.success)alert('Очищено: '+d.cleaned+' из '+d.total);lCS();});}
function csEdit(id,s){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать SEO: '+e(s.city_slug)+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return csSave(event,'+id+')">'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">H1</label><input id="cs-h1" class="input-f" value="'+e(s.seo_h1||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Meta Description</label><input id="cs-meta" class="input-f" value="'+e(s.meta_description||'')+'"></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">SEO-текст (HTML)</label><textarea id="cs-text" class="input-f font-mono text-xs" rows="12">'+e(s.seo_text||'')+'</textarea></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
}

function csSave(ev,id){ev.preventDefault();
ap('/city-seo/'+id,{method:'PUT',body:JSON.stringify({seoH1:document.getElementById('cs-h1').value,seoText:document.getElementById('cs-text').value,metaDescription:document.getElementById('cs-meta').value})}).then(()=>{cm();lCS();});return false;}

function csDel(id){if(confirm('Удалить SEO-текст? (будет регенерирован автоматически)'))ap('/city-seo/'+id,{method:'DELETE'}).then(()=>lCS());}


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



/* ============ CONVERSIONS / POSTBACK ============ */
var _convPeriod=30;
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

// Postback URL подсказка
h+='<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6"><h4 class="font-semibold text-blue-800 text-sm mb-2">🔗 Postback URL для leads.su</h4><p class="text-xs text-blue-700 mb-2">Скопируйте и вставьте в <strong>Инструменты → Глобальный postback</strong> в leads.su:</p><div class="bg-white rounded-lg p-3 font-mono text-xs text-gray-800 break-all select-all border">'+location.origin+'/api/postback?click_id={click_id}&status={status}&payout={payout}&ip={ip}&offer_id={offer_id}&transaction_id={transaction_id}&aff_sub={aff_sub}&goal_id={goal_id}</div></div>';

// По офферам
if(d.byOffer&&d.byOffer.length){
h+='<div class="bg-white rounded-xl border mb-6"><div class="p-4 border-b"><h3 class="font-semibold">По офферам</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Оффер</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Кол-во</th><th class="p-3 text-right">Сумма</th></tr></thead><tbody>';
d.byOffer.forEach(r=>{
var stBadge=r.status==='approved'?'bg-green-100 text-green-700':r.status==='rejected'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-medium">'+e(r.title||'—')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+stBadge+'">'+e(r.status)+'</span></td><td class="p-3 text-right">'+r.cnt+'</td><td class="p-3 text-right font-semibold">'+Number(r.sum_payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</td></tr>';});
h+='</tbody></table></div></div>';}

// Список конверсий
if(d.conversions&&d.conversions.length){
h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b"><h3 class="font-semibold">Последние конверсии</h3></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Оффер</th><th class="p-3 text-left">Статус</th><th class="p-3 text-right">Выплата</th><th class="p-3 text-left">IP конверсии</th><th class="p-3 text-left text-xs">Click ID</th></tr></thead><tbody>';
d.conversions.forEach(c=>{
var stBadge=c.status==='approved'?'bg-green-100 text-green-700':c.status==='rejected'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 text-xs text-gray-500 whitespace-nowrap">'+new Date(c.created_at).toLocaleString('ru-RU')+'</td><td class="p-3 font-medium text-sm">'+e(c.offer_title||c.external_offer_id||'—')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+stBadge+'">'+e(c.status)+'</span></td><td class="p-3 text-right font-semibold">'+Number(c.payout||0).toLocaleString('ru-RU',{minimumFractionDigits:2})+' ₽</td><td class="p-3 font-mono text-xs text-gray-500">'+(e(c.ip)||'—')+'</td><td class="p-3 font-mono text-xs text-gray-400">'+(e(c.click_id||c.aff_sub||'—').substring(0,16))+'</td></tr>';});
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
h+='<div class="flex items-center justify-between mb-4"><div><h3 class="font-bold text-gray-900">'+e(t.name)+'</h3><p class="text-xs text-gray-400">Создан: '+new Date(t.created_at).toLocaleDateString('ru-RU')+'</p></div>';
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

function abForm(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Новый A/B тест</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return abSave(event)">'+
'<div class="mb-4"><label class="block text-xs font-medium mb-1">Название теста</label><input id="ab-name" class="input-f" value="Тест кнопки" required></div>'+
'<div class="mb-4"><label class="block text-xs font-medium mb-2">Варианты кнопки</label><div id="ab-vars">'+
'<div class="flex gap-2 mb-2"><input class="input-f flex-1 ab-label" value="Оформить" placeholder="Текст кнопки"><input type="color" class="ab-color w-12 h-9 rounded cursor-pointer" value="#059669"><button type="button" onclick="this.closest(\'.flex\').remove()" class="text-red-400 hover:text-red-600">&times;</button></div>'+
'<div class="flex gap-2 mb-2"><input class="input-f flex-1 ab-label" value="Получить деньги" placeholder="Текст кнопки"><input type="color" class="ab-color w-12 h-9 rounded cursor-pointer" value="#1a56db"><button type="button" onclick="this.closest(\'.flex\').remove()" class="text-red-400 hover:text-red-600">&times;</button></div>'+
'</div><button type="button" onclick="abAddVar()" class="text-sm text-blue-600 hover:underline mb-4">+ Добавить вариант</button></div>'+
'<div class="flex justify-end gap-3"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Создать и активировать</button></div></form>');
}
function abAddVar(){
document.getElementById('ab-vars').insertAdjacentHTML('beforeend','<div class="flex gap-2 mb-2"><input class="input-f flex-1 ab-label" placeholder="Текст кнопки"><input type="color" class="ab-color w-12 h-9 rounded cursor-pointer" value="#059669"><button type="button" onclick="this.closest(\'.flex\').remove()" class="text-red-400 hover:text-red-600">&times;</button></div>');}
function abSave(ev){ev.preventDefault();
var vars=[];document.querySelectorAll('#ab-vars .flex').forEach(function(row){
var label=row.querySelector('.ab-label').value.trim();
var color=row.querySelector('.ab-color').value;
if(label)vars.push({label:label,color:color});});
if(vars.length<2){alert('Нужно минимум 2 варианта');return false;}
ap('/ab-tests',{method:'POST',body:JSON.stringify({name:document.getElementById('ab-name').value,isActive:true,variants:vars})}).then(function(){cm();lAB();});return false;}
function abToggle(id,v){ap('/ab-tests/'+id,{method:'PUT',body:JSON.stringify({isActive:!!v})}).then(function(){lAB();});}
function abReset(id){if(confirm('Сбросить счётчики?'))ap('/ab-tests/'+id+'/reset',{method:'POST'}).then(function(){lAB();});}
function abDel(id){if(confirm('Удалить тест?'))ap('/ab-tests/'+id,{method:'DELETE'}).then(function(){lAB();});}


/* ============ SUBSCRIBERS & NEWSLETTERS ============ */
function lSu(){
Promise.all([ap('/subscribers'),ap('/newsletters')]).then(([subs,nls])=>{
var active=subs.filter(s=>s.is_active);
var h='<div class="flex justify-between items-center mb-6"><h2 class="text-xl font-bold">📬 Подписчики и рассылки</h2><div class="flex gap-2"><span class="text-sm text-gray-500 mt-1">Активных: <strong>'+active.length+'</strong> из '+subs.length+'</span></div></div>';

// Рассылки
h+='<div class="bg-white rounded-xl border shadow-sm p-6 mb-6"><div class="flex justify-between items-center mb-4"><h3 class="font-bold text-gray-900">✉️ Рассылки</h3><button onclick="nlForm()" class="btn-p text-sm">+ Создать рассылку</button></div>';
if(nls.length){
h+='<div class="space-y-3">';
nls.forEach(n=>{
var st={draft:'<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">Черновик</span>',sending:'<span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs">Отправка...</span>',sent:'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">Отправлено</span>',failed:'<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs">Ошибка</span>'};
h+='<div class="bg-gray-50 rounded-lg border p-4"><div class="flex items-center justify-between mb-2"><div class="flex-1 min-w-0"><p class="font-semibold text-sm text-gray-900">'+e(n.subject||'Без темы')+'</p><p class="text-xs text-gray-400">'+new Date(n.created_at).toLocaleString('ru-RU')+(n.sent_at?' • Отправлено: '+new Date(n.sent_at).toLocaleString('ru-RU'):'')+'</p></div><div class="flex items-center gap-2">'+((st[n.status]||''))+'</div></div>';
h+='<div class="flex items-center gap-2 mt-2">';
if(n.status==='sent')h+='<span class="text-xs text-gray-500">✅ '+n.sent_count+' доставлено'+(n.failed_count>0?' / ❌ '+n.failed_count+' ошибок':'')+'</span>';
if(n.status==='sent')h+='<button onclick="nlStats('+n.id+')" class="text-blue-600 hover:underline text-xs">📊 Стат</button>';
if(n.status==='draft'){h+='<button onclick="nlForm('+JSON.stringify(n).replace(/\x27/g,"&#39;").replace(/"/g,"&quot;")+')" class="text-blue-600 hover:underline text-xs">Ред.</button>';h+='<button onclick="nlSend('+n.id+')" class="text-green-600 hover:underline text-xs">📤 Отправить</button>';}
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
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 font-mono text-xs">'+e(s.email)+'</td><td class="p-3 text-xs text-gray-500">'+new Date(s.subscribed_at).toLocaleDateString('ru-RU')+'</td><td class="p-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold '+(s.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(s.is_active?'Активен':'Отписан')+'</span></td>';
h+='<td class="p-3 text-right space-x-2">';
h+='<button onclick="subToggle('+s.id+','+(s.is_active?0:1)+')" class="text-blue-600 hover:underline text-xs">'+(s.is_active?'Отключить':'Включить')+'</button>';
h+='<button onclick="subDel('+s.id+',\''+e(s.email)+'\')" class="text-red-500 hover:underline text-xs">Удалить</button>';
h+='</td></tr>';});
h+='</tbody></table></div>';
}else{h+='<p class="p-4 text-gray-500 text-sm">Нет подписчиков</p>';}
h+='</div>';

document.getElementById('p-subs').innerHTML=h;});}

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
var brandHeader='<div style="margin:0 0 24px 0;text-align:center;background:#f8fafc;border-radius:12px;overflow:hidden"><img src="https://kosmozaim.ru/images/kosmo-rassil.jpg" alt="Космозайм" style="display:block;width:100%;max-width:600px;height:auto;border:0;margin:0 auto"></div>';
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
'<div class="border rounded-xl p-6 bg-white" style="max-width:600px;margin:0 auto;font-family:-apple-system,sans-serif">'+body+'<br><hr style="border:none;border-top:1px solid #eee;margin:24px 0"><p style="font-size:12px;color:#999;text-align:center">Вы получили это письмо от Космозайм.<br><a href="#" style="color:#999">Отписаться от рассылки</a></p></div>');}
function nlDel(id){if(confirm('Удалить рассылку?'))ap('/newsletters/'+id,{method:'DELETE'}).then(()=>lSu());}
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



/* ============ SETTINGS ============ */
var siteSettings={};
function lSet(){ap('/settings').then(d=>{
siteSettings=d.settings||{};
var h='<h2 class="text-xl font-bold mb-6">⚙️ Настройки сайта</h2>';
h+='<form onsubmit="return setSave(event)" class="space-y-6">';

// Основные
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">🌐 Основные настройки</h3>';
h+='<div class="grid md:grid-cols-2 gap-4">';
h+='<div><label class="block text-sm font-medium mb-1">Название сайта</label><input type="text" id="set-name" class="input-f" value="'+e(siteSettings.site_name||'Космозайм')+'" placeholder="Космозайм"></div>';
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
h+='</div>';

// YandexGPT
h+='<div class="bg-white rounded-xl border p-6"><h3 class="text-lg font-bold mb-4">🤖 Yandex GPT (генерация текстов и картинок)</h3>';
h+='<div class="grid md:grid-cols-2 gap-4">';
h+='<div><label class="block text-sm font-medium mb-1">API Key</label><input type="text" id="set-gpt-key" class="input-f font-mono text-sm" value="'+e(siteSettings.yandex_gpt_api_key_masked||siteSettings.yandex_gpt_api_key||'')+'" placeholder="AQVN..."></div>';
h+='<div><label class="block text-sm font-medium mb-1">Folder ID</label><input type="text" id="set-folder" class="input-f font-mono text-sm" value="'+e(siteSettings.yandex_folder_id||'')+'" placeholder="b1g..."></div>';
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
yandex_gpt_api_key:document.getElementById('set-gpt-key').value,
yandex_folder_id:document.getElementById('set-folder').value,
yandex_metrika_id:document.getElementById('set-metrika').value,
google_analytics_id:document.getElementById('set-ga').value
};
ap('/settings',{method:'POST',body:JSON.stringify(data)}).then(d=>{
if(d.success){alert('✅ Настройки сохранены!\n\nРекомендуем сбросить кэш страниц.');lSet();}
else alert('❌ '+(d.error||'Ошибка'));
});return false;}

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
function lUsers(){ap('/users').then(users=>{
var h='<h2 class="text-xl font-bold mb-6">👥 Пользователи ('+users.length+')</h2>';
if(!users.length){h+='<p class="text-gray-500 text-center py-8">Нет зарегистрированных пользователей</p>';}
else{
h+='<div class="bg-white rounded-xl border overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="p-3 text-left">Email</th><th class="p-3 text-left">Имя</th><th class="p-3 text-left">Заявки</th><th class="p-3 text-left">Одобрено</th><th class="p-3 text-left">Последний IP</th><th class="p-3 text-left">Регистрация</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
users.forEach(u=>{
h+='<tr class="border-t hover:bg-gray-50">';
h+='<td class="p-3 font-mono text-xs">'+e(u.email)+'</td>';
h+='<td class="p-3">'+e(u.name||'—')+'</td>';
h+='<td class="p-3 font-semibold">'+(u.app_count||0)+'</td>';
h+='<td class="p-3 text-green-600 font-semibold">'+(u.approved_count||0)+'</td>';
h+='<td class="p-3 font-mono text-xs text-gray-500">'+e(u.last_login_ip||'—')+'</td>';
h+='<td class="p-3 text-xs text-gray-500">'+new Date(u.created_at).toLocaleDateString('ru-RU')+'</td>';
h+='<td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(u.is_verified?'bg-green-100 text-green-700':'bg-yellow-100 text-yellow-700')+'">'+(u.is_verified?'Подтверждён':'Не подтв.')+'</span></td>';
h+='</tr>';});
h+='</tbody></table></div>';}
document.getElementById('p-users').innerHTML=h;});}

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

</script>
</body>
</html>

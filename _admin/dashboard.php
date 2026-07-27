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
<div class="bg-gray-900 text-white"><div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between"><div class="flex items-center space-x-3"><span class="text-2xl">⚙️</span><h1 class="text-lg font-bold">Админ-панель Космозайм</h1></div><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm mr-4">🔑 Сменить пароль</button><button onclick="clearCache()" class="text-gray-300 hover:text-white text-sm mr-4">🗑 Сбросить кэш</button><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти →</button></div></div>
<div class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-4"><div class="flex space-x-4 overflow-x-auto">
<button onclick="sw('settings')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="settings">⚙️ Настройки</button>
<button onclick="sw('offers')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="offers">📋 Предложения</button>
<button onclick="sw('articles')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="articles">📰 Статьи</button>
<button onclick="sw('reviews')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="reviews">⭐ Отзывы</button>
<button onclick="sw('tags')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="tags">🏷️ Теги</button>
<button onclick="sw('geo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="geo">🌍 Гео-редиректы</button>
<button onclick="sw('cityseo')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="cityseo">🏙️ SEO городов</button>
<button onclick="sw('stats')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="stats">📊 Статистика</button>
<button onclick="sw('subs')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="subs">📬 Подписчики</button>
<button onclick="sw('scheduler')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="scheduler">⏰ Планировщик</button>
<button onclick="sw('backup')" class="tb py-4 px-1 border-b-2 text-sm font-medium whitespace-nowrap" data-t="backup">💾 Бэкап</button>
</div></div></div>
<div class="max-w-7xl mx-auto px-4 py-8">
<div id="p-settings" class="tp hidden"></div>
<div id="p-offers" class="tp"></div>
<div id="p-articles" class="tp hidden"></div>
<div id="p-reviews" class="tp hidden"></div>
<div id="p-tags" class="tp hidden"></div>
<div id="p-geo" class="tp hidden"></div>
<div id="p-cityseo" class="tp hidden"></div>
<div id="p-stats" class="tp hidden"></div>
<div id="p-subs" class="tp hidden"></div>
<div id="p-scheduler" class="tp hidden"></div>
<div id="p-backup" class="tp hidden"></div>
</div>
<div id="M"></div>
<script>
const A='/api/admin';
function ap(u,o){return fetch(A+u,{headers:{'Content-Type':'application/json'},...o}).then(r=>r.json());}
function e(s){if(!s)return'';let d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function sw(t){document.querySelectorAll('.tp').forEach(x=>x.classList.add('hidden'));document.getElementById('p-'+t).classList.remove('hidden');document.querySelectorAll('.tb').forEach(b=>{let a=b.dataset.t===t;b.classList.toggle('border-blue-600',a);b.classList.toggle('text-blue-600',a);b.classList.toggle('border-transparent',!a);b.classList.toggle('text-gray-500',!a);});({settings:lSet,offers:lO,articles:lA,reviews:lR,tags:lT,geo:lG,cityseo:lCS,stats:lS,subs:lSu,scheduler:lSch,backup:lB})[t]?.();}
function clearCache(){fetch('/admin/clear-cache').then(r=>r.json()).then(d=>{if(d.success)alert('✓ Кэш очищен');else alert('Ошибка');}).catch(()=>alert('Ошибка'));}
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
function lT(){ap('/tags').then(tags=>{
var h='<div class="flex justify-between mb-6"><h2 class="text-xl font-bold">🏷️ Теги / Типы предложений ('+tags.length+')</h2><button onclick="tForm()" class="btn-p">+ Добавить</button></div>';
if(!tags.length){h+='<p class="text-gray-500 text-center py-8">Нет тегов. Добавьте первый!</p>';}
else{
h+='<div class="bg-white rounded-xl shadow-sm border overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3">Иконка</th><th class="text-left px-4 py-3">Название</th><th class="text-left px-4 py-3">Slug</th><th class="text-left px-4 py-3">Категория</th><th class="text-left px-4 py-3">Статус</th><th class="text-left px-4 py-3">Порядок</th><th class="text-right px-4 py-3">Действия</th></tr></thead><tbody>';
tags.forEach(t=>{
h+='<tr class="border-b hover:bg-gray-50"><td class="px-4 py-3 text-xl">'+e(t.icon||'🏷️')+'</td><td class="px-4 py-3 font-medium">'+e(t.title)+'</td><td class="px-4 py-3 text-gray-500 font-mono text-xs">'+e(t.slug)+'</td><td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 rounded text-xs">'+(TG_CAT[t.category]||t.category)+'</span></td><td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+(t.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(t.is_active?'Активен':'Выкл')+'</span></td><td class="px-4 py-3 text-gray-500">'+t.sort_order+'</td><td class="px-4 py-3 text-right"><button onclick=\'tForm('+JSON.stringify(t).replace(/\x27/g,"&#39;")+')\' class="text-blue-600 hover:underline text-sm mr-2">Ред.</button><button onclick="tD('+t.id+')" class="text-red-500 hover:underline text-sm">Удалить</button></td></tr>';});
h+='</tbody></table></div>';}
h+='<div class="bg-gray-50 rounded-xl p-4 mt-6"><p class="text-sm text-gray-500">💡 Теги отображаются как кнопки-фильтры на страницах предложений и создают отдельные SEO-страницы <code>/zajmy/type/slug</code></p></div>';
document.getElementById('p-tags').innerHTML=h;
initSort('tags-sortable','offer_tags');});}

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



/* ============ SUBSCRIBERS ============ */
function lSu(){ap('/subscribers').then(list=>{let h='<h2 class="text-xl font-bold mb-6">Подписчики ('+list.length+')</h2>';
if(!list.length){h+='<p class="text-gray-500 text-center py-8">Нет подписчиков</p>';}else{
h+='<div class="bg-white rounded-xl border"><table class="w-full"><thead><tr class="bg-gray-50"><th class="p-3 text-left text-sm">Email</th><th class="p-3 text-left text-sm">Дата</th><th class="p-3 text-left text-sm">Статус</th></tr></thead><tbody>';
list.forEach(s=>{h+='<tr class="border-t"><td class="p-3">'+e(s.email)+'</td><td class="p-3 text-sm text-gray-500">'+new Date(s.subscribed_at).toLocaleDateString('ru-RU')+'</td><td class="p-3"><span class="px-2 py-1 rounded-full text-xs '+(s.is_active?'bg-green-100 text-green-700':'bg-gray-100 text-gray-500')+'">'+(s.is_active?'Активен':'Отписан')+'</span></td></tr>';});
h+='</tbody></table></div>';}
document.getElementById('p-subs').innerHTML=h;});}

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

</script>
</body>
</html>

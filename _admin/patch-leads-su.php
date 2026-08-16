<?php ?>
<script>
function lLS(){
var el=document.getElementById('p-leadssu');if(!el)return;
el.innerHTML='<div class="flex justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';
ap('/leads-su?action=test').then(function(d){
var h='<div class="space-y-6">';
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"><div><h2 class="text-xl font-bold">🔗 Leads.su — Импорт офферов</h2><p class="text-gray-500 text-sm">Подключение офферов из партнёрской сети leads.su по API</p></div></div>';

if(!d.ok){
h+='<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">'+e(d.error||'Ошибка подключения')+'<br><br>Чтобы подключить:<ol class="list-decimal ml-4 mt-2"><li>Зайдите в <a href="https://webmaster.leads.su/account/default" target="_blank" class="underline">личный кабинет leads.su</a></li><li>Скопируйте API токен</li><li>Вставьте в Настройки сайта → поле <strong>leads_su_api_token</strong></li></ol></div>';
h+='</div>';el.innerHTML=h;return;
}

h+='<div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">✅ Подключение к leads.su работает</div>';

h+='<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">';
h+='<p class="font-semibold mb-1">ℹ️ Как работает импорт:</p>';
h+='<ul class="list-disc ml-4 space-y-1">';
h+='<li>Выберите <strong>площадку</strong> — она определяет параметр <code>pltfm_id</code> в партнёрской ссылке</li>';
h+='<li>Выберите <strong>категорию</strong> вручную или оставьте автоопределение — офферы попадут в нужный раздел сайта</li>';
h+='<li>Партнёрская ссылка: <code>pxl.leads.su/aff_c?offer_id=...&pltfm_id=...&source=kosmozaim</code></li>';
h+='<li>Импортированные офферы можно потом редактировать (ставки, суммы, описание)</li>';
h+='</ul></div>';

h+='<div class="flex flex-wrap gap-4 items-end">';
h+='<div><label class="block text-xs font-medium mb-1">Площадка</label><select id="ls-platform" class="sel-f"><option value="0">Загрузка...</option></select></div>';
h+='<div><label class="block text-xs font-medium mb-1">Категория для импорта</label><select id="ls-category" class="sel-f"><option value="">Автоопределение</option><option value="microloans">💵 Займы</option><option value="credits">🏦 Кредиты</option><option value="credit_cards">💳 Кредитные карты</option><option value="debit_cards">🪪 Дебетовые карты</option></select></div>';
h+='<button onclick="lsLoadOffers()" class="btn-p">📥 Загрузить офферы</button>';
h+='</div>';
h+='<div id="ls-offers-list"></div>';
h+='</div>';
el.innerHTML=h;
lsLoadPlatforms();
}).catch(function(err){el.innerHTML='<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка: '+(err.message||err)+'</div>';});
}

function lsLoadPlatforms(){
ap('/leads-su?action=platforms').then(function(d){
var sel=document.getElementById('ls-platform');if(!sel)return;
sel.innerHTML='<option value="0">— Выберите площадку —</option>';
if(d.ok&&d.platforms)d.platforms.forEach(function(p){
var isKosmo=p.name.toLowerCase().indexOf('kosmo')!==-1;
sel.innerHTML+='<option value="'+p.id+'"'+(isKosmo?' selected':'')+'>'+e(p.name)+' (ID:'+p.id+')</option>';
});
});
}

function lsLoadOffers(){
var platformId=document.getElementById('ls-platform').value||0;
var list=document.getElementById('ls-offers-list');
list.innerHTML='<p class="text-gray-400 text-center py-6">Загрузка офферов из leads.su...</p>';
ap('/leads-su?action=offers&platform_id='+platformId).then(function(d){
if(!d.ok){list.innerHTML='<div class="bg-red-50 p-4 rounded-xl text-red-600">'+e(d.error||'Ошибка')+'</div>';return;}
if(!d.offers||!d.offers.length){list.innerHTML='<p class="text-gray-400 text-center py-6">Нет доступных офферов</p>';return;}
window._lsOffers=d.offers;
var catLabels={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
var h='<div class="flex items-center justify-between mb-4"><p class="text-sm text-gray-600">Найдено: <strong>'+d.offers.length+'</strong> офферов</p><div class="flex gap-2"><button onclick="lsSelectAll()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">☑ Выбрать все</button><button onclick="lsImportSelected()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Импортировать выбранные</button></div></div>';
h+='<div class="overflow-x-auto bg-white rounded-xl border"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 w-8"><input type="checkbox" id="ls-check-all" onchange="lsToggleAll(this.checked)" class="w-4 h-4"></th><th class="text-left p-3">Название</th><th class="text-left p-3">Категория leads.su</th><th class="text-left p-3">→ Категория сайта</th><th class="text-left p-3">ID</th><th class="text-left p-3">Статус</th></tr></thead><tbody>';
d.offers.forEach(function(o,i){
var status=o.status||'active';
var badge=status==='active'?'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">active</span>':'<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">'+e(status)+'</span>';
var autoCat=lsGuessCategory(o);
var autoCatLabel=catLabels[autoCat]||autoCat;
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3"><input type="checkbox" class="ls-offer-cb w-4 h-4" data-idx="'+i+'"></td><td class="p-3 font-medium">'+e(o.name||'—')+'</td><td class="p-3 text-gray-500">'+e(o.category||o.vertical||'—')+'</td><td class="p-3"><span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold">'+e(autoCatLabel)+'</span></td><td class="p-3 text-gray-400">#'+e(o.id||'')+'</td><td class="p-3">'+badge+'</td></tr>';
});
h+='</tbody></table></div>';
list.innerHTML=h;
});
}

function lsGuessCategory(o){
var txt=((o.name||'')+' '+(o.category||'')+' '+(o.vertical||'')).toLowerCase();
if(/кредитн(ая|ые)\s+карт/.test(txt))return 'credit_cards';
if(/дебетов(ая|ые)\s+карт/.test(txt))return 'debit_cards';
if(/\b(кредит|кредиты|потребительский)\b/.test(txt))return 'credits';
return 'microloans';
}

function lsSelectAll(){document.querySelectorAll('.ls-offer-cb').forEach(function(cb){cb.checked=true;});document.getElementById('ls-check-all').checked=true;}
function lsToggleAll(v){document.querySelectorAll('.ls-offer-cb').forEach(function(cb){cb.checked=v;});}

function lsImportSelected(){
var cbs=document.querySelectorAll('.ls-offer-cb:checked');
if(!cbs.length){alert('Выберите офферы для импорта');return;}
var platformId=parseInt(document.getElementById('ls-platform').value)||0;
var category=document.getElementById('ls-category').value;
if(!platformId){alert('Выберите площадку! Она определяет вашу партнёрскую ссылку.');return;}
var offers=[];
cbs.forEach(function(cb){var idx=parseInt(cb.dataset.idx);if(window._lsOffers[idx])offers.push(window._lsOffers[idx]);});
var activate=confirm('Сразу активировать импортированные офферы?\n\nОК = Активировать\nОтмена = Сохранить как черновик');
var catLabel=category?({'microloans':'Займы','credits':'Кредиты','credit_cards':'Кредитные карты','debit_cards':'Дебетовые карты'}[category]||category):'Авто';
if(!confirm('Импортировать '+offers.length+' офферов?\n\nПлощадка: ID '+platformId+'\nКатегория: '+catLabel+'\nИсточник: source=kosmozaim\nСсылка: pxl.leads.su/aff_c?...&source=kosmozaim'))return;
ap('/leads-su?action=import',{method:'POST',body:JSON.stringify({offers:offers,platform_id:platformId,activate:activate,category:category})}).then(function(r){
var msg='Импорт завершён!\n\nИмпортировано: '+(r.imported||0)+'\nПропущено (дубли): '+(r.skipped||0);
if(r.errors&&r.errors.length)msg+='\n\nОшибки:\n'+r.errors.join('\n');
alert(msg);
lO();
}).catch(function(err){alert('Ошибка: '+(err.message||err));});
}
</script>

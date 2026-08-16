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
h+='<div class="flex flex-wrap gap-4 items-end">';
h+='<div><label class="block text-xs font-medium mb-1">Площадка (platform)</label><select id="ls-platform" class="sel-f"><option value="0">Загрузка...</option></select></div>';
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
sel.innerHTML='<option value="0">— Все офферы —</option>';
if(d.ok&&d.platforms)d.platforms.forEach(function(p){sel.innerHTML+='<option value="'+p.id+'">'+e(p.name)+' (ID:'+p.id+')</option>';});
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
var h='<div class="flex items-center justify-between mb-4"><p class="text-sm text-gray-600">Найдено: <strong>'+d.offers.length+'</strong> офферов</p><div class="flex gap-2"><button onclick="lsSelectAll()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">☑ Выбрать все</button><button onclick="lsImportSelected()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">📥 Импортировать выбранные</button></div></div>';
h+='<div class="overflow-x-auto bg-white rounded-xl border"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 w-8"><input type="checkbox" id="ls-check-all" onchange="lsToggleAll(this.checked)" class="w-4 h-4"></th><th class="text-left p-3">Название</th><th class="text-left p-3">Категория</th><th class="text-left p-3">ID</th><th class="text-left p-3">Статус</th></tr></thead><tbody>';
d.offers.forEach(function(o,i){
var status=o.status||'active';
var badge=status==='active'?'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">active</span>':'<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">'+e(status)+'</span>';
h+='<tr class="border-t hover:bg-gray-50"><td class="p-3"><input type="checkbox" class="ls-offer-cb w-4 h-4" data-idx="'+i+'"></td><td class="p-3 font-medium">'+e(o.name||'—')+'</td><td class="p-3 text-gray-500">'+e(o.category||o.vertical||'—')+'</td><td class="p-3 text-gray-400">#'+e(o.id||'')+'</td><td class="p-3">'+badge+'</td></tr>';
});
h+='</tbody></table></div>';
list.innerHTML=h;
});
}

function lsSelectAll(){document.querySelectorAll('.ls-offer-cb').forEach(function(cb){cb.checked=true;});document.getElementById('ls-check-all').checked=true;}
function lsToggleAll(v){document.querySelectorAll('.ls-offer-cb').forEach(function(cb){cb.checked=v;});}

function lsImportSelected(){
var cbs=document.querySelectorAll('.ls-offer-cb:checked');
if(!cbs.length){alert('Выберите офферы для импорта');return;}
var platformId=parseInt(document.getElementById('ls-platform').value)||0;
if(!platformId){
if(!confirm('Площадка не выбрана. Партнёрские ссылки будут с platform_id=0. Продолжить?'))return;
}
var offers=[];
cbs.forEach(function(cb){var idx=parseInt(cb.dataset.idx);if(window._lsOffers[idx])offers.push(window._lsOffers[idx]);});
var activate=confirm('Сразу активировать импортированные офферы?\n\nОК = Активировать\nОтмена = Сохранить как черновик');
ap('/leads-su?action=import',{method:'POST',body:JSON.stringify({offers:offers,platform_id:platformId,activate:activate})}).then(function(r){
var msg='Импорт завершён!\n\nИмпортировано: '+(r.imported||0)+'\nПропущено (дубли): '+(r.skipped||0);
if(r.errors&&r.errors.length)msg+='\n\nОшибки:\n'+r.errors.join('\n');
alert(msg);
lO();
}).catch(function(err){alert('Ошибка: '+(err.message||err));});
}
</script>

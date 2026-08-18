<script>
var _scCat='microloans';
function lSubcats(){
var el=document.getElementById('p-subcats');if(!el)return;
el.innerHTML='<p class="text-gray-500">Загрузка...</p>';
ap('/subcategories?category='+_scCat).then(function(list){

// Верхняя панель с кнопками
var h='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6"><div><h2 class="text-xl font-bold">📑 Дополнительные запросы</h2><p class="text-sm text-gray-500">Подкатегории с фильтрацией офферов, SEO-текстами и гео-страницами</p></div><div class="flex flex-wrap gap-2"><select id="sc-cat" onchange="_scCat=this.value;lSubcats()" class="sel-f text-sm w-auto"><option value="microloans"'+(_scCat==='microloans'?' selected':'')+'>Займы</option><option value="credits"'+(_scCat==='credits'?' selected':'')+'>Кредиты</option><option value="credit_cards"'+(_scCat==='credit_cards'?' selected':'')+'>Кредитные карты</option><option value="debit_cards"'+(_scCat==='debit_cards'?' selected':'')+'>Дебетовые карты</option></select><button onclick="scContentRecs()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium">📊 Рекомендации</button><button onclick="scRulesHelper()" class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg text-sm font-medium">🤖 Генератор правил</button><button onclick="scForm()" class="btn-p text-sm">+ Добавить</button></div></div>';

var catBase={microloans:'/zajmy',credits:'/kredity',credit_cards:'/karty/kreditnye',debit_cards:'/karty/debetovye'};
if(!list.length){h+='<p class="text-gray-400 text-center py-8">Нет допзапросов для этой категории</p>';}
else{
h+='<div class="bg-white rounded-xl border shadow-sm overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-3">Название</th><th class="text-left px-4 py-3">Slug</th><th class="text-left px-4 py-3">Фильтр</th><th class="text-left px-4 py-3">SEO</th><th class="text-right px-4 py-3">Действия</th></tr></thead><tbody>';
list.forEach(function(sc){
var rules='';try{var r=JSON.parse(sc.filter_rules||'{}');rules=Object.keys(r).map(function(k){return k+'='+r[k];}).join(', ');}catch(e){}
var hasSeo=sc.seo_text&&sc.seo_text.length>20;
h+='<tr class="border-b hover:bg-gray-50"><td class="px-4 py-3 font-medium">'+(sc.icon||'')+' '+e(sc.title)+'</td>';
h+='<td class="px-4 py-3 text-gray-500 text-xs font-mono">'+e(sc.slug)+'</td>';
h+='<td class="px-4 py-3 text-xs text-gray-500">'+e(rules||'все')+'</td>';
h+='<td class="px-4 py-3">'+(hasSeo?'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">✓</span>':'<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">—</span>')+'</td>';
h+='<td class="px-4 py-3 text-right space-x-2">';
h+='<a href="'+(catBase[_scCat]||'/zajmy')+'/q/'+sc.slug+'" target="_blank" class="text-gray-400 hover:text-gray-600 text-sm">👁</a> ';
h+='<button onclick=\'scForm('+JSON.stringify(sc).replace(/\'/g,"&#39;")+')\'  class="text-blue-600 hover:underline text-sm">Ред.</button> ';
h+='<button onclick="scGenSeo('+sc.id+')" class="text-purple-600 hover:underline text-sm">🤖 SEO</button> ';
h+='<button onclick="scToggle('+sc.id+','+(sc.is_active?0:1)+')" class="text-sm '+(sc.is_active?'text-yellow-600':'text-green-600')+' hover:underline">'+(sc.is_active?'Скрыть':'Показать')+'</button> ';
h+='<button onclick="scDel('+sc.id+')" class="text-red-500 hover:underline text-sm">Удалить</button>';
h+='</td></tr>';
});
h+='</tbody></table></div>';
}
el.innerHTML=h;
});
}

// === ГЕНЕРАТОР ПРАВИЛ ФИЛЬТРАЦИИ ===
function scRulesHelper(){
var catLabels={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредитные карты',debit_cards:'Дебетовые карты'};
modal('<div class="flex justify-between items-start mb-4"><div><h3 class="text-lg font-bold">🤖 Генератор правил фильтрации</h3><p class="text-sm text-gray-500 mt-1">Введите запросы — AI предложит JSON-правила</p></div><button onclick="cm()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button></div>'+
'<div class="space-y-4">'+
'<div><label class="block text-sm font-medium text-gray-700 mb-2">Категория</label><select id="rh-cat" class="sel-f"><option value="microloans"'+(_scCat==='microloans'?' selected':'')+'>'+catLabels.microloans+'</option><option value="credits"'+(_scCat==='credits'?' selected':'')+'>'+catLabels.credits+'</option><option value="credit_cards"'+(_scCat==='credit_cards'?' selected':'')+'>'+catLabels.credit_cards+'</option><option value="debit_cards"'+(_scCat==='debit_cards'?' selected':'')+'>'+catLabels.debit_cards+'</option></select></div>'+
'<div><label class="block text-sm font-medium text-gray-700 mb-2">Запросы <span class="text-gray-400 font-normal">(каждый с новой строки, до 50 шт.)</span></label><textarea id="rh-queries" class="input-f font-mono text-sm" rows="8" placeholder="Краткосрочный\nНа карту 24 часа\nМини займ на карту\nНа 6 месяцев\nНа карту сбербанка\nПод 0 процентов\nДля пенсионеров\nДля студентов"></textarea></div>'+
'<div class="flex gap-3"><button onclick="scRunRulesHelper()" class="btn-p flex items-center gap-2"><span id="rh-spinner" class="hidden">⏳</span><span id="rh-btn-text">🤖 Сгенерировать правила</span></button><button onclick="cm()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Отмена</button></div>'+
'<div id="rh-results" class="hidden"><div class="border-t pt-4 mt-4"><div class="flex justify-between items-center mb-3"><h4 class="font-bold text-gray-900">Результаты</h4><button onclick="scDownloadRules()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-medium">📥 Скачать TXT</button></div><div id="rh-results-list" class="space-y-2 max-h-80 overflow-y-auto"></div></div></div>'+
'</div>', false, 'max-w-2xl');
}

var _rhResults = [];

function scRunRulesHelper(){
var queries = document.getElementById('rh-queries').value.trim();
if(!queries){alert('Введите хотя бы один запрос');return;}
var cat = document.getElementById('rh-cat').value;
var lines = queries.split('\n').map(function(l){return l.trim();}).filter(function(l){return l.length>0;});
if(lines.length===0){alert('Введите хотя бы один запрос');return;}
if(lines.length>50){lines=lines.slice(0,50);alert('Обрезано до 50 запросов');}

document.getElementById('rh-spinner').classList.remove('hidden');
document.getElementById('rh-btn-text').textContent='Генерация...';

ap('/filter-rules-helper',{method:'POST',body:JSON.stringify({queries:lines,category:cat})})
.then(function(d){
document.getElementById('rh-spinner').classList.add('hidden');
document.getElementById('rh-btn-text').textContent='🤖 Сгенерировать правила';

if(d.error){alert(d.error);return;}
if(!d.results||!d.results.length){alert('Не удалось сгенерировать правила');return;}

_rhResults = d.results;
var listEl = document.getElementById('rh-results-list');
var h='';
d.results.forEach(function(r,i){
var rulesStr = JSON.stringify(r.rules||{});
var isEmpty = !r.rules || Object.keys(r.rules).length===0;
h+='<div class="bg-gray-50 rounded-lg p-3 flex items-start gap-3">';
h+='<span class="text-xl">'+(r.icon||'📋')+'</span>';
h+='<div class="flex-1 min-w-0">';
h+='<div class="font-medium text-gray-900">'+e(r.query)+'</div>';
h+='<code class="text-xs '+(isEmpty?'text-orange-600 bg-orange-50':'text-green-700 bg-green-50')+' px-2 py-1 rounded mt-1 inline-block break-all">'+e(rulesStr)+'</code>';
if(r.hint){h+='<div class="text-xs text-gray-400 mt-1">💡 '+e(r.hint)+'</div>';}
h+='</div>';
h+='<button onclick="scUseRule('+i+')" class="text-blue-600 hover:text-blue-800 text-sm whitespace-nowrap" title="Копировать JSON">📋</button>';
h+='</div>';
});
listEl.innerHTML=h;
document.getElementById('rh-results').classList.remove('hidden');

// Показать провайдера
var provLabel = d.provider==='fallback'?'(локальные правила)':'('+d.provider+')';
listEl.insertAdjacentHTML('beforebegin','<p class="text-xs text-gray-400 mb-2">Источник: '+provLabel+'</p>');
})
.catch(function(err){
document.getElementById('rh-spinner').classList.add('hidden');
document.getElementById('rh-btn-text').textContent='🤖 Сгенерировать правила';
alert('Ошибка: '+err.message);
});
}

function scUseRule(idx){
if(!_rhResults[idx])return;
var r = _rhResults[idx];
var text = r.query + '\t' + (r.icon||'📋') + '\t' + JSON.stringify(r.rules||{});
if(navigator.clipboard){
navigator.clipboard.writeText(JSON.stringify(r.rules||{})).then(function(){
alert('JSON скопирован в буфер обмена:\n'+JSON.stringify(r.rules||{}));
});
} else {
prompt('Скопируйте JSON:', JSON.stringify(r.rules||{}));
}
}

function scDownloadRules(){
if(!_rhResults.length){alert('Нет результатов');return;}
var lines = ['# Правила фильтрации для дополнительных запросов','# Формат: Название | Иконка | JSON правила','# Сгенерировано: '+new Date().toLocaleString('ru-RU'),''];
_rhResults.forEach(function(r){
lines.push(r.query + '\t' + (r.icon||'📋') + '\t' + JSON.stringify(r.rules||{}) + '\t' + (r.hint||''));
});
var blob = new Blob([lines.join('\n')], {type:'text/plain;charset=utf-8'});
var a = document.createElement('a');
a.href = URL.createObjectURL(blob);
a.download = 'filter-rules-'+new Date().toISOString().slice(0,10)+'.txt';
a.click();
}

// === ОСНОВНЫЕ ФУНКЦИИ ===
function scForm(sc){
var f=sc||{title:'',slug:'',icon:'📋',category:_scCat,filter_rules:'{}',sort_order:0,h1:'',description:'',meta_title:'',meta_description:'',seo_text:'',is_active:true};
var id=sc?sc.id:0;
var rules='';try{rules=typeof f.filter_rules==='string'?f.filter_rules:JSON.stringify(f.filter_rules||{});}catch(e){rules='{}';}
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать':'Новый допзапрос')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return scSave(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="sc-title" class="input-f" value="'+e(f.title)+'" required placeholder="Краткосрочный"></div>'+
'<div><label class="block text-xs font-medium mb-1">Иконка</label><input id="sc-icon" class="input-f" value="'+e(f.icon||'📋')+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сортировка</label><input id="sc-sort" type="number" class="input-f" value="'+(f.sort_order||0)+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Правила фильтрации (JSON) <button type="button" onclick="cm();setTimeout(scRulesHelper,100)" class="text-purple-600 hover:underline text-xs ml-2">🤖 Помощник</button></label><textarea id="sc-rules" class="input-f font-mono text-xs" rows="3" placeholder=\'{"term_max_days":20}\'>'+e(rules)+'</textarea><p class="text-xs text-gray-400 mt-1">Ключи: term_max_days, term_min_days_min, term_max_days_min, amount_max_min, amount_max_max, amount_min_max, free_term_days_min, rate_max, borrower_category</p></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">H1</label><input id="sc-h1" class="input-f" value="'+e(f.h1||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Описание</label><textarea id="sc-desc" class="input-f" rows="2">'+e(f.description||'')+'</textarea></div>'+
'<div><label class="block text-xs font-medium mb-1">Meta Title</label><input id="sc-mt" class="input-f" value="'+e(f.meta_title||'')+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Meta Description</label><input id="sc-md" class="input-f" value="'+e(f.meta_description||'')+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">SEO текст (HTML)</label><textarea id="sc-seo" class="input-f" rows="4">'+e(f.seo_text||'')+'</textarea></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>', false);
}
function scSave(ev,id){ev.preventDefault();
var rules='{}';try{rules=document.getElementById('sc-rules').value.trim()||'{}';JSON.parse(rules);}catch(e){alert('Неверный JSON в правилах фильтрации');return false;}
var data={title:document.getElementById('sc-title').value,icon:document.getElementById('sc-icon').value,sort_order:parseInt(document.getElementById('sc-sort').value)||0,filter_rules:rules,h1:document.getElementById('sc-h1').value,description:document.getElementById('sc-desc').value,meta_title:document.getElementById('sc-mt').value,meta_description:document.getElementById('sc-md').value,seo_text:document.getElementById('sc-seo').value,category:_scCat};
if(id)data.id=id;
ap('/subcategories',{method:id?'PUT':'POST',body:JSON.stringify(data)}).then(function(d){if(d.error){alert(d.error);return;}cm();lSubcats();});return false;
}
function scGenSeo(id){
ap('/subcategories',{method:'POST',body:JSON.stringify({action:'generate-seo',id:id})}).then(function(d){
if(d.error){alert(d.error);return;}
alert('SEO сгенерировано ('+(d.provider||'template')+')');
lSubcats();
});
}
function scToggle(id,v){ap('/subcategories',{method:'PUT',body:JSON.stringify({id:id,is_active:!!v})}).then(function(){lSubcats();});}
function scDel(id){if(!confirm('Удалить этот допзапрос?'))return;ap('/subcategories',{method:'DELETE',body:JSON.stringify({id:id})}).then(function(){lSubcats();});}
// === РЕКОМЕНДАЦИИ ПО КОНТЕНТУ ИЗ ПОИСКОВЫХ ЗАПРОСОВ ===
function scContentRecs(){
modal('<div class="flex justify-between items-start mb-4"><div><h3 class="text-lg font-bold">📊 Рекомендации по контенту</h3><p class="text-sm text-gray-500 mt-1">Анализ поисковых запросов из Яндекс и Google</p></div><button onclick="cm()" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button></div>'+
'<div id="cr-content"><p class="text-gray-500 text-center py-8">⏳ Загрузка данных из Яндекс.Вебмастер и Google Search Console...</p></div>', false, 'max-w-4xl');
loadContentRecs();
}

function loadContentRecs(){
var days=30, minShows=10;
ap('/content-recommendations?action=analyze&days='+days+'&min_shows='+minShows).then(function(d){
var box=document.getElementById('cr-content');
if(d.error){box.innerHTML='<p class="text-red-500">'+e(d.error)+'</p>';return;}
if(!d.recommendations_count){box.innerHTML='<p class="text-gray-500 text-center py-8">Нет новых рекомендаций. Весь контент уже создан! 🎉</p>';return;}

var h='<div class="flex items-center justify-between mb-4"><div class="text-sm text-gray-500">Проанализировано <strong>'+d.total_queries+'</strong> запросов за '+d.days+' дней</div><div class="text-sm"><span class="bg-green-100 text-green-700 px-2 py-1 rounded">'+d.recommendations_count+' рекомендаций</span></div></div>';
h+='<div class="space-y-2 max-h-96 overflow-y-auto">';

d.recommendations.forEach(function(r,i){
var catLabel={microloans:'Займы',credits:'Кредиты',credit_cards:'Кредит.карты',debit_cards:'Дебет.карты'}[r.category]||'';
var typeColor=r.content_type==='article'?'blue':'purple';
h+='<div class="bg-gray-50 rounded-lg p-3 flex items-start gap-3 hover:bg-gray-100">';
h+='<div class="flex-1 min-w-0">';
h+='<div class="flex items-center gap-2 flex-wrap">';
h+='<span class="font-medium text-gray-900">'+e(r.query)+'</span>';
h+='<span class="text-xs bg-'+typeColor+'-100 text-'+typeColor+'-700 px-1.5 py-0.5 rounded">'+r.action+'</span>';
h+='<span class="text-xs bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded">'+catLabel+'</span>';
h+='</div>';
h+='<div class="flex gap-4 mt-1 text-xs text-gray-500">';
h+='<span>👁 '+r.shows+' показов</span>';
h+='<span>👆 '+r.clicks+' кликов</span>';
h+='<span>📍 позиция '+r.position+'</span>';
h+='<span>⭐ score '+r.score+'</span>';
h+='</div>';
h+='</div>';
h+='<div class="flex gap-1">';
if(r.content_type==='subcategory'){
h+='<button onclick="crCreateSubcat(\''+e(r.query).replace(/'/g,"\\'")+'\',\''+r.category+'\')" class="text-xs bg-purple-600 hover:bg-purple-700 text-white px-2 py-1 rounded">+ Допзапрос</button>';
}
if(r.content_type==='article'){
h+='<button onclick="crArticleIdea(\''+e(r.query).replace(/'/g,"\\'")+'\','+i+')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded">💡 Идея статьи</button>';
}
h+='</div>';
h+='</div>';
});

h+='</div>';
h+='<div class="mt-4 pt-4 border-t text-xs text-gray-400">💡 Рекомендации основаны на запросах, по которым ещё нет контента. Чем выше score — тем больше потенциал.</div>';
box.innerHTML=h;
}).catch(function(err){
document.getElementById('cr-content').innerHTML='<p class="text-red-500">Ошибка: '+err.message+'</p>';
});
}

function crCreateSubcat(query, category){
if(!confirm('Создать допзапрос "'+query+'" для категории '+category+'?')) return;
ap('/content-recommendations?action=generate-subcat',{method:'POST',body:JSON.stringify({query:query,category:category})})
.then(function(d){
if(d.error){alert('Ошибка: '+d.error);return;}
alert('✅ Допзапрос создан!\n\nSlug: '+d.slug+'\nПравила: '+JSON.stringify(d.rules)+'\n\n'+d.message);
cm();
_scCat=category;
lSubcats();
}).catch(function(err){alert('Ошибка: '+err.message);});
}

function crArticleIdea(query, idx){
var box=document.getElementById('cr-content');
var origHtml=box.innerHTML;
box.innerHTML='<p class="text-gray-500 text-center py-8">⏳ Генерация идеи статьи...</p>';

ap('/content-recommendations?action=generate-article-idea',{method:'POST',body:JSON.stringify({query:query})})
.then(function(d){
if(d.error){alert('Ошибка: '+d.error);box.innerHTML=origHtml;return;}
var h='<div class="bg-blue-50 rounded-lg p-4 border border-blue-200">';
h+='<h4 class="font-bold text-blue-900 mb-2">💡 Идея статьи по запросу "'+e(query)+'"</h4>';
h+='<p class="font-medium text-gray-900 mb-3">📝 '+e(d.title)+'</p>';
h+='<p class="text-sm text-gray-700 mb-2">План:</p><ul class="list-disc list-inside text-sm text-gray-600 mb-3">';
(d.outline||[]).forEach(function(p){h+='<li>'+e(p)+'</li>';});
h+='</ul>';
if(d.target_keywords&&d.target_keywords.length){
h+='<p class="text-xs text-gray-500">Ключевые слова: '+d.target_keywords.map(function(k){return e(k);}).join(', ')+'</p>';
}
h+='<p class="text-xs text-gray-400 mt-2">Источник: '+d.provider+'</p>';
h+='<div class="flex gap-2 mt-3"><button onclick="loadContentRecs()" class="text-sm text-blue-600 hover:underline">← Назад к рекомендациям</button></div>';
h+='</div>';
box.innerHTML=h;
}).catch(function(err){alert('Ошибка: '+err.message);box.innerHTML=origHtml;});
}
</script>

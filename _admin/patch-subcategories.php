<script>
var _scCat='microloans';
function lSubcats(){
var el=document.getElementById('p-subcats');if(!el)return;
el.innerHTML='<p class="text-gray-500">Загрузка...</p>';
ap('/subcategories?category='+_scCat).then(function(list){
var h='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6"><div><h2 class="text-xl font-bold">📑 Дополнительные запросы</h2><p class="text-sm text-gray-500">Подкатегории с фильтрацией офферов, SEO-текстами и гео-страницами</p></div><div class="flex gap-2"><select id="sc-cat" onchange="_scCat=this.value;lSubcats()" class="sel-f text-sm w-auto"><option value="microloans"'+(_scCat==='microloans'?' selected':'')+'>Займы</option><option value="credits"'+(_scCat==='credits'?' selected':'')+'>Кредиты</option><option value="credit_cards"'+(_scCat==='credit_cards'?' selected':'')+'>Кредитные карты</option><option value="debit_cards"'+(_scCat==='debit_cards'?' selected':'')+'>Дебетовые карты</option></select><button onclick="scForm()" class="btn-p text-sm">+ Добавить</button></div></div>';
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
h+='<button onclick=\'scForm('+JSON.stringify(sc).replace(/\'/g,"&#39;")+')\' class="text-blue-600 hover:underline text-sm">Ред.</button> ';
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
function scForm(sc){
var f=sc||{title:'',slug:'',icon:'📋',category:_scCat,filter_rules:'{}',sort_order:0,h1:'',description:'',meta_title:'',meta_description:'',seo_text:'',is_active:true};
var id=sc?sc.id:0;
var rules='';try{rules=typeof f.filter_rules==='string'?f.filter_rules:JSON.stringify(f.filter_rules||{});}catch(e){rules='{}';}
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать':'Новый допзапрос')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return scSave(event,'+id+')"><div class="grid grid-cols-2 gap-3">'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Название *</label><input id="sc-title" class="input-f" value="'+e(f.title)+'" required placeholder="Краткосрочный"></div>'+
'<div><label class="block text-xs font-medium mb-1">Иконка</label><input id="sc-icon" class="input-f" value="'+e(f.icon||'📋')+'"></div>'+
'<div><label class="block text-xs font-medium mb-1">Сортировка</label><input id="sc-sort" type="number" class="input-f" value="'+(f.sort_order||0)+'"></div>'+
'<div class="col-span-2"><label class="block text-xs font-medium mb-1">Правила фильтрации (JSON)</label><textarea id="sc-rules" class="input-f font-mono text-xs" rows="3" placeholder=\'{"term_max_days":20}\'>'+e(rules)+'</textarea><p class="text-xs text-gray-400 mt-1">Ключи: term_max_days, term_min_days_min, term_max_days_min, amount_max_min, amount_max_max, amount_min_max, free_term_days_min, rate_max, borrower_category</p></div>'+
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
</script>

<?php
/**
 * UI для email-воронки в админке
 */
?>
<script>
function lEF(){
var el=document.getElementById('p-emailfunnel');if(!el)return;
el.innerHTML='<div class="flex justify-center py-12"><div class="animate-spin w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full"></div></div>';
ap('/email-funnel?action=stats').then(function(d){
var h='<div class="space-y-6">';
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">';
h+='<div><h2 class="text-xl font-bold">📧 Email-воронка</h2><p class="text-gray-500 text-sm">Автоматические цепочки писем для подписчиков</p></div>';
h+='<div class="flex gap-2"><button onclick="efRun()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">▶ Запустить</button><button onclick="efAdd()" class="btn-p">+ Добавить шаг</button><button onclick="efLog()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold">📜 Лог</button></div>';
h+='</div>';
h+='<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-800">📊 Всего подписчиков: <strong>'+(d.total_subscribers||0)+'</strong>. Добавьте cron: <code class="bg-blue-100 px-1 rounded">php cron/funnel-cron.php</code> (раз в час)</div>';
if(d.steps&&d.steps.length){
h+='<div class="space-y-4">';
d.steps.forEach(function(s,i){
var pct=d.total_subscribers>0?Math.round(s.sent_count/d.total_subscribers*100):0;
var badge=s.is_active?'<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-semibold">Активен</span>':'<span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs font-semibold">Выключен</span>';
var delay=s.delay_hours==0?'Сразу':s.delay_hours<24?s.delay_hours+' ч':Math.round(s.delay_hours/24)+' дн';
h+='<div class="bg-white rounded-xl border p-5">';
h+='<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">';
h+='<div class="flex items-center gap-3"><span class="w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center font-bold text-sm">'+(i+1)+'</span><div><h3 class="font-bold">'+e(s.name)+'</h3><p class="text-xs text-gray-500">Тема: '+e(s.subject)+'</p></div></div>';
h+='<div class="flex items-center gap-2">'+badge+'<span class="text-xs text-gray-400">Задержка: '+delay+'</span></div>';
h+='</div>';
h+='<div class="flex items-center gap-4 mb-3"><div class="flex-1 bg-gray-100 rounded-full h-2"><div class="bg-blue-500 rounded-full h-2" style="width:'+pct+'%"></div></div><span class="text-sm font-medium text-gray-600">'+s.sent_count+'/'+d.total_subscribers+' ('+pct+'%)</span></div>';
h+='<div class="flex gap-2"><button onclick="efEdit('+s.id+')" class="text-blue-600 hover:underline text-sm">Редактировать</button><button onclick="efToggle('+s.id+')" class="text-yellow-600 hover:underline text-sm">'+(s.is_active?'Выключить':'Включить')+'</button><button onclick="efDel('+s.id+')" class="text-red-500 hover:underline text-sm">Удалить</button></div>';
h+='</div>';
});
h+='</div>';
}else{
h+='<div class="bg-gray-50 rounded-xl p-8 text-center text-gray-400">Нет шагов воронки. Нажмите «+ Добавить шаг» или «▶ Запустить» для создания шаблонов.</div>';
}
h+='</div>';
el.innerHTML=h;
}).catch(function(err){el.innerHTML='<div class="bg-red-50 border border-red-200 p-6 rounded-xl text-red-600">Ошибка: '+(err.message||err)+'</div>';});
}

function efRun(){
if(!confirm('Запустить отправку писем воронки?'))return;
ap('/email-funnel?action=run',{method:'POST',body:'{}'}).then(function(r){
alert('Отправлено: '+(r.sent||0)+', ошибок: '+(r.errors||0));
lEF();
});
}

function efToggle(id){
ap('/email-funnel?action=toggle',{method:'POST',body:JSON.stringify({id:id})}).then(function(){lEF();});
}

function efDel(id){
if(!confirm('Удалить шаг воронки?'))return;
ap('/email-funnel?action=delete',{method:'POST',body:JSON.stringify({id:id})}).then(function(){lEF();});
}

function efAdd(){efShowForm(null);}

function efEdit(id){
ap('/email-funnel?action=steps').then(function(steps){
var step=steps.find(function(s){return s.id==id;});
if(step)efShowForm(step);
});
}

function efShowForm(s){
var f=s||{id:0,name:'',subject:'',body_html:'',delay_hours:24,is_active:true};
var id=f.id||0;
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">'+(id?'Редактировать шаг':'Новый шаг воронки')+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return efSave(event,'+id+')"><div class="space-y-3">'+
'<div><label class="block text-xs font-medium mb-1">Название шага</label><input id="ef-name" class="input-f" value="'+e(f.name)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">Тема письма</label><input id="ef-subject" class="input-f" value="'+e(f.subject)+'" required></div>'+
'<div><label class="block text-xs font-medium mb-1">HTML содержание</label><p class="text-xs text-gray-400 mb-1">Используйте {{offers}} для блока офферов</p><textarea id="ef-body" class="input-f" rows="8" required>'+e(f.body_html)+'</textarea></div>'+
'<div><label class="block text-xs font-medium mb-1">Задержка (часы после подписки)</label><input id="ef-delay" type="number" class="input-f" value="'+f.delay_hours+'" min="0"></div>'+
'<div><label class="flex items-center gap-2"><input type="checkbox" id="ef-active" '+(f.is_active?'checked':'')+' class="w-4 h-4"><span class="text-sm">Активен</span></label></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="btn-p">Сохранить</button></div></form>');
}

function efSave(ev,id){
ev.preventDefault();
var d={id:id,name:document.getElementById('ef-name').value,subject:document.getElementById('ef-subject').value,body_html:document.getElementById('ef-body').value,delay_hours:parseInt(document.getElementById('ef-delay').value)||0,is_active:document.getElementById('ef-active').checked};
ap('/email-funnel?action='+(id?'update':'create'),{method:'POST',body:JSON.stringify(d)}).then(function(){cm();lEF();});
return false;
}

function efLog(){
ap('/email-funnel?action=log').then(function(rows){
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">📜 Лог отправки воронки</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';
if(!rows||!rows.length){h+='<p class="text-gray-400">Пока нет отправок</p>';modal(h);return;}
h+='<div class="max-h-96 overflow-y-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-gray-50"><th class="text-left p-2">Email</th><th class="text-left p-2">Шаг</th><th class="text-left p-2">Статус</th><th class="text-right p-2">Дата</th></tr></thead><tbody>';
rows.forEach(function(r){
var st=r.status==='sent'?'<span class="text-green-600">✅ Отправлено</span>':'<span class="text-red-500">❌ Ошибка</span>';
h+='<tr class="border-b"><td class="p-2">'+e(r.email)+'</td><td class="p-2">'+(r.step_name||'#'+r.step_id)+'</td><td class="p-2">'+st+'</td><td class="p-2 text-right text-gray-400">'+e((r.sent_at||'').substring(0,16))+'</td></tr>';
});
h+='</tbody></table></div>';
modal(h);
});
}
</script>

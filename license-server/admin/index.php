<?php
/**
 * Админка сервера лицензий
 */
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
$isAuth = !empty($_SESSION['lic_admin_id']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>License Server — Космозайм</title>
<script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}.sel-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;background:white;font-size:0.875rem;}.input-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;}</style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php if (!$isAuth): ?>
<!-- Логин -->
<div class="flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
<div class="text-center mb-6"><span class="text-4xl">🔐</span><h1 class="text-xl font-bold mt-2">License Server</h1></div>
<form onsubmit="return doLogin(event)">
<div class="mb-4"><input type="text" id="lg-user" placeholder="Логин" required class="input-f"></div>
<div class="mb-4"><input type="password" id="lg-pass" placeholder="Пароль" required class="input-f"></div>
<div id="lg-err" class="hidden text-red-600 text-sm mb-3"></div>
<button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700">Войти</button>
</form>
</div>
</div>
<script>
function doLogin(e){e.preventDefault();
fetch('/admin/api?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:document.getElementById('lg-user').value,password:document.getElementById('lg-pass').value})})
.then(r=>r.json()).then(d=>{if(d.success)location.reload();else{var el=document.getElementById('lg-err');el.textContent=d.error;el.classList.remove('hidden');}});
return false;}
</script>

<?php else: ?>
<!-- Админка -->
<div class="bg-gray-900 text-white"><div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center"><div class="flex items-center gap-3"><span class="text-2xl">🔑</span><h1 class="font-bold">License Server</h1></div><div class="flex items-center gap-4"><span class="text-gray-400 text-sm"><?= e($_SESSION['lic_admin_user'] ?? '') ?></span><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm">🔑 Пароль</button><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти</button></div></div></div>

<div class="max-w-6xl mx-auto px-4 py-8">
<div id="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8"></div>

<div class="flex justify-between items-center mb-4">
<h2 class="text-xl font-bold">Лицензии</h2>
<button onclick="showCreate()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">+ Создать</button>
</div>
<div id="list" class="space-y-3"></div>

<h2 class="text-xl font-bold mt-10 mb-4">Последние события</h2>
<div id="logs" class="bg-white rounded-xl border overflow-hidden"></div>
</div>
<div id="M"></div>

<script>
var A='/admin/api';
function ap(u,o){return fetch(A+u,Object.assign({headers:{'Content-Type':'application/json'},credentials:'same-origin'},o||{})).then(async function(r){var t=await r.text(); try{return JSON.parse(t);}catch(e){throw new Error('Невалидный ответ API: '+t.substring(0,200));}});}
function e(s){if(!s)return'';var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function modal(h){document.getElementById('M').innerHTML='<div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto" onclick="if(event.target===this)cm()"><div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:560px;margin-top:40px" onclick="event.stopPropagation()">'+h+'</div></div>';}
function cm(){document.getElementById('M').innerHTML='';}

function load(){
ap('?action=stats').then(d=>{
if(d.error) throw new Error(d.error);
var h='';
[['🔑','Всего',d.total],['✅','Активных',d.active],['⏰','Истёкших',d.expired],['🚫','Отказов сегодня',d.denied_today]].forEach(c=>{
h+='<div class=\"bg-white rounded-xl border p-4 text-center\"><p class=\"text-2xl font-bold\">'+(c[2]||0)+'</p><p class=\"text-xs text-gray-500\">'+c[0]+' '+c[1]+'</p></div>';});
document.getElementById('stats').innerHTML=h;
}).catch(function(err){document.getElementById('stats').innerHTML='<div class=\"col-span-full bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\">Ошибка загрузки статистики: '+e(err.message)+'</div>';});

ap('?action=list').then(d=>{
if(d.error) throw new Error(d.error);
var h='';
(d.licenses||[]).forEach(l=>{
var st={'active':'bg-green-100 text-green-700','expired':'bg-red-100 text-red-700','suspended':'bg-yellow-100 text-yellow-700','revoked':'bg-gray-100 text-gray-500'};
var exp=l.expires_at?new Date(l.expires_at).toLocaleDateString('ru-RU'):'∞';
h+='<div class="bg-white rounded-xl border p-4">';
h+='<div class="flex items-center justify-between mb-2"><code class="text-sm font-bold bg-gray-100 px-2 py-1 rounded">'+e(l.license_key)+'</code><span class="text-xs px-2 py-0.5 rounded '+(st[l.status]||'')+'">'+(l.status)+'</span></div>';
h+='<div class="text-sm text-gray-600"><span class="font-medium">'+e(l.domain||'не привязан')+'</span> • '+e(l.plan)+' • до '+exp+'</div>';
h+='<div class="text-xs text-gray-400 mt-1">'+e(l.owner_name||'')+' '+e(l.owner_email||'')+' • Проверок за 24ч: '+(l.checks_24h||0)+'</div>';
h+='<div class="flex gap-2 mt-2">';
h+='<button onclick="editLic('+l.id+')" class="text-blue-600 text-xs hover:underline">Ред.</button>';
h+='<button onclick="showLogs('+l.id+')" class="text-purple-600 text-xs hover:underline">Логи</button>';
if(l.status==='active')h+='<button onclick="toggleLic('+l.id+',\'suspended\')" class="text-yellow-600 text-xs hover:underline">Приостановить</button>';
if(l.status==='suspended')h+='<button onclick="toggleLic('+l.id+',\'active\')" class="text-green-600 text-xs hover:underline">Активировать</button>';
h+='<button onclick="delLic('+l.id+')" class="text-red-500 text-xs hover:underline">Удалить</button>';
h+='</div></div>';});
document.getElementById('list').innerHTML=h||'<p class=\"text-gray-400 text-center py-8\">Нет лицензий</p>';
}).catch(function(err){document.getElementById('list').innerHTML='<div class=\"bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\">Ошибка загрузки лицензий: '+e(err.message)+'</div>';});

ap('?action=logs&limit=30').then(d=>{
if(d.error) throw new Error(d.error);
var h='<table class=\"w-full text-xs\"><thead class=\"bg-gray-50\"><tr><th class=\"px-3 py-2 text-left\">Время</th><th class=\"px-3 py-2\">Действие</th><th class=\"px-3 py-2\">Ключ</th><th class=\"px-3 py-2\">Домен</th><th class=\"px-3 py-2\">IP</th><th class=\"px-3 py-2\">Код</th></tr></thead><tbody>';
(d.logs||[]).forEach(l=>{
var acol={'activate':'text-green-600','verify':'text-blue-600','denied':'text-red-600','heartbeat':'text-gray-500','deactivate':'text-yellow-600','error':'text-red-600'};
h+='<tr class="border-t"><td class="px-3 py-1.5">'+new Date(l.created_at).toLocaleString('ru-RU',{hour:'2-digit',minute:'2-digit',day:'2-digit',month:'2-digit'})+'</td><td class="px-3 py-1.5 font-medium '+(acol[l.action]||'')+'">'+e(l.action)+'</td><td class="px-3 py-1.5 font-mono">'+e((l.license_key||'').substr(-12))+'</td><td class="px-3 py-1.5">'+e(l.domain||'-')+'</td><td class="px-3 py-1.5 text-gray-400">'+e(l.ip||'')+'</td><td class="px-3 py-1.5">'+(l.response_code||'')+'</td></tr>';});
h+='</tbody></table>';
document.getElementById('logs').innerHTML=h;
}).catch(function(err){document.getElementById('logs').innerHTML='<div class=\"bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\">Ошибка загрузки логов: '+e(err.message)+'</div>';});
}

function showCreate(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Создать лицензию</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return createLic(event)"><div class="space-y-3">'+
'<div><label class="block text-xs font-medium mb-1">Домен</label><input id="cr-domain" class="input-f" placeholder="example.com"></div>'+
'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="cr-plan" class="sel-f"><option value="trial">Trial</option><option value="basic" selected>Basic</option><option value="pro">Pro</option><option value="enterprise">Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="cr-exp" class="input-f"></div></div>'+
'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="cr-name" class="input-f"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="cr-email" class="input-f" type="email"></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="cr-notes" class="input-f" rows="2"></textarea></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Создать</button></div></form>');
}

function createLic(ev){ev.preventDefault();
ap('?action=create',{method:'POST',body:JSON.stringify({
domain:document.getElementById('cr-domain').value,
plan:document.getElementById('cr-plan').value,
expires_at:document.getElementById('cr-exp').value||null,
owner_name:document.getElementById('cr-name').value,
owner_email:document.getElementById('cr-email').value,
notes:document.getElementById('cr-notes').value
})}).then(d=>{
if(d.success){cm();alert('✅ Ключ создан:\\n'+d.license_key);load();}else alert('❌ '+d.error);
});return false;}

function toggleLic(id,st){
ap('?action=update',{method:'POST',body:JSON.stringify({id:id,status:st})}).then(()=>load());
}
function delLic(id){if(!confirm('Удалить лицензию?'))return;
ap('?action=delete',{method:'POST',body:JSON.stringify({id:id})}).then(()=>load());
}
function editLic(id){
ap('?action=list').then(d=>{
var l=(d.licenses||[]).find(x=>x.id==id);if(!l)return;
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<div class="mb-3"><label class="block text-xs font-medium mb-1">Ключ</label><code class="text-sm bg-gray-100 px-2 py-1 rounded block">'+e(l.license_key)+'</code></div>'+
'<form onsubmit="return saveLic(event,'+id+')"><div class="space-y-3">'+
'<div><label class="block text-xs font-medium mb-1">Домен</label><input id="ed-domain" class="input-f" value="'+e(l.domain||'')+'"></div>'+
'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="ed-plan" class="sel-f"><option value="trial"'+(l.plan==='trial'?' selected':'')+'>Trial</option><option value="basic"'+(l.plan==='basic'?' selected':'')+'>Basic</option><option value="pro"'+(l.plan==='pro'?' selected':'')+'>Pro</option><option value="enterprise"'+(l.plan==='enterprise'?' selected':'')+'>Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Статус</label><select id="ed-status" class="sel-f"><option value="active"'+(l.status==='active'?' selected':'')+'>Active</option><option value="suspended"'+(l.status==='suspended'?' selected':'')+'>Suspended</option><option value="expired"'+(l.status==='expired'?' selected':'')+'>Expired</option><option value="revoked"'+(l.status==='revoked'?' selected':'')+'>Revoked</option></select></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="ed-exp" class="input-f" value="'+(l.expires_at?l.expires_at.substr(0,10):'')+'"></div>'+
'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="ed-name" class="input-f" value="'+e(l.owner_name||'')+'"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="ed-email" class="input-f" value="'+e(l.owner_email||'')+'"></div></div>'+
'<div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="ed-notes" class="input-f" rows="2">'+e(l.notes||'')+'</textarea></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Сохранить</button></div></form>');
});
}
function saveLic(ev,id){ev.preventDefault();
ap('?action=update',{method:'POST',body:JSON.stringify({
id:id,domain:document.getElementById('ed-domain').value,plan:document.getElementById('ed-plan').value,status:document.getElementById('ed-status').value,
expires_at:document.getElementById('ed-exp').value||null,owner_name:document.getElementById('ed-name').value,owner_email:document.getElementById('ed-email').value,notes:document.getElementById('ed-notes').value
})}).then(d=>{if(d.success){cm();load();}else alert('❌ '+(d.error||'Ошибка'));});return false;}

function showLogs(id){
ap('?action=logs&license_id='+id+'&limit=50').then(d=>{
var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Логи лицензии #'+id+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';
h+='<div class="max-h-96 overflow-y-auto"><table class="w-full text-xs"><thead class="bg-gray-50"><tr><th class="px-2 py-1">Время</th><th class="px-2 py-1">Действие</th><th class="px-2 py-1">Домен</th><th class="px-2 py-1">IP</th><th class="px-2 py-1">Сообщение</th></tr></thead><tbody>';
(d.logs||[]).forEach(l=>{h+='<tr class="border-t"><td class="px-2 py-1">'+new Date(l.created_at).toLocaleString('ru-RU')+'</td><td class="px-2 py-1 font-medium">'+e(l.action)+'</td><td class="px-2 py-1">'+e(l.domain||'')+'</td><td class="px-2 py-1">'+e(l.ip||'')+'</td><td class="px-2 py-1 text-gray-500">'+e(l.message||'')+'</td></tr>';});
h+='</tbody></table></div>';
modal(h);
});
}

function showChangePw(){
modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔑 Смена пароля</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>'+
'<form onsubmit="return doChangePw(event)"><div class="space-y-4">'+
'<div><label class="block text-sm font-medium mb-1">Текущий пароль</label><input type="password" id="cp-old" class="input-f" required></div>'+
'<div><label class="block text-sm font-medium mb-1">Новый пароль</label><input type="password" id="cp-new" class="input-f" required minlength="6"></div>'+
'<div><label class="block text-sm font-medium mb-1">Повторите</label><input type="password" id="cp-confirm" class="input-f" required minlength="6"></div>'+
'<div id="cp-err" class="hidden text-red-600 text-sm"></div>'+
'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" id="cp-btn" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Сохранить</button></div></form>');
}

function doChangePw(ev){ev.preventDefault();
var o=document.getElementById('cp-old').value;
var n=document.getElementById('cp-new').value;
var c=document.getElementById('cp-confirm').value;
var err=document.getElementById('cp-err');
err.classList.add('hidden');
if(n!==c){err.textContent='Пароли не совпадают';err.classList.remove('hidden');return false;}
if(n.length<6){err.textContent='Минимум 6 символов';err.classList.remove('hidden');return false;}
var btn=document.getElementById('cp-btn');btn.disabled=true;btn.textContent='⏳';
ap('?action=change-password',{method:'POST',body:JSON.stringify({current_password:o,new_password:n})}).then(function(d){
btn.disabled=false;btn.textContent='Сохранить';
if(d.success){cm();alert('✅ '+(d.message||'Пароль изменён'));}
else{err.textContent=d.error||'Ошибка';err.classList.remove('hidden');}
}).catch(function(){btn.disabled=false;btn.textContent='Сохранить';err.textContent='Ошибка соединения';err.classList.remove('hidden');});
return false;}

function logout(){fetch('/admin/api?action=logout',{method:'POST'}).then(()=>location.reload());}
load();
</script>
<?php endif; ?>
</body>
</html>

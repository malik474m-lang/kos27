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
<title>License Server — KZM</title>
<script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}.input-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;}.input-f:focus{outline:none;border-color:#3b82f6;}.sel-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;background:white;font-size:0.875rem;}</style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php if (!$isAuth): ?>
<div class="flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
<div class="text-center mb-6"><span class="text-4xl">🔐</span><h1 class="text-xl font-bold mt-2">License Server</h1></div>
<form id="login-form" onsubmit="return doLogin(event)">
<div class="mb-4"><input type="text" id="lg-user" placeholder="Логин" required class="input-f"></div>
<div class="mb-4"><input type="password" id="lg-pass" placeholder="Пароль" required class="input-f"></div>
<div id="lg-err" class="hidden text-red-600 text-sm mb-3 bg-red-50 border border-red-200 rounded-lg p-2"></div>
<button type="submit" id="lg-btn" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700">Войти</button>
</form>
<form id="totp-form" class="hidden" onsubmit="return do2FA(event)">
<div class="text-center mb-4"><span class="text-3xl">🔐</span><p class="text-sm text-gray-500 mt-2">Введите код из приложения</p></div>
<div class="mb-4"><input type="text" id="totp-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" class="input-f text-center text-2xl tracking-widest font-mono"></div>
<div id="totp-err" class="hidden text-red-600 text-sm mb-3 bg-red-50 border border-red-200 rounded-lg p-2"></div>
<button type="submit" id="totp-btn" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 mb-2">Подтвердить</button>
<button type="button" onclick="showBackup()" class="w-full text-gray-500 text-sm py-1">Резервный код</button>
</form>
<form id="backup-form" class="hidden" onsubmit="return doBackupCode(event)">
<div class="text-center mb-4"><span class="text-3xl">🔑</span><p class="text-sm text-gray-500 mt-2">Резервный код</p></div>
<div class="mb-4"><input type="text" id="backup-code" maxlength="8" placeholder="ABCD1234" class="input-f text-center text-lg tracking-widest font-mono uppercase"></div>
<div id="backup-err" class="hidden text-red-600 text-sm mb-3"></div>
<button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 mb-2">Войти</button>
<button type="button" onclick="showTotp()" class="w-full text-gray-500 text-sm py-1">← Код из приложения</button>
</form>
<div id="blocked-msg" class="hidden text-center py-6"><span class="text-4xl">⏳</span><p class="text-red-600 font-semibold mt-3" id="blocked-text"></p></div>
</div>
</div>
<script>
var creds={};
function doLogin(e){e.preventDefault();
creds={username:document.getElementById('lg-user').value,password:document.getElementById('lg-pass').value};
var btn=document.getElementById('lg-btn');btn.disabled=true;btn.textContent='⏳';
fetch('/admin/api?action=login',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(creds)})
.then(function(r){return r.json();}).then(function(d){
btn.disabled=false;btn.textContent='Войти';
if(d.success)location.reload();
else if(d.require_2fa){document.getElementById('login-form').classList.add('hidden');document.getElementById('totp-form').classList.remove('hidden');document.getElementById('totp-code').focus();}
else if(d.blocked){document.getElementById('login-form').classList.add('hidden');var b=document.getElementById('blocked-msg');b.classList.remove('hidden');document.getElementById('blocked-text').textContent=d.error;}
else{var el=document.getElementById('lg-err');el.textContent=d.error||'Ошибка';el.classList.remove('hidden');}
});return false;}
function do2FA(e){e.preventDefault();
var p=Object.assign({},creds,{totp_code:document.getElementById('totp-code').value});
fetch('/admin/api?action=login',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(p)})
.then(function(r){return r.json();}).then(function(d){
if(d.success)location.reload();else{var el=document.getElementById('totp-err');el.textContent=d.error||'Неверный код';el.classList.remove('hidden');}
});return false;}
function doBackupCode(e){e.preventDefault();
var p=Object.assign({},creds,{backup_code:document.getElementById('backup-code').value});
fetch('/admin/api?action=login',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify(p)})
.then(function(r){return r.json();}).then(function(d){
if(d.success)location.reload();else{var el=document.getElementById('backup-err');el.textContent=d.error||'Неверный код';el.classList.remove('hidden');}
});return false;}
function showTotp(){document.getElementById('backup-form').classList.add('hidden');document.getElementById('totp-form').classList.remove('hidden');}
function showBackup(){document.getElementById('totp-form').classList.add('hidden');document.getElementById('backup-form').classList.remove('hidden');}
</script>

<?php else: ?>
<div class="bg-gray-900 text-white"><div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center"><div class="flex items-center gap-3"><span class="text-2xl">🔑</span><h1 class="font-bold">License Server</h1></div><div class="flex items-center gap-3"><button onclick="show2FA()" class="text-gray-300 hover:text-white text-sm">🔐 2FA</button><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm">🔑 Пароль</button><button onclick="showBackups()" class="text-gray-300 hover:text-white text-sm">💾 Бэкап</button><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти</button></div></div></div>

<div class="max-w-6xl mx-auto px-4 py-8">
<div id="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8"></div>
<div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Лицензии</h2><button onclick="showCreate()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">+ Создать</button></div>
<div id="list" class="space-y-3"></div>
<h2 class="text-xl font-bold mt-10 mb-4">Последние события</h2>
<div id="logs" class="bg-white rounded-xl border overflow-hidden"></div>
</div>
<div id="M"></div>

<script>
var A='/admin/api';
function ap(u,o){return fetch(A+u,Object.assign({headers:{'Content-Type':'application/json'},credentials:'same-origin'},o||{})).then(async function(r){var t=await r.text();try{return JSON.parse(t);}catch(x){throw new Error(t.substring(0,200));}});}
function e(s){if(!s)return'';var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function modal(h){document.getElementById('M').innerHTML='<div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto" onclick="if(event.target===this)cm()"><div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:560px;margin-top:40px" onclick="event.stopPropagation()">'+h+'</div></div>';}
function cm(){document.getElementById('M').innerHTML='';}

function load(){
ap('?action=stats').then(function(d){
if(d.error)throw new Error(d.error);
var h='';[['🔑','Всего',d.total],['✅','Активных',d.active],['⏰','Истёкших',d.expired],['🚫','Отказов',d.denied_today]].forEach(function(c){
h+='<div class="bg-white rounded-xl border p-4 text-center"><p class="text-2xl font-bold">'+(c[2]||0)+'</p><p class="text-xs text-gray-500">'+c[0]+' '+c[1]+'</p></div>';});
document.getElementById('stats').innerHTML=h;
}).catch(function(x){document.getElementById('stats').innerHTML='<div class="col-span-full bg-red-50 border border-red-200 text-red-700 rounded-xl p-4">'+e(x.message)+'</div>';});

ap('?action=list').then(function(d){
if(d.error)throw new Error(d.error);
var h='';(d.licenses||[]).forEach(function(l){
var st={'active':'bg-green-100 text-green-700','expired':'bg-red-100 text-red-700','suspended':'bg-yellow-100 text-yellow-700','revoked':'bg-gray-100 text-gray-500'};
var exp=l.expires_at?new Date(l.expires_at).toLocaleDateString('ru-RU'):'∞';
h+='<div class="bg-white rounded-xl border p-4"><div class="flex items-center justify-between mb-2"><code class="text-sm font-bold bg-gray-100 px-2 py-1 rounded">'+e(l.license_key)+'</code><span class="text-xs px-2 py-0.5 rounded '+(st[l.status]||'')+'">'+(l.status)+'</span></div>';
h+='<div class="text-sm text-gray-600"><span class="font-medium">'+e(l.domain||'не привязан')+'</span> • '+e(l.plan)+' • до '+exp+'</div>';
h+='<div class="text-xs text-gray-400 mt-1">'+e(l.owner_name||'')+' '+e(l.owner_email||'')+' • Проверок 24ч: '+(l.checks_24h||0)+'</div>';
h+='<div class="flex gap-2 mt-2"><button onclick="editLic('+l.id+')" class="text-blue-600 text-xs hover:underline">Ред.</button><button onclick="showLicLogs('+l.id+')" class="text-purple-600 text-xs hover:underline">Логи</button>';
if(l.status==='active')h+='<button onclick="toggleLic('+l.id+',\'suspended\')" class="text-yellow-600 text-xs hover:underline">Приостановить</button>';
if(l.status==='suspended')h+='<button onclick="toggleLic('+l.id+',\'active\')" class="text-green-600 text-xs hover:underline">Активировать</button>';
h+='<button onclick="delLic('+l.id+')" class="text-red-500 text-xs hover:underline">Удалить</button></div></div>';});
document.getElementById('list').innerHTML=h||'<p class="text-gray-400 text-center py-8">Нет лицензий</p>';
}).catch(function(x){document.getElementById('list').innerHTML='<div class="bg-red-50 text-red-700 rounded-xl p-4">'+e(x.message)+'</div>';});

ap('?action=logs&limit=30').then(function(d){
if(d.error)throw new Error(d.error);
var h='<table class="w-full text-xs"><thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Время</th><th class="px-3 py-2">Действие</th><th class="px-3 py-2">Ключ</th><th class="px-3 py-2">Домен</th><th class="px-3 py-2">IP</th><th class="px-3 py-2">Код</th></tr></thead><tbody>';
(d.logs||[]).forEach(function(l){var ac={'activate':'text-green-600','verify':'text-blue-600','denied':'text-red-600','heartbeat':'text-gray-500'};
h+='<tr class="border-t"><td class="px-3 py-1.5">'+new Date(l.created_at).toLocaleString('ru-RU',{hour:'2-digit',minute:'2-digit',day:'2-digit',month:'2-digit'})+'</td><td class="px-3 py-1.5 font-medium '+(ac[l.action]||'')+'">'+e(l.action)+'</td><td class="px-3 py-1.5 font-mono">'+e((l.license_key||'').substr(-12))+'</td><td class="px-3 py-1.5">'+e(l.domain||'-')+'</td><td class="px-3 py-1.5 text-gray-400">'+e(l.ip||'')+'</td><td class="px-3 py-1.5">'+(l.response_code||'')+'</td></tr>';});
h+='</tbody></table>';document.getElementById('logs').innerHTML=h;
}).catch(function(x){document.getElementById('logs').innerHTML='<div class="bg-red-50 text-red-700 p-4">'+e(x.message)+'</div>';});
}

function showCreate(){modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Создать лицензию</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><form onsubmit="return createLic(event)"><div class="space-y-3"><div><label class="block text-xs font-medium mb-1">Домен</label><input id="cr-domain" class="input-f" placeholder="example.com"></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="cr-plan" class="sel-f"><option value="trial">Trial</option><option value="basic" selected>Basic</option><option value="pro">Pro</option><option value="enterprise">Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="cr-exp" class="input-f"></div></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="cr-name" class="input-f"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="cr-email" class="input-f" type="email"></div></div><div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="cr-notes" class="input-f" rows="2"></textarea></div></div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Создать</button></div></form>');}
function createLic(ev){ev.preventDefault();ap('?action=create',{method:'POST',body:JSON.stringify({domain:document.getElementById('cr-domain').value,plan:document.getElementById('cr-plan').value,expires_at:document.getElementById('cr-exp').value||null,owner_name:document.getElementById('cr-name').value,owner_email:document.getElementById('cr-email').value,notes:document.getElementById('cr-notes').value})}).then(function(d){if(d.success){cm();alert('✅ Ключ:\n'+d.license_key);load();}else alert('❌ '+d.error);});return false;}
function toggleLic(id,st){ap('?action=update',{method:'POST',body:JSON.stringify({id:id,status:st})}).then(function(){load();});}
function delLic(id){if(!confirm('Удалить лицензию?'))return;ap('?action=delete',{method:'POST',body:JSON.stringify({id:id})}).then(function(){load();});}
function editLic(id){ap('?action=list').then(function(d){var l=(d.licenses||[]).find(function(x){return x.id==id;});if(!l)return;modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><div class="mb-3"><code class="text-sm bg-gray-100 px-2 py-1 rounded block">'+e(l.license_key)+'</code></div><form onsubmit="return saveLic(event,'+id+')"><div class="space-y-3"><div><label class="block text-xs font-medium mb-1">Домен</label><input id="ed-domain" class="input-f" value="'+e(l.domain||'')+'"></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="ed-plan" class="sel-f"><option value="trial"'+(l.plan==='trial'?' selected':'')+'>Trial</option><option value="basic"'+(l.plan==='basic'?' selected':'')+'>Basic</option><option value="pro"'+(l.plan==='pro'?' selected':'')+'>Pro</option><option value="enterprise"'+(l.plan==='enterprise'?' selected':'')+'>Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Статус</label><select id="ed-status" class="sel-f"><option value="active"'+(l.status==='active'?' selected':'')+'>Active</option><option value="suspended"'+(l.status==='suspended'?' selected':'')+'>Suspended</option><option value="expired"'+(l.status==='expired'?' selected':'')+'>Expired</option><option value="revoked"'+(l.status==='revoked'?' selected':'')+'>Revoked</option></select></div></div><div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="ed-exp" class="input-f" value="'+(l.expires_at?l.expires_at.substr(0,10):'')+'"></div><div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="ed-name" class="input-f" value="'+e(l.owner_name||'')+'"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="ed-email" class="input-f" value="'+e(l.owner_email||'')+'"></div></div><div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="ed-notes" class="input-f" rows="2">'+e(l.notes||'')+'</textarea></div></div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Сохранить</button></div></form>');});}
function saveLic(ev,id){ev.preventDefault();ap('?action=update',{method:'POST',body:JSON.stringify({id:id,domain:document.getElementById('ed-domain').value,plan:document.getElementById('ed-plan').value,status:document.getElementById('ed-status').value,expires_at:document.getElementById('ed-exp').value||null,owner_name:document.getElementById('ed-name').value,owner_email:document.getElementById('ed-email').value,notes:document.getElementById('ed-notes').value})}).then(function(d){if(d.success){cm();load();}else alert('❌ '+(d.error||'Ошибка'));});return false;}
function showLicLogs(id){ap('?action=logs&license_id='+id+'&limit=50').then(function(d){var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Логи #'+id+'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><div class="max-h-96 overflow-y-auto"><table class="w-full text-xs"><thead class="bg-gray-50"><tr><th class="px-2 py-1">Время</th><th class="px-2 py-1">Действие</th><th class="px-2 py-1">Домен</th><th class="px-2 py-1">IP</th><th class="px-2 py-1">Сообщение</th></tr></thead><tbody>';(d.logs||[]).forEach(function(l){h+='<tr class="border-t"><td class="px-2 py-1">'+new Date(l.created_at).toLocaleString('ru-RU')+'</td><td class="px-2 py-1 font-medium">'+e(l.action)+'</td><td class="px-2 py-1">'+e(l.domain||'')+'</td><td class="px-2 py-1">'+e(l.ip||'')+'</td><td class="px-2 py-1 text-gray-500">'+e(l.message||'')+'</td></tr>';});h+='</tbody></table></div>';modal(h);});}

function showChangePw(){modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔑 Смена пароля</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><form onsubmit="return doChangePw(event)"><div class="space-y-4"><div><label class="block text-sm font-medium mb-1">Текущий пароль</label><input type="password" id="cp-old" class="input-f" required></div><div><label class="block text-sm font-medium mb-1">Новый пароль</label><input type="password" id="cp-new" class="input-f" required minlength="6"></div><div><label class="block text-sm font-medium mb-1">Повторите</label><input type="password" id="cp-confirm" class="input-f" required></div><div id="cp-err" class="hidden text-red-600 text-sm"></div></div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" id="cp-btn" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Сохранить</button></div></form>');}
function doChangePw(ev){ev.preventDefault();var n=document.getElementById('cp-new').value,c=document.getElementById('cp-confirm').value,err=document.getElementById('cp-err');err.classList.add('hidden');if(n!==c){err.textContent='Пароли не совпадают';err.classList.remove('hidden');return false;}ap('?action=change-password',{method:'POST',body:JSON.stringify({current_password:document.getElementById('cp-old').value,new_password:n})}).then(function(d){if(d.success){cm();alert('✅ '+d.message);}else{err.textContent=d.error;err.classList.remove('hidden');}});return false;}

function show2FA(){ap('?action=2fa-status').then(function(d){if(d.error){alert('Ошибка 2FA: '+d.error);return;}var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔐 Двухфакторная авторизация</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';if(d.enabled){h+='<div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 text-center"><span class="text-3xl">✅</span><p class="font-bold text-green-700 mt-2">2FA включена</p><p class="text-sm text-green-600">Резервных кодов: '+d.backup_codes_remaining+'</p></div><div class="space-y-3"><button onclick="tfaRegenBackup()" class="w-full bg-blue-100 hover:bg-blue-200 text-blue-700 py-2 rounded-lg text-sm font-semibold">🔄 Новые резервные коды</button><button onclick="tfaDisable()" class="w-full bg-red-100 hover:bg-red-200 text-red-700 py-2 rounded-lg text-sm font-semibold">⛔ Отключить 2FA</button></div>';}else{h+='<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4"><p class="text-yellow-700 text-sm"><strong>⚠️ 2FA не включена.</strong></p></div><div class="text-center"><button onclick="tfaSetup()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">🔐 Включить 2FA</button></div>';}modal(h);}).catch(function(err){alert('Ошибка загрузки 2FA: '+err.message);});}
function tfaSetup(){ap('?action=2fa',{method:'POST',body:JSON.stringify({action:'setup'})}).then(function(d){if(d.error){alert(d.error);return;}modal('<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔐 Настройка 2FA</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><div class="text-center mb-4"><p class="text-sm text-gray-600 mb-3">Отсканируйте QR в приложении:</p><img src="'+d.qr_url+'" style="width:250px;height:250px;margin:0 auto;border:1px solid #e5e7eb;border-radius:12px;padding:8px"><p class="text-xs text-gray-400 mt-2">Вручную: <code class="bg-gray-100 px-2 py-1 rounded font-mono text-xs">'+d.secret+'</code></p></div><div class="mt-4"><p class="text-sm mb-2">Введите 6-значный код:</p><div class="flex gap-3"><input type="text" id="tfa-code" maxlength="6" inputmode="numeric" placeholder="000000" class="input-f text-center text-2xl tracking-widest font-mono flex-1"><button onclick="tfaEnable()" id="tfa-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold">OK</button></div><div id="tfa-err" class="hidden text-red-600 text-sm mt-2"></div></div>');setTimeout(function(){var el=document.getElementById('tfa-code');if(el)el.focus();},200);});}
function tfaEnable(){var code=document.getElementById('tfa-code').value;ap('?action=2fa',{method:'POST',body:JSON.stringify({action:'enable',code:code})}).then(function(d){if(d.error){var el=document.getElementById('tfa-err');el.textContent=d.error;el.classList.remove('hidden');return;}var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">✅ 2FA включена!</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4"><p class="font-bold text-yellow-700 mb-2">⚠️ Сохраните резервные коды!</p><div class="grid grid-cols-2 gap-2 font-mono text-sm mt-2">';(d.backup_codes||[]).forEach(function(c){h+='<div class="bg-white border rounded px-3 py-1.5 text-center">'+c+'</div>';});h+='</div></div><button onclick="cm()" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold">Понятно</button>';modal(h);});}
function tfaDisable(){var pw=prompt('Пароль для отключения 2FA:');if(!pw)return;ap('?action=2fa',{method:'POST',body:JSON.stringify({action:'disable',password:pw})}).then(function(d){if(d.error)alert('❌ '+d.error);else{alert('✅ 2FA отключена');cm();}});}
function tfaRegenBackup(){var pw=prompt('Пароль:');if(!pw)return;ap('?action=2fa',{method:'POST',body:JSON.stringify({action:'regen-backup',password:pw})}).then(function(d){if(d.error){alert('❌ '+d.error);return;}var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔄 Новые коды</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div><div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4"><p class="font-bold text-yellow-700 mb-2">Старые коды недействительны!</p><div class="grid grid-cols-2 gap-2 font-mono text-sm mt-2">';(d.backup_codes||[]).forEach(function(c){h+='<div class="bg-white border rounded px-3 py-1.5 text-center">'+c+'</div>';});h+='</div></div><button onclick="cm()" class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold">OK</button>';modal(h);});}

function showBackups(){ap('?action=backup-list').then(function(d){var h='<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">💾 Бэкапы базы данных</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>';h+='<button onclick="createBackup()" id="bk-btn" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-semibold mb-4">📦 Создать бэкап</button>';h+='<div class="space-y-2 max-h-64 overflow-y-auto">';if(!d.backups||!d.backups.length)h+='<p class="text-gray-400 text-sm text-center py-4">Нет бэкапов</p>';(d.backups||[]).forEach(function(b){h+='<div class="flex items-center justify-between bg-gray-50 rounded-lg p-3"><div><p class="text-sm font-medium">'+e(b.name)+'</p><p class="text-xs text-gray-400">'+b.size+' • '+b.date+'</p></div><div class="flex gap-2"><a href="/admin/api?action=backup-download&name='+encodeURIComponent(b.name)+'" class="text-blue-600 text-xs hover:underline">Скачать</a><button onclick="delBackup(\''+e(b.name)+'\')" class="text-red-500 text-xs hover:underline">Удалить</button></div></div>';});h+='</div>';modal(h);});}
function createBackup(){var btn=document.getElementById('bk-btn');btn.disabled=true;btn.textContent='⏳ Создание...';ap('?action=backup-create',{method:'POST'}).then(function(d){btn.disabled=false;btn.textContent='📦 Создать бэкап';if(d.success){alert('✅ Бэкап: '+d.file+' ('+d.size+')');showBackups();}else alert('❌ '+(d.error||'Ошибка'));});}
function delBackup(name){if(!confirm('Удалить '+name+'?'))return;ap('?action=backup-delete',{method:'POST',body:JSON.stringify({name:name})}).then(function(){showBackups();});}

function logout(){fetch('/admin/api?action=logout',{method:'POST',credentials:'same-origin'}).then(function(){location.reload();});}
load();
</script>
<?php endif; ?>
</body>
</html>

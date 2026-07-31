<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Вход — License Server</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-900 to-gray-800 min-h-screen flex items-center justify-center p-4">
<div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
<div class="text-center mb-8"><span class="text-5xl">🔐</span><h1 class="text-2xl font-bold mt-3">License Server</h1><p class="text-gray-500 text-sm">KosmoEngine</p></div>
<form id="f1" onsubmit="return doLogin(event)">
<div class="mb-4"><label class="block text-sm font-medium mb-1">Логин</label><input type="text" name="username" required class="w-full border rounded-lg px-4 py-3" autofocus></div>
<div class="mb-6"><label class="block text-sm font-medium mb-1">Пароль</label><input type="password" name="password" required class="w-full border rounded-lg px-4 py-3"></div>
<div id="err" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
<button type="submit" id="btn1" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3 rounded-lg font-semibold">Войти</button>
</form>
<form id="f2" class="hidden" onsubmit="return do2FA(event)">
<div class="text-center mb-6"><span class="text-4xl">📱</span><h2 class="text-lg font-bold mt-2">Двухфакторная авторизация</h2></div>
<div class="mb-4"><input type="text" id="totp" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="000000" class="w-full border rounded-lg px-4 py-4 text-center text-2xl font-mono tracking-widest"></div>
<div id="err2" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
<button type="submit" id="btn2" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-3 rounded-lg font-semibold">Подтвердить</button>
</form>
<div class="mt-8 pt-4 border-t text-center"><p class="text-gray-400 text-xs">KosmoEngine License Server</p></div>
</div>
<script>
var ld={};
function doLogin(e){e.preventDefault();var f=e.target;ld={username:f.username.value,password:f.password.value};
document.getElementById('btn1').disabled=true;document.getElementById('btn1').textContent='⏳';
fetch('/api/admin/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(ld)})
.then(r=>r.json()).then(d=>{document.getElementById('btn1').disabled=false;document.getElementById('btn1').textContent='Войти';
if(d.requires_2fa){document.getElementById('f1').classList.add('hidden');document.getElementById('f2').classList.remove('hidden');document.getElementById('totp').focus();return;}
if(d.success){location.href='/admin';return;}
document.getElementById('err').textContent=d.error||'Ошибка';document.getElementById('err').classList.remove('hidden');
});return false;}
function do2FA(e){e.preventDefault();var code=document.getElementById('totp').value;
document.getElementById('btn2').disabled=true;
fetch('/api/admin/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...ld,totp_code:code})})
.then(r=>r.json()).then(d=>{document.getElementById('btn2').disabled=false;
if(d.success){location.href='/admin';return;}
document.getElementById('err2').textContent=d.error||'Неверный код';document.getElementById('err2').classList.remove('hidden');
});return false;}
</script>
</body></html>

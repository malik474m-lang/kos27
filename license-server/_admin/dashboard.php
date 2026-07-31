<?php $admin = getCurrentAdmin(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>License Server — KosmoEngine</title>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔐</text></svg>">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<style>.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2rem;overflow-y:auto;}</style>
</head>
<body class="bg-gray-100 min-h-screen">
<header class="bg-gradient-to-r from-purple-700 to-indigo-700 text-white shadow-lg">
<div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
<div class="flex items-center space-x-3"><span class="text-3xl">🔐</span><div><h1 class="text-xl font-bold">KosmoEngine License Server</h1></div></div>
<div class="flex items-center space-x-4">
<span class="text-purple-200 text-sm">👤 <?=e($admin['username']??'Admin')?></span>
<button onclick="show2FA()" class="text-purple-200 hover:text-white text-sm">🔒 2FA</button>
<button onclick="showPw()" class="text-purple-200 hover:text-white text-sm">🔑 Пароль</button>
<button onclick="logout()" class="bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded-lg text-sm">Выйти</button>
</div></div></header>
<nav class="bg-white shadow-sm border-b"><div class="max-w-7xl mx-auto px-4"><div class="flex space-x-1">
<button onclick="tab('dash')" class="tb px-4 py-3 text-sm font-medium border-b-2" data-t="dash">📊 Дашборд</button>
<button onclick="tab('lic')" class="tb px-4 py-3 text-sm font-medium border-b-2" data-t="lic">🔑 Лицензии</button>
<button onclick="tab('chk')" class="tb px-4 py-3 text-sm font-medium border-b-2" data-t="chk">📋 Проверки</button>
<button onclick="tab('aud')" class="tb px-4 py-3 text-sm font-medium border-b-2" data-t="aud">📜 Аудит</button>
</div></div></nav>
<main class="max-w-7xl mx-auto px-4 py-6">
<div id="t-dash" class="tc">
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
<div class="bg-white rounded-xl p-5 shadow-sm border"><div class="text-gray-500 text-sm">Всего лицензий</div><div class="text-3xl font-bold" id="s-total">-</div></div>
<div class="bg-white rounded-xl p-5 shadow-sm border"><div class="text-gray-500 text-sm">Активных</div><div class="text-3xl font-bold text-green-600" id="s-active">-</div></div>
<div class="bg-white rounded-xl p-5 shadow-sm border"><div class="text-gray-500 text-sm">Проверок (24ч)</div><div class="text-3xl font-bold text-blue-600" id="s-checks">-</div></div>
<div class="bg-white rounded-xl p-5 shadow-sm border"><div class="text-gray-500 text-sm">Истекают скоро</div><div class="text-3xl font-bold text-orange-500" id="s-exp">-</div></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<div class="bg-white rounded-xl p-5 shadow-sm border"><h3 class="font-semibold mb-4">По статусу</h3><canvas id="ch1" height="200"></canvas></div>
<div class="bg-white rounded-xl p-5 shadow-sm border"><h3 class="font-semibold mb-4">По тарифам</h3><canvas id="ch2" height="200"></canvas></div>
</div></div>
<div id="t-lic" class="tc hidden">
<div class="flex justify-between items-center mb-4">
<div class="flex space-x-2">
<input type="text" id="lic-q" placeholder="Поиск..." class="border rounded-lg px-3 py-2 text-sm w-64" onkeyup="if(event.key==='Enter')loadLic()">
<select id="lic-st" class="border rounded-lg px-3 py-2 text-sm" onchange="loadLic()">
<option value="">Все</option><option value="pending">Ожидает</option><option value="active">Активна</option><option value="suspended">Приостановлена</option><option value="expired">Истекла</option><option value="blocked">Заблокирована</option>
</select></div>
<button onclick="showCreate()" class="bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700">+ Создать</button>
</div>
<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr>
<th class="px-4 py-3 text-left font-medium text-gray-600">Ключ</th>
<th class="px-4 py-3 text-left font-medium text-gray-600">Домен</th>
<th class="px-4 py-3 text-left font-medium text-gray-600">Тариф</th>
<th class="px-4 py-3 text-left font-medium text-gray-600">Статус</th>
<th class="px-4 py-3 text-left font-medium text-gray-600">Истекает</th>
<th class="px-4 py-3 text-right font-medium text-gray-600">Действия</th>
</tr></thead><tbody id="lic-tb"></tbody></table></div></div>
<div id="t-chk" class="tc hidden"><div class="bg-white rounded-xl shadow-sm border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr>
<th class="px-4 py-3 text-left">Время</th><th class="px-4 py-3 text-left">Ключ</th><th class="px-4 py-3 text-left">Домен</th><th class="px-4 py-3 text-left">IP</th><th class="px-4 py-3 text-left">Статус</th>
</tr></thead><tbody id="chk-tb"><tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Загрузка...</td></tr></tbody></table></div></div>
<div id="t-aud" class="tc hidden"><div class="bg-white rounded-xl shadow-sm border overflow-hidden">
<table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr>
<th class="px-4 py-3 text-left">Время</th><th class="px-4 py-3 text-left">Админ</th><th class="px-4 py-3 text-left">Действие</th><th class="px-4 py-3 text-left">Объект</th>
</tr></thead><tbody id="aud-tb"><tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Загрузка...</td></tr></tbody></table></div></div>
</main>
<div id="modal"></div>
<script>
const A='/api/admin';
let plans=[],ch1,ch2;

function tab(n){
    document.querySelectorAll('.tc').forEach(e=>e.classList.add('hidden'));
    document.getElementById('t-'+n).classList.remove('hidden');
    document.querySelectorAll('.tb').forEach(b=>{
        b.classList.toggle('border-purple-600',b.dataset.t===n);
        b.classList.toggle('text-purple-600',b.dataset.t===n);
        b.classList.toggle('border-transparent',b.dataset.t!==n);
        b.classList.toggle('text-gray-500',b.dataset.t!==n);
    });
    if(n==='dash')loadStats();
    if(n==='lic')loadLic();
    if(n==='chk')loadChk();
    if(n==='aud')loadAud();
}

async function loadStats(){
    try {
        const[sr,pr]=await Promise.all([fetch(A+'/stats').then(r=>r.json()),fetch(A+'/plans').then(r=>r.json())]);
        plans=pr.plans||[];
        const s=sr.stats||{};
        document.getElementById('s-total').textContent=s.total_licenses||0;
        document.getElementById('s-active').textContent=s.total_active||0;
        document.getElementById('s-exp').textContent=s.expiring_soon||0;
        const c24=s.checks_24h||{};
        document.getElementById('s-checks').textContent=Object.values(c24).reduce((a,b)=>a+parseInt(b||0),0);
        const bs=s.by_status||{};
        if(ch1)ch1.destroy();
        ch1=new Chart(document.getElementById('ch1'),{type:'doughnut',data:{labels:['Активные','Ожидают','Истекшие','Заблокированные'],datasets:[{data:[bs.active||0,bs.pending||0,bs.expired||0,bs.blocked||0],backgroundColor:['#10b981','#6366f1','#f59e0b','#ef4444']}]},options:{plugins:{legend:{position:'bottom'}}}});
        const bp=s.by_plan||[];
        if(ch2)ch2.destroy();
        ch2=new Chart(document.getElementById('ch2'),{type:'bar',data:{labels:bp.map(p=>p.name),datasets:[{data:bp.map(p=>p.cnt),backgroundColor:'#8b5cf6'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
    } catch(e) { console.error('loadStats error:', e); }
}

async function loadLic(){
    try {
        const q=document.getElementById('lic-q').value,st=document.getElementById('lic-st').value;
        const r=await fetch(`${A}/licenses?search=${encodeURIComponent(q)}&status=${st}`).then(r=>r.json());
        document.getElementById('lic-tb').innerHTML=(r.licenses||[]).map(l=>`<tr class="border-b hover:bg-gray-50">
        <td class="px-4 py-3 font-mono text-xs">${esc(l.license_key)}</td>
        <td class="px-4 py-3">${esc(l.domain||'—')}</td>
        <td class="px-4 py-3">${esc(l.plan_name)}</td>
        <td class="px-4 py-3">${badge(l.status)}</td>
        <td class="px-4 py-3 text-gray-500">${l.expires_at?fmtDate(l.expires_at):'∞'}</td>
        <td class="px-4 py-3 text-right"><button onclick="editLic(${l.id})" class="text-blue-600 hover:underline text-xs mr-2">Изм.</button><button onclick="delLic(${l.id})" class="text-red-600 hover:underline text-xs">Уд.</button></td>
        </tr>`).join('')||'<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Нет данных</td></tr>';
    } catch(e) { console.error('loadLic error:', e); }
}

function showCreate(){
    const opts=plans.map(p=>`<option value="${p.id}">${esc(p.name)} (${p.duration_days?p.duration_days+'дн.':'∞'})</option>`).join('');
    modal(`<h2 class="text-xl font-bold mb-4">Создать лицензию</h2>
    <form onsubmit="return createLic(event)">
    <div class="mb-4"><label class="block text-sm font-medium mb-1">Тариф</label><select name="plan_id" required class="w-full border rounded-lg px-3 py-2">${opts}</select></div>
    <div class="mb-4"><label class="block text-sm font-medium mb-1">Домен (опционально)</label><input type="text" name="domain" placeholder="example.com" class="w-full border rounded-lg px-3 py-2"></div>
    <div class="grid grid-cols-2 gap-4 mb-4">
    <div><label class="block text-sm font-medium mb-1">Имя</label><input type="text" name="owner_name" class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="owner_email" class="w-full border rounded-lg px-3 py-2"></div></div>
    <div class="mb-4"><label class="block text-sm font-medium mb-1">Срок действия</label><input type="datetime-local" name="expires_at" class="w-full border rounded-lg px-3 py-2"></div>
    <div class="flex justify-end space-x-3"><button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Отмена</button><button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Создать</button></div>
    </form>`);
}

async function createLic(e){
    e.preventDefault();
    const f=e.target;
    try {
        const r=await fetch(A+'/licenses/create',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({plan_id:f.plan_id.value,domain:f.domain.value,owner_name:f.owner_name.value,owner_email:f.owner_email.value,expires_at:f.expires_at.value||null})}).then(r=>r.json());
        if(r.success){closeModal();modal(`<div class="text-center py-4"><span class="text-5xl">✅</span><h2 class="text-xl font-bold mt-4 mb-2">Лицензия создана!</h2><div class="bg-gray-100 rounded-lg p-4 my-4 font-mono text-lg select-all">${esc(r.license.license_key)}</div><button onclick="closeModal();loadLic();" class="px-6 py-2 bg-purple-600 text-white rounded-lg">OK</button></div>`);}
        else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка соединения'); }
    return false;
}

async function editLic(id){
    try {
        const r=await fetch(A+'/licenses').then(r=>r.json());
        const l=r.licenses.find(x=>x.id==id);
        if(!l)return;
        const po=plans.map(p=>`<option value="${p.id}" ${p.id==l.plan_id?'selected':''}>${esc(p.name)}</option>`).join('');
        const so=['pending','active','suspended','expired','blocked'].map(s=>`<option value="${s}" ${s===l.status?'selected':''}>${s}</option>`).join('');
        modal(`<h2 class="text-xl font-bold mb-4">Редактировать</h2><p class="font-mono text-sm bg-gray-100 p-2 rounded mb-4">${esc(l.license_key)}</p>
        <form onsubmit="return updLic(event,${id})">
        <div class="grid grid-cols-2 gap-4 mb-4"><div><label class="block text-sm font-medium mb-1">Тариф</label><select name="plan_id" class="w-full border rounded-lg px-3 py-2">${po}</select></div>
        <div><label class="block text-sm font-medium mb-1">Статус</label><select name="status" class="w-full border rounded-lg px-3 py-2">${so}</select></div></div>
        <div class="mb-4"><label class="block text-sm font-medium mb-1">Домен</label><input type="text" name="domain" value="${esc(l.domain||'')}" class="w-full border rounded-lg px-3 py-2"></div>
        <div class="mb-4"><label class="block text-sm font-medium mb-1">Истекает</label><input type="datetime-local" name="expires_at" value="${l.expires_at?(l.expires_at.replace(' ','T').slice(0,16)):''}" class="w-full border rounded-lg px-3 py-2"></div>
        <div class="mb-4"><label class="block text-sm font-medium mb-1">Причина блокировки</label><input type="text" name="block_reason" value="${esc(l.block_reason||'')}" class="w-full border rounded-lg px-3 py-2"></div>
        <div class="flex justify-end space-x-3"><button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Отмена</button><button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Сохранить</button></div>
        </form>`);
    } catch(e) { alert('Ошибка'); }
}

async function updLic(e,id){
    e.preventDefault();
    const f=e.target;
    try {
        const r=await fetch(A+'/licenses/update',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,plan_id:f.plan_id.value,status:f.status.value,domain:f.domain.value||null,expires_at:f.expires_at.value||null,block_reason:f.block_reason.value||null})}).then(r=>r.json());
        if(r.success){closeModal();loadLic();}else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка'); }
    return false;
}

async function delLic(id){
    if(!confirm('Удалить?'))return;
    try {
        const r=await fetch(A+'/licenses/delete',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}).then(r=>r.json());
        if(r.success)loadLic();else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка'); }
}

async function loadChk(){
    try {
        const r=await fetch(A+'/checks').then(r=>r.json());
        console.log('checks response:', r);
        const checks = r.checks || [];
        if(checks.length === 0) {
            document.getElementById('chk-tb').innerHTML='<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Нет данных</td></tr>';
            return;
        }
        document.getElementById('chk-tb').innerHTML=checks.map(c=>`<tr class="border-b hover:bg-gray-50">
        <td class="px-4 py-3 text-gray-500 text-xs">${fmtDate(c.created_at)}</td>
        <td class="px-4 py-3 font-mono text-xs">${esc((c.license_key||'').substring(0,15))}...</td>
        <td class="px-4 py-3">${esc(c.domain)}</td>
        <td class="px-4 py-3 text-gray-500 text-xs">${esc(c.ip)}</td>
        <td class="px-4 py-3">${chkBadge(c.status)}</td>
        </tr>`).join('');
    } catch(e) {
        console.error('loadChk error:', e);
        document.getElementById('chk-tb').innerHTML='<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">Ошибка загрузки</td></tr>';
    }
}

async function loadAud(){
    try {
        const r=await fetch(A+'/audit').then(r=>r.json());
        console.log('audit response:', r);
        const logs = r.logs || [];
        if(logs.length === 0) {
            document.getElementById('aud-tb').innerHTML='<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">Нет данных</td></tr>';
            return;
        }
        document.getElementById('aud-tb').innerHTML=logs.map(l=>`<tr class="border-b hover:bg-gray-50">
        <td class="px-4 py-3 text-gray-500 text-xs">${fmtDate(l.created_at)}</td>
        <td class="px-4 py-3">${esc(l.username||'—')}</td>
        <td class="px-4 py-3 font-medium">${esc(l.action)}</td>
        <td class="px-4 py-3 text-gray-500">${l.entity_type?esc(l.entity_type)+' #'+l.entity_id:'—'}</td>
        </tr>`).join('');
    } catch(e) {
        console.error('loadAud error:', e);
        document.getElementById('aud-tb').innerHTML='<tr><td colspan="4" class="px-4 py-8 text-center text-red-500">Ошибка загрузки</td></tr>';
    }
}

async function show2FA(){
    try {
        const me=await fetch(A+'/me').then(r=>r.json());
        if(me.admin?.totp_enabled){
            modal(`<h2 class="text-xl font-bold mb-4">🔒 2FA включена</h2>
            <form onsubmit="return dis2FA(event)"><div class="mb-4"><label class="block text-sm font-medium mb-1">Пароль для отключения</label><input type="password" name="pw" required class="w-full border rounded-lg px-3 py-2"></div>
            <div class="flex justify-end space-x-3"><button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Отмена</button><button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Отключить</button></div></form>`);
        } else {
            const s=await fetch(A+'/2fa/setup').then(r=>r.json());
            modal(`<h2 class="text-xl font-bold mb-4">🔒 Настройка 2FA</h2><div class="text-center mb-4"><img src="${s.qr_url}" class="mx-auto rounded-lg"><p class="text-xs text-gray-500 mt-2">Или: <code class="bg-gray-100 px-2 py-1 rounded">${s.secret}</code></p></div>
            <form onsubmit="return en2FA(event)"><div class="mb-4"><input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required class="w-full border rounded-lg px-3 py-3 text-center text-xl font-mono" placeholder="000000"></div>
            <div class="flex justify-end space-x-3"><button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Отмена</button><button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Включить</button></div></form>`);
        }
    } catch(e) { alert('Ошибка'); }
}

async function en2FA(e){
    e.preventDefault();
    try {
        const r=await fetch(A+'/2fa/enable',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({code:e.target.code.value})}).then(r=>r.json());
        if(r.success){modal(`<div class="text-center"><span class="text-5xl">✅</span><h2 class="text-xl font-bold mt-4 mb-2">2FA включена!</h2><p class="text-gray-600 mb-4">Резервные коды:</p><div class="bg-gray-100 rounded-lg p-4 font-mono text-sm grid grid-cols-2 gap-2">${r.backup_codes.map(c=>`<div>${c}</div>`).join('')}</div><button onclick="closeModal()" class="mt-4 px-6 py-2 bg-purple-600 text-white rounded-lg">OK</button></div>`);}
        else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка'); }
    return false;
}

async function dis2FA(e){
    e.preventDefault();
    try {
        const r=await fetch(A+'/2fa/disable',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:e.target.pw.value})}).then(r=>r.json());
        if(r.success){closeModal();alert('2FA отключена');}else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка'); }
    return false;
}

function showPw(){
    modal(`<h2 class="text-xl font-bold mb-4">🔑 Смена пароля</h2>
    <form onsubmit="return chgPw(event)"><div class="mb-4"><label class="block text-sm font-medium mb-1">Текущий</label><input type="password" name="cur" required class="w-full border rounded-lg px-3 py-2"></div>
    <div class="mb-4"><label class="block text-sm font-medium mb-1">Новый (мин. 8 симв.)</label><input type="password" name="new" required minlength="8" class="w-full border rounded-lg px-3 py-2"></div>
    <div class="flex justify-end space-x-3"><button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg">Отмена</button><button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Сменить</button></div></form>`);
}

async function chgPw(e){
    e.preventDefault();
    try {
        const r=await fetch(A+'/change-password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({current_password:e.target.cur.value,new_password:e.target.new.value})}).then(r=>r.json());
        if(r.success){closeModal();alert('Пароль изменён');}else alert(r.error||'Ошибка');
    } catch(e) { alert('Ошибка'); }
    return false;
}

async function logout(){
    await fetch(A+'/logout',{method:'POST'});
    location.href='/admin/login';
}

function modal(h){document.getElementById('modal').innerHTML=`<div class="modal-bg" onclick="if(event.target===this)closeModal()"><div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-lg mt-8">${h}</div></div>`;}
function closeModal(){document.getElementById('modal').innerHTML='';}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function fmtDate(d){if(!d)return'';return new Date(d.replace(' ','T')).toLocaleString('ru-RU',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});}
function badge(s){const c={active:'bg-green-100 text-green-700',pending:'bg-blue-100 text-blue-700',expired:'bg-yellow-100 text-yellow-700',blocked:'bg-red-100 text-red-700',suspended:'bg-purple-100 text-purple-700'};return`<span class="px-2 py-1 rounded-full text-xs font-medium ${c[s]||'bg-gray-100'}">${s}</span>`;}
function chkBadge(s){const c={success:'bg-green-100 text-green-700',invalid_key:'bg-red-100 text-red-700',domain_mismatch:'bg-orange-100 text-orange-700',expired:'bg-yellow-100 text-yellow-700',blocked:'bg-red-100 text-red-700'};return`<span class="px-2 py-1 rounded-full text-xs font-medium ${c[s]||'bg-gray-100'}">${s}</span>`;}

// Init
tab('dash');
</script>
</body></html>

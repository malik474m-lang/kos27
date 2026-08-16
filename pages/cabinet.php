<?php
require_once __DIR__ . '/../includes/user-auth.php';
$user = getUser();
if (!$user) { header('Location: /login'); exit; }

$pageTitle = 'Личный кабинет — ' . SITE_NAME;
$metaDescription = 'Личный кабинет пользователя сайта ' . SITE_NAME . ': избранные предложения, история заявок и настройки аккаунта.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Личный кабинет', '/cabinet')];
$pageHeadHtml = '<meta name="robots" content="noindex,follow">';
ob_start();
?>
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <?= renderBreadcrumbs($breadcrumbs) ?>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Личный кабинет</h1>
            <p class="text-gray-500 text-sm mt-1" id="cab-email"></p>
        </div>
        <button onclick="fetch('/api/user/logout',{method:'POST'}).then(()=>location.href='/')" class="text-sm text-gray-500 hover:text-red-500">Выйти →</button>
    </div>

    <div id="cab-content"><p class="text-gray-500 text-center py-12">Загрузка...</p></div>
</section>

<script>
function normLogo(u){if(!u)return'';if(u.indexOf('/public/')===0)return u.substring(7);return u;}
function fmtDate(d){try{return new Date(d).toLocaleString('ru-RU');}catch(e){return d;}}
function loadCabinet(){
    fetch('/api/user/profile').then(r=>{if(r.status===401){location.href='/login';return;}return r.json();}).then(d=>{
        if(!d)return;
        var p=d.profile||{};
        var apps=d.applications||[];
        document.getElementById('cab-email').textContent=p.email||'';

        var h='<div class="grid sm:grid-cols-3 gap-4 mb-8">';
        h+='<div class="bg-white rounded-xl border p-5 text-center"><p class="text-2xl font-bold text-blue-600">'+apps.length+'</p><p class="text-xs text-gray-500">Заявок</p></div>';
        var approved=apps.filter(a=>a.status==='approved').length;
        var rejected=apps.filter(a=>a.status==='rejected').length;
        h+='<div class="bg-white rounded-xl border p-5 text-center"><p class="text-2xl font-bold text-green-600">'+approved+'</p><p class="text-xs text-gray-500">Одобрено</p></div>';
        h+='<div class="bg-white rounded-xl border p-5 text-center"><p class="text-2xl font-bold text-red-600">'+rejected+'</p><p class="text-xs text-gray-500">Отклонено</p></div>';
h+='<div class="bg-white rounded-xl border p-5 text-center"><p class="text-2xl font-bold text-amber-600">'+(d.bonus_balance||0)+'</p><p class="text-xs text-gray-500">КосмоБонусы (₽)</p></div>';
        h+='</div>';

        if(d.bonus_history&&d.bonus_history.length){
        h+='<div class="bg-white rounded-xl border mt-6"><div class="p-4 border-b"><h2 class="font-bold text-gray-900">🎁 История КосмоБонусов</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Оффер</th><th class="p-3 text-right">Сумма</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
        d.bonus_history.forEach(function(b){
            var st=b.status==='confirmed'?'<span class="text-green-600">Начислено</span>':(b.status==='pending'?'<span class="text-yellow-600">Ожидание</span>':'<span class="text-red-500">Отменено</span>');
            var sign=Number(b.amount)>0?'+':'';
            h+='<tr class="border-t"><td class="p-3 text-gray-500">'+new Date(b.created_at).toLocaleDateString('ru-RU')+'</td><td class="p-3">'+(b.offer_title||'—')+'</td><td class="p-3 text-right font-semibold '+(Number(b.amount)>=0?'text-amber-600':'text-red-600')+'">'+sign+b.amount+' ₽</td><td class="p-3">'+st+'</td></tr>';
        });
        h+='</tbody></table></div></div>';
    }

    h+='<div class="bg-white rounded-xl border mt-6 p-6">';
    h+='<h2 class="font-bold text-gray-900 mb-2">💸 Заявка на вывод КосмоБонусов</h2>';
    h+='<p class="text-sm text-gray-500 mb-4">Укажите банк, номер телефона, привязанный к карте, и имя владельца карты. 1 бонус = 1 ₽.</p>';
    if((d.bonus_balance||0) > 0){
      h+='<form onsubmit="return submitBonusWithdraw(event)" class="grid sm:grid-cols-2 gap-4">';
      h+='<div><label class="block text-sm font-medium text-gray-700 mb-1">Сумма вывода</label><input type="number" id="bw-amount-user" min="1" max="'+(d.bonus_balance||0)+'" value="'+(d.bonus_balance||0)+'" class="w-full border border-gray-300 rounded-lg px-4 py-2.5" required></div>';
      h+='<div><label class="block text-sm font-medium text-gray-700 mb-1">Название банка</label><input type="text" id="bw-bank" class="w-full border border-gray-300 rounded-lg px-4 py-2.5" placeholder="Например, Сбербанк" required></div>';
      h+='<div><label class="block text-sm font-medium text-gray-700 mb-1">Телефон, привязанный к карте</label><input type="text" id="bw-phone" class="w-full border border-gray-300 rounded-lg px-4 py-2.5" placeholder="+7 9XX XXX-XX-XX" required></div>';
      h+='<div><label class="block text-sm font-medium text-gray-700 mb-1">Имя владельца карты</label><input type="text" id="bw-cardholder" class="w-full border border-gray-300 rounded-lg px-4 py-2.5" placeholder="Иван Иванов" required></div>';
      h+='<div class="sm:col-span-2"><button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-lg font-semibold">Отправить заявку на вывод</button></div>';
      h+='</form>';
    } else {
      h+='<p class="text-sm text-gray-400">У вас пока нет доступных бонусов для вывода.</p>';
    }
    h+='</div>';

    if(d.bonus_withdraw_requests&&d.bonus_withdraw_requests.length){
      h+='<div class="bg-white rounded-xl border mt-6"><div class="p-4 border-b"><h2 class="font-bold text-gray-900">📋 Мои заявки на вывод</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Банк</th><th class="p-3 text-left">Телефон</th><th class="p-3 text-left">Получатель</th><th class="p-3 text-right">Сумма</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
      d.bonus_withdraw_requests.forEach(function(r){
        var st=r.status==='paid'?'<span class="text-green-600">Выплачено</span>':(r.status==='pending'?'<span class="text-yellow-600">На проверке</span>':'<span class="text-red-500">Отклонено</span>');
        h+='<tr class="border-t"><td class="p-3 text-gray-500">'+new Date(r.created_at).toLocaleDateString('ru-RU')+'</td><td class="p-3">'+(r.bank_name||'—')+'</td><td class="p-3">'+(r.phone||'—')+'</td><td class="p-3">'+(r.cardholder_name||'—')+'</td><td class="p-3 text-right font-semibold">'+r.amount+' ₽</td><td class="p-3">'+st+'</td></tr>';
      });
      h+='</tbody></table></div></div>';
    }
    if(apps.length){
            h+='<div class="bg-white rounded-xl border"><div class="p-4 border-b"><h2 class="font-bold text-gray-900">Мои заявки</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-gray-50"><tr><th class="p-3 text-left">Дата</th><th class="p-3 text-left">Предложение</th><th class="p-3 text-left">Статус</th></tr></thead><tbody>';
            apps.forEach(function(a){
                var stBadge=a.status==='approved'?'bg-green-100 text-green-700':a.status==='rejected'?'bg-red-100 text-red-700':'bg-yellow-100 text-yellow-700';
                var stLabel=a.status==='approved'?'Одобрено':a.status==='rejected'?'Отклонено':'На рассмотрении';
                var logo=normLogo(a.logo_url);
                h+='<tr class="border-t hover:bg-gray-50"><td class="p-3 text-xs text-gray-500 whitespace-nowrap">'+fmtDate(a.created_at)+'</td>';
                h+='<td class="p-3"><div class="flex items-center gap-2">';
                if(logo)h+='<img src="'+logo+'" class="w-6 h-6 rounded object-contain" loading="lazy">';
                h+='<a href="/offer/'+(a.offer_slug||'')+'" class="font-medium text-primary hover:underline">'+(a.offer_title||'—')+'</a></div></td>';
                h+='<td class="p-3"><span class="px-2 py-0.5 rounded text-xs font-semibold '+stBadge+'">'+stLabel+'</span></td></tr>';
            });
            h+='</tbody></table></div></div>';
        } else {
            h+='<div class="bg-white rounded-xl border p-8 text-center"><p class="text-gray-500">У вас пока нет заявок</p><a href="/zajmy" class="inline-block mt-4 bg-primary text-white px-6 py-2 rounded-lg font-semibold hover:bg-primary-dark">Смотреть предложения</a></div>';
        }
        document.getElementById('cab-content').innerHTML=h;
    }).catch(function(){document.getElementById('cab-content').innerHTML='<p class="text-red-500 text-center">Ошибка загрузки</p>';});
}
function submitBonusWithdraw(ev){
    ev.preventDefault();
    var amount=parseInt(document.getElementById('bw-amount-user').value)||0;
    var bank=document.getElementById('bw-bank').value||'';
    var phone=document.getElementById('bw-phone').value||'';
    var cardholder=document.getElementById('bw-cardholder').value||'';
    fetch('/api/user/bonus-withdraw',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({amount:amount,bank_name:bank,phone:phone,cardholder_name:cardholder})
    }).then(function(r){return r.json();}).then(function(d){
        if(d.error){alert(d.error);return;}
        alert('✅ Заявка на вывод отправлена. Новый доступный баланс: '+(d.new_balance||0)+' ₽');
        loadCabinet();
    }).catch(function(){alert('Ошибка отправки заявки');});
    return false;
}

loadCabinet();
</script>
<?php
$jsonLdSchemas = [jsonLdBreadcrumb($breadcrumbs)];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

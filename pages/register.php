<?php
$pageTitle = 'Регистрация — ' . SITE_NAME;
$metaDescription = 'Регистрация личного кабинета на сайте ' . SITE_NAME . ' для сохранения избранного, сравнения предложений и отслеживания заявок.';
$pageHeadHtml = '<meta name="robots" content="noindex,follow">';
ob_start();
?>
<section class="max-w-md mx-auto px-4 py-12">
    <nav class="text-sm text-gray-500 mb-6"><a href="/" class="hover:text-primary">Главная</a> → Регистрация</nav>
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Создать аккаунт</h1>

    <div id="reg-step-1" class="bg-white rounded-2xl border border-gray-100 p-6">
        <form onsubmit="return regSubmit(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                <input type="text" id="reg-name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Иван">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" id="reg-email" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="email@example.com">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль * (минимум 6 символов)</label>
                <input type="password" id="reg-pass" required minlength="6" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="space-y-3 mb-6 text-sm text-gray-600">
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="reg-terms" required class="mt-0.5 w-4 h-4 rounded">
                    <span>Соглашаюсь с <a href="/terms" target="_blank" class="text-primary underline">Пользовательским соглашением</a>, условиями партнёров, предоставляю своё согласие на обработку персональных данных и на участие в Программе лояльности.</span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="reg-marketing" class="mt-0.5 w-4 h-4 rounded">
                    <span>Предоставляю своё согласие на получение рекламы и информационных сообщений.</span>
                </label>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" id="reg-finance" required class="mt-0.5 w-4 h-4 rounded">
                    <span>Соглашаюсь с <a href="/disclaimer" target="_blank" class="text-primary underline">Правилами финансовой платформы</a>, Правилами ЭДО.</span>
                </label>
            </div>

            <div id="reg-error" class="hidden mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"></div>
            <button type="submit" id="reg-btn" class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors">Зарегистрироваться</button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-4">Уже есть аккаунт? <a href="/login" class="text-primary hover:underline">Войти</a></p>
    </div>

    <div id="reg-step-2" class="hidden bg-white rounded-2xl border border-gray-100 p-6 text-center">
        <span class="text-4xl block mb-4">📧</span>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Введите код подтверждения</h2>
        <p class="text-sm text-gray-500 mb-2">Мы отправили 6-значный код на <strong id="reg-sent-email"></strong></p>
        <p class="text-xs text-yellow-600 mb-4 bg-yellow-50 rounded-lg p-2">⚠️ Если письмо не пришло — проверьте папку <strong>Спам</strong></p>
        <input type="text" id="reg-code" maxlength="6" class="w-full text-center text-2xl tracking-[0.5em] font-bold border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" placeholder="000000">
        <div id="verify-error" class="hidden mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"></div>
        <button onclick="verifyCode()" id="verify-btn" class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors">Подтвердить</button>
    </div>
</section>

<script>
var regEmail='';
function regSubmit(e){
    e.preventDefault();
    var err=document.getElementById('reg-error');err.classList.add('hidden');
    var btn=document.getElementById('reg-btn');btn.disabled=true;btn.textContent='Отправка...';
    regEmail=document.getElementById('reg-email').value.trim();
    fetch('/api/user/register',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        email:regEmail,password:document.getElementById('reg-pass').value,name:document.getElementById('reg-name').value.trim(),
        agreedTerms:document.getElementById('reg-terms').checked,agreedMarketing:document.getElementById('reg-marketing').checked,agreedFinance:document.getElementById('reg-finance').checked
    })}).then(r=>r.json()).then(d=>{
        btn.disabled=false;btn.textContent='Зарегистрироваться';
        if(d.error){err.textContent=d.error;err.classList.remove('hidden');return;}
        document.getElementById('reg-step-1').classList.add('hidden');
        document.getElementById('reg-step-2').classList.remove('hidden');
        document.getElementById('reg-sent-email').textContent=regEmail;
    }).catch(()=>{btn.disabled=false;btn.textContent='Зарегистрироваться';err.textContent='Ошибка соединения';err.classList.remove('hidden');});
    return false;
}
function verifyCode(){
    var err=document.getElementById('verify-error');err.classList.add('hidden');
    var btn=document.getElementById('verify-btn');btn.disabled=true;btn.textContent='Проверка...';
    fetch('/api/user/verify',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        email:regEmail,code:document.getElementById('reg-code').value.trim()
    })}).then(r=>r.json()).then(d=>{
        btn.disabled=false;btn.textContent='Подтвердить';
        if(d.error){err.textContent=d.error;err.classList.remove('hidden');return;}
        location.href='/cabinet';
    }).catch(()=>{btn.disabled=false;btn.textContent='Подтвердить';err.textContent='Ошибка';err.classList.remove('hidden');});
}
</script>
<?php
$jsonLdSchemas = [jsonLdBreadcrumb([['name'=>'Главная','url'=>'/'],['name'=>'Регистрация','url'=>'/register']])];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

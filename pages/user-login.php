<?php
$pageTitle = 'Вход — ' . SITE_NAME;
$metaDescription = 'Вход в личный кабинет сайта ' . SITE_NAME . ' для управления избранными предложениями и заявками.';
$breadcrumbs = [breadcrumbItem('Главная', '/'), breadcrumbItem('Вход', '/login')];
$pageHeadHtml = '<meta name="robots" content="noindex,follow">';
ob_start();
?>
<section class="max-w-md mx-auto px-4 py-12">
    <?= renderBreadcrumbs($breadcrumbs) ?>
    <h1 class="text-2xl font-bold text-gray-900 mb-6 text-center">Войти в аккаунт</h1>
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <form onsubmit="return loginSubmit(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="login-email" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" autofocus>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" id="login-pass" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="login-error" class="hidden mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200"></div>
            <button type="submit" id="login-btn" class="w-full bg-primary text-white py-3 rounded-lg font-semibold hover:bg-primary-dark transition-colors">Войти</button>
        </form>
        <p class="text-center text-sm text-gray-500 mt-4">Нет аккаунта? <a href="/register" class="text-primary hover:underline">Зарегистрироваться</a></p>
    </div>
</section>
<script>
function loginSubmit(e){
    e.preventDefault();
    var err=document.getElementById('login-error');err.classList.add('hidden');
    var btn=document.getElementById('login-btn');btn.disabled=true;btn.textContent='Вход...';
    fetch('/api/user/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        email:document.getElementById('login-email').value.trim(),password:document.getElementById('login-pass').value
    })}).then(r=>r.json()).then(d=>{
        btn.disabled=false;btn.textContent='Войти';
        if(d.error){err.textContent=d.error;err.classList.remove('hidden');return;}
        location.href='/cabinet';
    }).catch(()=>{btn.disabled=false;btn.textContent='Войти';err.textContent='Ошибка';err.classList.remove('hidden');});
    return false;
}
</script>
<?php
$jsonLdSchemas = [jsonLdBreadcrumb($breadcrumbs)];
$content = ob_get_clean();
require __DIR__ . '/../includes/layout.php';

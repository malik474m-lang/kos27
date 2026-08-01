<?php
require_once __DIR__ . '/../includes/minify.php';
ob_start('minifyHtmlOutput');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход — KosmoEngine</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/tailwind.css?v=20260801">
    <style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <span class="text-4xl">🚀</span>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">KosmoEngine</h1>
            <p class="text-gray-500 text-sm">Панель управления</p>
        </div>

        <!-- Шаг 1: Логин/пароль -->
        <form id="login-form" onsubmit="return handleLogin(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" autofocus>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="error" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
            <button type="submit" id="login-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">Войти</button>
        </form>

        <!-- Шаг 2: 2FA код -->
        <form id="totp-form" class="hidden" onsubmit="return handle2FA(event)">
            <div class="text-center mb-4">
                <span class="text-3xl">🔐</span>
                <h2 class="text-lg font-bold text-gray-900 mt-2">Двухфакторная авторизация</h2>
                <p class="text-gray-500 text-sm">Введите код из приложения</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Код из приложения</label>
                <input type="text" id="totp-code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" autofocus>
            </div>
            <div id="totp-error" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
            <button type="submit" id="totp-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors mb-3">Подтвердить</button>
            <button type="button" onclick="showBackupForm()" class="w-full text-gray-500 hover:text-gray-700 text-sm py-2">Использовать резервный код</button>
        </form>

        <!-- Шаг 2б: Резервный код -->
        <form id="backup-form" class="hidden" onsubmit="return handleBackup(event)">
            <div class="text-center mb-4">
                <span class="text-3xl">🔑</span>
                <h2 class="text-lg font-bold text-gray-900 mt-2">Резервный код</h2>
                <p class="text-gray-500 text-sm">Введите один из резервных кодов</p>
            </div>
            <div class="mb-4">
                <input type="text" id="backup-code" maxlength="8" placeholder="ABCD1234" class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-lg tracking-widest font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="backup-error" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
            <button type="submit" id="backup-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors mb-3">Войти</button>
            <button type="button" onclick="showTotpForm()" class="w-full text-gray-500 hover:text-gray-700 text-sm py-2">← Ввести код из приложения</button>
        </form>

        <!-- Блокировка -->
        <div id="blocked-msg" class="hidden text-center py-6">
            <span class="text-4xl">⏳</span>
            <p class="text-red-600 font-semibold mt-3" id="blocked-text"></p>
            <p class="text-gray-500 text-sm mt-2">Осталось: <span id="blocked-timer" class="font-mono font-bold">--:--</span></p>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-gray-200 text-center">
            <p class="text-gray-400 text-xs">KosmoEngine &copy; <?= date('Y') ?></p>
            <p class="text-gray-400 text-xs mt-1">Разработчик: Рудаков Юрий</p>
        </div>
    </div>

    <script>
    var loginData = {};
    var blockedUntil = 0;

    function handleLogin(e) {
        e.preventDefault();
        var form = e.target;
        loginData = {username: form.username.value, password: form.password.value};
        var btn = document.getElementById('login-btn');
        btn.disabled = true; btn.textContent = '⏳';

        fetch('/api/admin/login', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(loginData)})
            .then(function(r){return r.json();})
            .then(function(d){
                btn.disabled = false; btn.textContent = 'Войти';
                if (d.blocked) {
                    blockedUntil = Date.now() + (d.remaining || 900) * 1000;
                    showBlocked(d.error || 'Слишком много попыток');
                    return;
                }
                if (d.requires_2fa) {
                    document.getElementById('login-form').classList.add('hidden');
                    document.getElementById('totp-form').classList.remove('hidden');
                    document.getElementById('totp-code').focus();
                    return;
                }
                if (d.success) {
                    window.location.href = '/admin';
                    return;
                }
                showError('error', d.error || 'Неверный логин или пароль');
            })
            .catch(function(){
                btn.disabled = false; btn.textContent = 'Войти';
                showError('error', 'Ошибка соединения');
            });
        return false;
    }

    function handle2FA(e) {
        e.preventDefault();
        var code = document.getElementById('totp-code').value;
        var btn = document.getElementById('totp-btn');
        btn.disabled = true; btn.textContent = '⏳';

        fetch('/api/admin/login', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:loginData.username, password:loginData.password, totp_code:code})})
            .then(function(r){return r.json();})
            .then(function(d){
                btn.disabled = false; btn.textContent = 'Подтвердить';
                if (d.success) {
                    window.location.href = '/admin';
                    return;
                }
                showError('totp-error', d.error || 'Неверный код');
            })
            .catch(function(){
                btn.disabled = false; btn.textContent = 'Подтвердить';
                showError('totp-error', 'Ошибка соединения');
            });
        return false;
    }

    function handleBackup(e) {
        e.preventDefault();
        var code = document.getElementById('backup-code').value;
        var btn = document.getElementById('backup-btn');
        btn.disabled = true; btn.textContent = '⏳';

        fetch('/api/admin/login', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({username:loginData.username, password:loginData.password, backup_code:code})})
            .then(function(r){return r.json();})
            .then(function(d){
                btn.disabled = false; btn.textContent = 'Войти';
                if (d.success) {
                    window.location.href = '/admin';
                    return;
                }
                showError('backup-error', d.error || 'Неверный код');
            })
            .catch(function(){
                btn.disabled = false; btn.textContent = 'Войти';
                showError('backup-error', 'Ошибка соединения');
            });
        return false;
    }

    function showError(id, msg) {
        var el = document.getElementById(id);
        el.textContent = msg;
        el.classList.remove('hidden');
    }

    function showBackupForm() {
        document.getElementById('totp-form').classList.add('hidden');
        document.getElementById('backup-form').classList.remove('hidden');
        document.getElementById('backup-code').focus();
    }

    function showTotpForm() {
        document.getElementById('backup-form').classList.add('hidden');
        document.getElementById('totp-form').classList.remove('hidden');
        document.getElementById('totp-code').focus();
    }

    function showBlocked(msg) {
        document.getElementById('login-form').classList.add('hidden');
        document.getElementById('totp-form').classList.add('hidden');
        document.getElementById('backup-form').classList.add('hidden');
        document.getElementById('blocked-text').textContent = msg;
        document.getElementById('blocked-msg').classList.remove('hidden');
        updateBlockTimer();
    }

    function updateBlockTimer() {
        var remaining = Math.max(0, Math.floor((blockedUntil - Date.now()) / 1000));
        if (remaining <= 0) {
            location.reload();
            return;
        }
        var m = Math.floor(remaining / 60);
        var s = remaining % 60;
        document.getElementById('blocked-timer').textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        setTimeout(updateBlockTimer, 1000);
    }
    </script>
</body>
</html>
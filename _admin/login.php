<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход в админку — Космозайм</title>
    <script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
    <style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <span class="text-4xl">\xf0\x9f\x9a\x80</span>
            <h1 class="text-2xl font-bold text-gray-900 mt-2">Админ-панель</h1>
            <p class="text-gray-500 text-sm">Космозайм</p>
        </div>
        <form id="login-form" onsubmit="return handleLogin(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Логин</label>
                <input type="text" name="username" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500" autofocus>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="error" class="hidden text-red-600 text-sm mb-4"></div>
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">Войти</button>
        </form>
    </div>
    <script>
    function handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const data = {username: form.username.value, password: form.password.value};
        fetch('/api/admin/login', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data)})
            .then(r => r.json())
            .then(d => {
                if (d.success) window.location.href = '/admin';
                else { document.getElementById('error').textContent = d.error || 'Ошибка'; document.getElementById('error').classList.remove('hidden'); }
            }).catch(() => { document.getElementById('error').textContent = 'Ошибка соединения'; document.getElementById('error').classList.remove('hidden'); });
        return false;
    }
    </script>
</body>
</html>

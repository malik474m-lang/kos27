<?php
/**
 * Страница управления лицензией в админке
 */
require_once __DIR__ . '/../includes/license.php';

// Если не авторизован — сначала логинимся
if (!isAdmin()) {
    header('Location: /admin/login');
    exit;
}

$licenseInfo = getLicenseInfo();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Лицензия — KosmoEngine</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-lg">
            <div class="text-center mb-8">
                <span class="text-5xl">🔐</span>
                <h1 class="text-2xl font-bold text-gray-900 mt-3">Лицензия KosmoEngine</h1>
            </div>

            <?php if ($licenseInfo['active']): ?>
            <!-- Лицензия активна -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
                <div class="flex items-center mb-4">
                    <span class="text-3xl mr-3">✅</span>
                    <div>
                        <h2 class="font-bold text-green-800">Лицензия активна</h2>
                        <p class="text-green-600 text-sm">Тариф: <?= e($licenseInfo['plan']) ?></p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ключ:</span>
                        <span class="font-mono"><?= e($licenseInfo['key']) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Домен:</span>
                        <span><?= e($licenseInfo['domain']) ?></span>
                    </div>
                    <?php if ($licenseInfo['expires_at']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Истекает:</span>
                        <span><?= date('d.m.Y H:i', strtotime($licenseInfo['expires_at'])) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Срок:</span>
                        <span class="text-green-600">Бессрочно</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Проверка:</span>
                        <span><?= $licenseInfo['last_check'] ? date('d.m.Y H:i', $licenseInfo['last_check']) : '—' ?></span>
                    </div>
                </div>
                <?php if ($licenseInfo['grace'] ?? false): ?>
                <div class="mt-4 bg-yellow-100 text-yellow-800 text-xs px-3 py-2 rounded-lg">
                    ⚠️ Grace-режим (нет связи с сервером лицензий)
                </div>
                <?php endif; ?>
            </div>

            <div class="flex space-x-3">
                <a href="/admin" class="flex-1 bg-gray-900 text-white py-3 rounded-lg font-semibold text-center hover:bg-gray-800">← В админку</a>
                <button onclick="forceCheck()" id="check-btn" class="px-4 py-3 border border-blue-300 text-blue-600 rounded-lg hover:bg-blue-50 text-sm">🔄 Проверить</button>
                <button onclick="removeLicense()" class="px-4 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm">Удалить</button>
            </div>

            <?php else: ?>
            <!-- Лицензия не активна -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">⚠️</span>
                    <div>
                        <h2 class="font-bold text-red-800">Лицензия не активна</h2>
                        <p class="text-red-600 text-sm"><?= e($licenseInfo['reason'] ?? ($licenseInfo['message'] ?? 'Введите ключ')) ?></p>
                    </div>
                </div>
            </div>

            <form onsubmit="return activateLicense(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ключ лицензии</label>
                    <input type="text" id="license-key" placeholder="XXXX-XXXX-XXXX-XXXX" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center font-mono text-lg tracking-wider uppercase focus:outline-none focus:ring-2 focus:ring-blue-500"
                        autocomplete="off" spellcheck="false">
                </div>
                <div id="error" class="hidden text-red-600 text-sm mb-4 bg-red-50 border border-red-200 rounded-lg p-3"></div>
                <div id="success" class="hidden text-green-600 text-sm mb-4 bg-green-50 border border-green-200 rounded-lg p-3"></div>
                <button type="submit" id="submit-btn" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700">
                    Активировать лицензию
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-400 text-xs">Домен: <?= e(getCurrentDomain()) ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-8 pt-4 border-t text-center">
                <p class="text-gray-400 text-xs">KosmoEngine © <?= date('Y') ?></p>
            </div>
        </div>
    </div>

    <script>
    function activateLicense(e) {
        e.preventDefault();
        const key = document.getElementById('license-key').value.trim();
        if (!key) { showError('Введите ключ лицензии'); return false; }

        const btn = document.getElementById('submit-btn');
        btn.disabled = true; btn.textContent = '⏳ Проверка...';
        hideMessages();

        fetch('/api/admin/license', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'activate', license_key: key})
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; btn.textContent = 'Активировать лицензию';
            if (data.success) {
                showSuccess(data.message || 'Лицензия активирована!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showError(data.error || 'Ошибка активации');
            }
        })
        .catch(() => {
            btn.disabled = false; btn.textContent = 'Активировать лицензию';
            showError('Ошибка соединения');
        });
        return false;
    }

    function forceCheck() {
        const btn = document.getElementById('check-btn');
        btn.disabled = true; btn.textContent = '⏳';
        fetch('/api/admin/license', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'check'})
        })
        .then(r => r.json())
        .then(() => location.reload())
        .catch(() => location.reload());
    }

    function removeLicense() {
        if (!confirm('Удалить лицензию? Сайт перестанет работать.')) return;
        fetch('/api/admin/license', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'remove'})
        }).then(() => location.reload());
    }

    function showError(msg) { const el = document.getElementById('error'); el.textContent = msg; el.classList.remove('hidden'); }
    function showSuccess(msg) { const el = document.getElementById('success'); el.textContent = msg; el.classList.remove('hidden'); }
    function hideMessages() { document.getElementById('error')?.classList.add('hidden'); document.getElementById('success')?.classList.add('hidden'); }
    </script>
</body>
</html>

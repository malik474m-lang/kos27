<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$phpVersion = phpversion();
$mysqlVersion = '';
try {
    $db = getDB();
    $mysqlVersion = $db->query("SELECT VERSION()")->fetchColumn();
    $totalOffers = $db->query("SELECT COUNT(*) FROM offers")->fetchColumn();
    $totalClicks = $db->query("SELECT COUNT(*) FROM click_stats")->fetchColumn();
    $totalArticles = 0;
    try { $totalArticles = $db->query("SELECT COUNT(*) FROM articles")->fetchColumn(); } catch (Exception $e) {}
    $totalReviews = 0;
    try { $totalReviews = $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn(); } catch (Exception $e) {}
} catch (Exception $e) {
    $mysqlVersion = 'Ошибка подключения';
    $totalOffers = $totalClicks = $totalArticles = $totalReviews = '—';
}

$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно';
$uptime = @file_get_contents('/proc/uptime');
$uptimeStr = '—';
if ($uptime) {
    $sec = (int)explode(' ', $uptime)[0];
    $days = floor($sec / 86400);
    $hours = floor(($sec % 86400) / 3600);
    $uptimeStr = "{$days} дн. {$hours} ч.";
}

require_once __DIR__ . '/../includes/minify.php';
ob_start('minifyHtmlOutput');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>О системе — KosmoEngine</title>
    <script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
    <script>tailwind.config={theme:{extend:{colors:{primary:'#1a56db','primary-dark':'#1244af',accent:'#059669'}}}}</script>
    <style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}</style>
</head>
<body class="bg-gray-100 min-h-screen">

<!-- Шапка -->
<div class="bg-gray-900 text-white">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-2xl">🚀</span>
            <h1 class="text-lg font-bold">KosmoEngine</h1>
            <span class="text-gray-400 text-sm">/ О системе</span>
        </div>
        <div class="flex items-center space-x-4">
            <a href="/admin" class="text-gray-300 hover:text-white text-sm">← Панель управления</a>
            <button onclick="if(confirm('Выйти?')){fetch('/api/admin/logout',{method:'POST'}).then(()=>location.href='/admin/login')}" class="text-gray-300 hover:text-white text-sm">Выйти ↗</button>
        </div>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 py-8">

    <!-- Лого и название -->
    <div class="bg-white rounded-2xl shadow-sm p-8 mb-6 text-center">
        <div class="text-6xl mb-4">🚀</div>
        <h1 class="text-3xl font-bold text-gray-900">KosmoEngine</h1>
        <p class="text-gray-500 mt-2 text-lg">Система управления финансовыми предложениями</p>
        <div class="mt-4 inline-flex items-center bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-medium">
            Версия 2.7 &bull; Сборка kos27
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">

        <!-- Информация о системе -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <span class="mr-2">⚙️</span> Системная информация
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">PHP</span>
                    <span class="font-mono text-sm"><?= e($phpVersion) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">MySQL</span>
                    <span class="font-mono text-sm"><?= e($mysqlVersion) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Веб-сервер</span>
                    <span class="font-mono text-sm text-right max-w-[200px] truncate"><?= e($serverSoftware) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Аптайм сервера</span>
                    <span class="font-mono text-sm"><?= e($uptimeStr) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Кодировка</span>
                    <span class="font-mono text-sm">UTF-8</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Часовой пояс</span>
                    <span class="font-mono text-sm"><?= e(date_default_timezone_get()) ?></span>
                </div>
            </div>
        </div>

        <!-- Статистика контента -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <span class="mr-2">📊</span> Контент
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-700"><?= $totalOffers ?></div>
                    <div class="text-blue-600 text-sm mt-1">Предложений</div>
                </div>
                <div class="bg-green-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-700"><?= number_format((int)$totalClicks, 0, '', ' ') ?></div>
                    <div class="text-green-600 text-sm mt-1">Кликов</div>
                </div>
                <div class="bg-purple-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-700"><?= $totalArticles ?></div>
                    <div class="text-purple-600 text-sm mt-1">Статей</div>
                </div>
                <div class="bg-amber-50 rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-amber-700"><?= $totalReviews ?></div>
                    <div class="text-amber-600 text-sm mt-1">Отзывов</div>
                </div>
            </div>
        </div>

        <!-- Разработчик -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <span class="mr-2">👨‍💻</span> Разработчик
            </h2>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Имя</span>
                    <span class="font-semibold">Рудаков Юрий</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-500">Email</span>
                    <a href="mailto:malik474@yandex.ru" class="text-blue-600 hover:underline">malik474@yandex.ru</a>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Поддержка</span>
                    <a href="mailto:malik474@yandex.ru" class="text-blue-600 hover:underline">Написать</a>
                </div>
            </div>
        </div>

        <!-- Лицензия -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <span class="mr-2">📜</span> Лицензия
            </h2>
            <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600 leading-relaxed">
                <p class="font-semibold text-gray-800 mb-2">KosmoEngine — Proprietary License</p>
                <p class="mb-2">
                    Данное программное обеспечение является собственностью разработчика 
                    <strong>Рудакова Юрия</strong> и защищено законодательством об авторском праве.
                </p>
                <p class="mb-2">
                    Запрещается копирование, распространение, декомпиляция или модификация 
                    исходного кода без письменного разрешения правообладателя.
                </p>
                <p>
                    Все права защищены &copy; <?= date('Y') ?>
                </p>
            </div>
        </div>

    </div>

    <!-- Возможности системы -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mt-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
            <span class="mr-2">🛠️</span> Возможности KosmoEngine
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">📋</div>
                <h3 class="font-semibold text-gray-800 mb-1">Управление офферами</h3>
                <p class="text-gray-500 text-sm">Займы, кредиты, карты. Сортировка, категории, теги.</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">📈</div>
                <h3 class="font-semibold text-gray-800 mb-1">Аналитика и статистика</h3>
                <p class="text-gray-500 text-sm">Клики, конверсии, воронки, A/B тесты.</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">🤖</div>
                <h3 class="font-semibold text-gray-800 mb-1">AI-генерация контента</h3>
                <p class="text-gray-500 text-sm">Yandex GPT для статей и отзывов. Автоматический планировщик.</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">🌍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Гео-таргетинг</h3>
                <p class="text-gray-500 text-sm">Определение города пользователя. SEO по городам.</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">🔐</div>
                <h3 class="font-semibold text-gray-800 mb-1">Безопасность</h3>
                <p class="text-gray-500 text-sm">2FA, IP-whitelist, аудит-лог, защита от брутфорса.</p>
            </div>
            <div class="border border-gray-200 rounded-xl p-4">
                <div class="text-xl mb-2">💾</div>
                <h3 class="font-semibold text-gray-800 mb-1">Бэкапы</h3>
                <p class="text-gray-500 text-sm">Резервное копирование БД и файлов. Восстановление.</p>
            </div>
        </div>
    </div>

</div>

<!-- Футер -->
<footer class="border-t border-gray-200 mt-8 py-6 text-center">
    <p class="text-gray-400 text-sm">KosmoEngine v2.7 &copy; <?= date('Y') ?> — Разработчик: Рудаков Юрий</p>
    <p class="text-gray-400 text-xs mt-1">
        <a href="mailto:malik474@yandex.ru" class="hover:text-gray-600">malik474@yandex.ru</a>
    </p>
</footer>

</body>
</html>
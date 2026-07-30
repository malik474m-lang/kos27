<?php
/**
 * KosmoEngine — Патч брендинга для dashboard.php
 * 
 * Запустить ОДИН РАЗ через SSH:
 *   cd ~/domains/kosmozaim.ru
 *   php _admin/patch-dashboard.php
 *
 * Или через браузер (только если авторизован в админке):
 *   https://kosmozaim.ru/admin/patch-dashboard
 * 
 * Скрипт сделает резервную копию и заменит брендинг.
 */

// Если запуск через браузер — проверяем авторизацию
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config.php';
    requireAdmin();
    header('Content-Type: text/plain; charset=utf-8');
}

$file = __DIR__ . '/dashboard.php';

if (!file_exists($file)) {
    echo "ОШИБКА: Файл dashboard.php не найден!\n";
    exit(1);
}

// Резервная копия
$backup = __DIR__ . '/dashboard.php.bak.' . date('Ymd_His');
if (!copy($file, $backup)) {
    echo "ОШИБКА: Не удалось создать резервную копию!\n";
    exit(1);
}
echo "✅ Резервная копия: " . basename($backup) . "\n";

$content = file_get_contents($file);
$original = $content;
$changes = 0;

// === ЗАМЕНЫ ===

// 1. Title страницы
$search = '<title>Админ-панель — Космозайм</title>';
$replace = '<title>KosmoEngine — Панель управления</title>';
if (str_contains($content, $search)) {
    $content = str_replace($search, $replace, $content);
    $changes++;
    echo "✅ Title заменён\n";
} else {
    echo "⚠️  Title не найден (возможно уже заменён)\n";
}

// 2. Заголовок в шапке (h1)
$search = 'Админ-панель Космозайм</h1>';
$replace = 'KosmoEngine</h1>';
if (str_contains($content, $search)) {
    $content = str_replace($search, $replace, $content);
    $changes++;
    echo "✅ Заголовок шапки заменён\n";
} else {
    echo "⚠️  Заголовок шапки не найден (возможно уже заменён)\n";
}

// 3. Иконка в шапке ⚙️ → 🚀
$search = '<span class="text-2xl">⚙️</span>';
$replace = '<span class="text-2xl">🚀</span>';
if (str_contains($content, $search)) {
    $content = str_replace($search, $replace, $content);
    $changes++;
    echo "✅ Иконка шапки заменена ⚙️ → 🚀\n";
} else {
    echo "⚠️  Иконка шапки не найдена\n";
}

// 4. Добавляем кнопку "О системе" в шапку (рядом с кнопкой "Выйти")
$search = 'Выйти ↗</button></div></div>';
$replace = 'Выйти ↗</button><a href="/admin/about" class="text-gray-300 hover:text-white text-sm ml-4">ℹ️ О системе</a></div></div>';
if (str_contains($content, $search)) {
    $content = str_replace($search, $replace, $content);
    $changes++;
    echo "✅ Кнопка «О системе» добавлена в шапку\n";
} else {
    echo "⚠️  Место для кнопки «О системе» не найдено\n";
}

// 5. Добавляем footer перед </body>
$footerHtml = '
<footer class="border-t border-gray-200 mt-8 py-4 text-center bg-white">
<p class="text-gray-400 text-sm">KosmoEngine v2.7 &copy; ' . date('Y') . ' — Разработчик: Рудаков Юрий</p>
<p class="text-gray-400 text-xs mt-1"><a href="mailto:malik474@yandex.ru" class="hover:text-gray-600">malik474@yandex.ru</a> &bull; <a href="/admin/about" class="hover:text-gray-600">О системе</a></p>
</footer>';

if (!str_contains($content, 'KosmoEngine v2.7')) {
    $content = str_replace('</body>', $footerHtml . "\n</body>", $content);
    $changes++;
    echo "✅ Footer добавлен\n";
} else {
    echo "⚠️  Footer уже существует\n";
}

// === СОХРАНЕНИЕ ===
if ($changes > 0) {
    file_put_contents($file, $content);
    echo "\n🎉 Готово! Применено замен: {$changes}\n";
    echo "Резервная копия сохранена в: " . basename($backup) . "\n";
} else {
    echo "\nℹ️  Изменений не требуется — всё уже обновлено.\n";
    @unlink($backup); // Удаляем ненужный бэкап
}

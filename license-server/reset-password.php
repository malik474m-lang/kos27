<?php
/**
 * Скрипт сброса пароля администратора
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * 1. Откройте в браузере: https://serv.kosmozaim.ru/reset-password.php
 * 2. Скопируйте новый пароль
 * 3. ОБЯЗАТЕЛЬНО УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!
 */

require_once __DIR__ . '/config.php';

$newPassword = 'admin123';
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $db = getDB();
    
    // Сбрасываем пароль и отключаем 2FA
    $stmt = $db->prepare("UPDATE admins SET password_hash = ?, totp_enabled = 0, totp_secret = NULL, backup_codes = NULL WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    $affected = $stmt->rowCount();
    
    // Проверяем что запись существует
    $check = $db->query("SELECT id, username, password_hash FROM admins WHERE username = 'admin'");
    $admin = $check->fetch();
    
    // Очищаем блокировки IP
    $db->exec("DELETE FROM login_attempts");
    
    echo "<pre>\n";
    echo "==============================================\n";
    echo "  СБРОС ПАРОЛЯ АДМИНИСТРАТОРА\n";
    echo "==============================================\n\n";
    
    if ($affected > 0) {
        echo "✅ Пароль успешно сброшен!\n\n";
    } else {
        echo "⚠️  Строки не обновлены.\n\n";
    }
    
    echo "Логин:   admin\n";
    echo "Пароль:  admin123\n\n";
    echo "Хеш:    " . $hash . "\n\n";
    
    // Верификация
    if ($admin) {
        echo "Проверка: admin найден в БД (id={$admin['id']})\n";
        $verify = password_verify('admin123', $admin['password_hash']);
        echo "Верификация пароля: " . ($verify ? "✅ ОК" : "❌ ОШИБКА") . "\n\n";
    } else {
        echo "❌ Пользователь admin НЕ НАЙДЕН в таблице admins!\n";
        echo "   Создаю нового...\n";
        $db->prepare("INSERT INTO admins (username, password_hash) VALUES ('admin', ?)")->execute([$hash]);
        echo "   ✅ Пользователь создан!\n\n";
    }
    
    echo "2FA: отключена\n";
    echo "Блокировки IP: очищены\n\n";
    echo "==============================================\n";
    echo "⚠️  УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!\n";
    echo "    rm ~/domains/serv.kosmozaim.ru/reset-password.php\n";
    echo "==============================================\n";
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<pre>\n";
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "\nПроверьте настройки .env\n";
    echo "</pre>";
}

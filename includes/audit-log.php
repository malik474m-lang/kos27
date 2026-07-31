<?php
/**
 * Аудит-лог: запись действий админов
 */

/**
 * Записать действие в аудит-лог
 */
function auditLog(string $action, string $entity, ?int $entityId = null, ?string $entityName = null, ?array $changes = null): void {
    try {
        $db = getDB();

        // Получаем данные админа из сессии
        startAdminSession();
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Unknown';

        $ip = getClientIp();
        $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        // Проверяем/создаём таблицу
        try {
            $db->query("SELECT 1 FROM admin_audit_log LIMIT 1");
        } catch (Exception $e) {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `admin_audit_log` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `admin_id` int(11) DEFAULT NULL,
                  `admin_name` varchar(100) DEFAULT NULL,
                  `action` varchar(50) NOT NULL,
                  `entity` varchar(50) NOT NULL,
                  `entity_id` int(11) DEFAULT NULL,
                  `entity_name` varchar(255) DEFAULT NULL,
                  `changes` text DEFAULT NULL,
                  `ip` varchar(45) DEFAULT NULL,
                  `user_agent` varchar(500) DEFAULT NULL,
                  `created_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `idx_entity` (`entity`, `entity_id`),
                  KEY `idx_action` (`action`),
                  KEY `idx_admin` (`admin_id`),
                  KEY `idx_created` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }

        $stmt = $db->prepare("
            INSERT INTO admin_audit_log 
            (admin_id, admin_name, action, entity, entity_id, entity_name, changes, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $adminId,
            $adminName,
            $action,
            $entity,
            $entityId,
            $entityName ? mb_substr($entityName, 0, 255) : null,
            $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
            $ip,
            $userAgent
        ]);
    } catch (Exception $e) {
        // Не прерываем работу при ошибке логирования
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Сравнить старые и новые данные, вернуть изменения
 */
function auditDiff(array $old, array $new, array $fields = []): array {
    $changes = [];
    $checkFields = $fields ?: array_unique(array_merge(array_keys($old), array_keys($new)));

    foreach ($checkFields as $field) {
        $oldVal = $old[$field] ?? null;
        $newVal = $new[$field] ?? null;

        $oldStr = is_array($oldVal) ? json_encode($oldVal) : (string)$oldVal;
        $newStr = is_array($newVal) ? json_encode($newVal) : (string)$newVal;

        if ($oldStr !== $newStr) {
            $changes[$field] = [
                'old' => $oldVal,
                'new' => $newVal
            ];
        }
    }

    return $changes;
}

/**
 * Человекочитаемое название действия
 */
function auditActionLabel(string $action): string {
    switch ($action) {
        case 'create': return 'Создание';
        case 'update': return 'Изменение';
        case 'delete': return 'Удаление';
        case 'enable': return 'Включение';
        case 'disable': return 'Отключение';
        case 'send': return 'Отправка';
        case 'apply': return 'Применение';
        case 'reorder': return 'Сортировка';
        case 'generate': return 'Генерация';
        case 'login': return 'Вход';
        case 'logout': return 'Выход';
        default: return $action;
    }
}

/**
 * Человекочитаемое название сущности
 */
function auditEntityLabel(string $entity): string {
    switch ($entity) {
        case 'offer': return 'Оффер';
        case 'article': return 'Статья';
        case 'review': return 'Отзыв';
        case 'tag': return 'Тег';
        case 'category': return 'Категория';
        case 'newsletter': return 'Рассылка';
        case 'postback': return 'Postback';
        case 'postback_profile': return 'Профиль Postback';
        case 'smart_rating': return 'Умный рейтинг';
        case 'ab_test': return 'A/B тест';
        case 'city_seo': return 'SEO города';
        case 'settings': return 'Настройки';
        case 'subscriber': return 'Подписчик';
        case 'user': return 'Пользователь';
        case 'admin': return 'Администратор';
        case 'geo_redirect': return 'Гео-редирект';
        case 'batch': return 'Пакетная операция';
        default: return $entity;
    }
}

/**
 * Иконка для действия
 */
function auditActionIcon(string $action): string {
    switch ($action) {
        case 'create': return '➕';
        case 'update': return '✏️';
        case 'delete': return '🗑️';
        case 'enable': return '✅';
        case 'disable': return '⛔';
        case 'send': return '📤';
        case 'apply': return '⚡';
        case 'reorder': return '↕️';
        case 'generate': return '🤖';
        case 'login': return '🔑';
        case 'logout': return '🚪';
        default: return '📝';
    }
}

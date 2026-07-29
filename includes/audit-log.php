<?php
/**
 * Аудит-лог: запись действий админов
 */

/**
 * Записать действие в аудит-лог
 * 
 * @param string $action Действие: create, update, delete, send, apply, enable, disable
 * @param string $entity Сущность: offer, tag, category, newsletter, postback, smart_rating, article, review, etc.
 * @param int|null $entityId ID сущности
 * @param string|null $entityName Название сущности (для истории)
 * @param array|null $changes Изменения ['field' => ['old' => ..., 'new' => ...]]
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
        
        // Приводим к строкам для сравнения
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
    return match($action) {
        'create' => 'Создание',
        'update' => 'Изменение',
        'delete' => 'Удаление',
        'enable' => 'Включение',
        'disable' => 'Отключение',
        'send' => 'Отправка',
        'apply' => 'Применение',
        'reorder' => 'Сортировка',
        'generate' => 'Генерация',
        'login' => 'Вход',
        'logout' => 'Выход',
        default => $action
    };
}

/**
 * Человекочитаемое название сущности
 */
function auditEntityLabel(string $entity): string {
    return match($entity) {
        'offer' => 'Оффер',
        'article' => 'Статья',
        'review' => 'Отзыв',
        'tag' => 'Тег',
        'category' => 'Категория',
        'newsletter' => 'Рассылка',
        'postback' => 'Postback',
        'postback_profile' => 'Профиль Postback',
        'smart_rating' => 'Умный рейтинг',
        'ab_test' => 'A/B тест',
        'city_seo' => 'SEO города',
        'settings' => 'Настройки',
        'subscriber' => 'Подписчик',
        'user' => 'Пользователь',
        'admin' => 'Администратор',
        'geo_redirect' => 'Гео-редирект',
        'batch' => 'Пакетная операция',
        default => $entity
    };
}

/**
 * Иконка для действия
 */
function auditActionIcon(string $action): string {
    return match($action) {
        'create' => '➕',
        'update' => '✏️',
        'delete' => '🗑️',
        'enable' => '✅',
        'disable' => '⛔',
        'send' => '📤',
        'apply' => '⚡',
        'reorder' => '↕️',
        'generate' => '🤖',
        'login' => '🔑',
        'logout' => '🚪',
        default => '📝'
    };
}

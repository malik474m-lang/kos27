<?php
if (apiCacheStart('admin_users', 10)) exit;
$db = getDB();

try {
    $users = $db->query("
        SELECT u.id, u.email, u.name, u.is_verified, u.last_login_at, u.last_login_ip,
               u.agreed_terms, u.agreed_marketing, u.agreed_finance, u.created_at,
               (SELECT COUNT(*) FROM user_applications WHERE user_id = u.id) as app_count,
               (SELECT COUNT(*) FROM user_applications WHERE user_id = u.id AND status = 'approved') as approved_count
        FROM users u
        ORDER BY u.created_at DESC
        LIMIT 200
    ")->fetchAll();
    apiCacheEnd($users);
} catch (Exception $e) {
    // Если таблица user_applications не существует — выводим без подсчёта
    try {
        $users = $db->query("SELECT *, 0 as app_count, 0 as approved_count FROM users ORDER BY created_at DESC LIMIT 200")->fetchAll();
        apiCacheEnd($users);
    } catch (Exception $e2) {
        apiCacheEnd([]);
    }
}

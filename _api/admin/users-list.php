<?php
if (apiCacheStart('admin_users', 20)) exit;
$db = getDB();
$users = $db->query("SELECT u.*, (SELECT COUNT(*) FROM user_applications ua WHERE ua.user_id = u.id) as app_count, (SELECT COUNT(*) FROM user_applications ua WHERE ua.user_id = u.id AND ua.status = 'approved') as approved_count FROM users ORDER BY u.created_at DESC LIMIT 200")->fetchAll();
apiCacheEnd($users);

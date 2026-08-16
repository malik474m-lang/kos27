<?php
require_once __DIR__ . '/../../includes/user-auth.php';
require_once __DIR__ . '/../../includes/kosmobonus.php';
header('Content-Type: application/json; charset=UTF-8');
$user = getUser();
if (!$user) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

$db = getDB();
$u = $db->prepare("SELECT id, email, name, created_at FROM users WHERE id = ?");
$u->execute([$user['id']]);
$profile = $u->fetch();

$apps = $db->prepare("
    SELECT ua.*, o.title as offer_title, o.slug as offer_slug, o.logo_url
    FROM user_applications ua
    LEFT JOIN offers o ON ua.offer_id = o.id
    WHERE ua.user_id = ?
    ORDER BY ua.created_at DESC LIMIT 50
");
$apps->execute([$user['id']]);
$applications = $apps->fetchAll();

echo json_encode(['profile' => $profile, 'applications' => $applications, 'bonus_balance' => kosmoBonusBalance((int)$user['id']), 'bonus_history' => kosmoBonusHistory((int)$user['id'], 20), 'bonus_withdraw_requests' => kosmoBonusWithdrawRequestsByUser((int)$user['id'], 20)]);

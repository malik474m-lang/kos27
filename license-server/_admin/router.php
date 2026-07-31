<?php
$p = substr($uri, 6);
if ($p === '/login' || $p === '/login/') { if (isAdmin()) { header('Location: /admin'); exit; } require __DIR__ . '/login.php'; exit; }
if ($p === '' || $p === '/') { if (!isAdmin()) { header('Location: /admin/login'); exit; } require __DIR__ . '/dashboard.php'; exit; }
requireAdmin();
if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== getClientIp()) { session_destroy(); header('Location: /admin/login'); exit; }
require __DIR__ . '/dashboard.php';

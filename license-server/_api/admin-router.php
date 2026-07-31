<?php
$ap = substr($apiPath ?? substr($uri, 4), 7);
if ($ap === 'login') { require __DIR__ . '/admin/login.php'; exit; }
requireAdmin();
switch ($ap) {
    case 'logout': session_destroy(); jsonResponse(['success' => true]); break;
    case 'me': jsonResponse(['admin' => getCurrentAdmin()]); break;
    case 'change-password': require __DIR__ . '/admin/change-password.php'; break;
    case '2fa/setup': require __DIR__ . '/admin/2fa-setup.php'; break;
    case '2fa/enable': require __DIR__ . '/admin/2fa-enable.php'; break;
    case '2fa/disable': require __DIR__ . '/admin/2fa-disable.php'; break;
    case 'licenses': case 'licenses/list': require __DIR__ . '/admin/licenses-list.php'; break;
    case 'licenses/create': require __DIR__ . '/admin/licenses-create.php'; break;
    case 'licenses/update': require __DIR__ . '/admin/licenses-update.php'; break;
    case 'licenses/delete': require __DIR__ . '/admin/licenses-delete.php'; break;
    case 'plans': case 'plans/list': require __DIR__ . '/admin/plans-list.php'; break;
    case 'stats': require __DIR__ . '/admin/stats.php'; break;
    case 'audit': require __DIR__ . '/admin/audit.php'; break;
    case 'checks': require __DIR__ . '/admin/checks.php'; break;
    default: jsonResponse(['error' => 'Unknown'], 404);
}

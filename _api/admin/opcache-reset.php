<?php
$reset = false;
$enabled = function_exists('opcache_reset');
$status = null;

if ($enabled) {
    try {
        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
        $reset = @opcache_reset();
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'enabled' => true]);
        exit;
    }
}

echo json_encode([
    'success' => (bool)$reset,
    'enabled' => $enabled,
    'message' => $enabled
        ? ($reset ? 'OPcache успешно сброшен' : 'OPcache не удалось сбросить')
        : 'opcache_reset() недоступен на этом хостинге',
    'opcache_enabled' => (bool)($status['opcache_enabled'] ?? false),
]);

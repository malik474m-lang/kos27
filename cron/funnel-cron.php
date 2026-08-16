<?php
/**
 * Email-воронка — cron задача
 * Запуск: php cron/funnel-cron.php
 * Рекомендуемый интервал: каждый час
 * Или через URL: /api/cron-generate?secret=CRON_SECRET&action=funnel
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/email-funnel.php';

$result = processFunnel(30);

if (php_sapi_name() === 'cli') {
    echo "Email funnel: sent={$result['sent']}, errors={$result['errors']}\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($result);
}

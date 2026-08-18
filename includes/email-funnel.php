<?php
/**
 * Email-воронка — автоматические цепочки писем
 */

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/newsletter-helpers.php';

function ensureFunnelTables(PDO $db): bool {
    static $checked = false;
    if ($checked) return true;
    try {
        $db->query("SELECT 1 FROM email_funnel_steps LIMIT 1");
        $db->query("SELECT 1 FROM email_funnel_log LIMIT 1");
        $checked = true;
        return true;
    } catch (Exception $e) {
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS `email_funnel_steps` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `subject` varchar(500) NOT NULL,
                  `body_html` text NOT NULL,
                  `delay_hours` int(11) NOT NULL DEFAULT 24,
                  `step_order` int(11) NOT NULL DEFAULT 0,
                  `is_active` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
            $db->exec("
                CREATE TABLE IF NOT EXISTS `email_funnel_log` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `subscriber_id` int(11) NOT NULL,
                  `step_id` int(11) NOT NULL,
                  `email` varchar(255) NOT NULL,
                  `status` enum('sent','failed','skipped') NOT NULL DEFAULT 'sent',
                  `error_message` varchar(500) DEFAULT NULL,
                  `sent_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `idx_subscriber_step` (`subscriber_id`, `step_id`),
                  KEY `idx_sent_at` (`sent_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
            $checked = true;
            return true;
        } catch (Exception $e2) {
            return false;
        }
    }
}

function seedDefaultFunnelSteps(PDO $db): void {
    $count = (int)$db->query("SELECT COUNT(*) FROM email_funnel_steps")->fetchColumn();
    if ($count > 0) return;

    $steps = [
        ['Приветствие', 'Добро пожаловать в ' . SITE_NAME . '!',
         '<h2 style="color:#1a56db">Добро пожаловать! 👋</h2><p>Спасибо за подписку на ' . SITE_NAME . '!</p><p>Мы поможем вам найти лучшие финансовые предложения:</p><ul><li>💵 Займы от 0% для новых клиентов</li><li>🏦 Кредиты с низкой ставкой</li><li>💳 Кредитные карты с кэшбеком</li></ul><p><a href="' . SITE_URL . '/zajmy" style="display:inline-block;background:#1a56db;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Посмотреть предложения →</a></p>{{offers}}',
         0, 1],
        ['Лучшие предложения', '🔥 Лучшие предложения этой недели',
         '<h2>Не пропустите лучшие условия!</h2><p>Подобрали для вас самые выгодные предложения:</p>{{offers}}<p>💡 <strong>Совет:</strong> подавайте 2-3 заявки одновременно.</p>',
         48, 2],
        ['Как выбрать займ', '📋 Как выбрать займ и не переплатить',
         '<h2>5 правил выгодного займа</h2><ol><li><strong>Сравнивайте ПСК</strong></li><li><strong>Берите первый займ под 0%</strong></li><li><strong>Проверяйте лицензию ЦБ</strong></li><li><strong>Не берите больше</strong>, чем можете вернуть</li><li><strong>Используйте калькулятор</strong></li></ol>{{offers}}',
         120, 3],
        ['Напоминание', '💰 Специальные условия ждут!',
         '<h2>Вы ещё не оформили заявку?</h2><p>Вот обновлённый список с лучшими условиями:</p>{{offers}}<p>Первый займ во многих МФО — <strong>под 0%!</strong></p>',
         168, 4],
    ];

    $stmt = $db->prepare("INSERT INTO email_funnel_steps (name, subject, body_html, delay_hours, step_order, is_active) VALUES (?,?,?,?,?,1)");
    foreach ($steps as $s) {
        $stmt->execute($s);
    }
}

function processFunnel(int $batchLimit = 50): array {
    $db = getDB();
    if (!ensureFunnelTables($db)) {
        return ['sent' => 0, 'errors' => 0, 'debug' => 'Не удалось создать таблицы воронки'];
    }

    seedDefaultFunnelSteps($db);

    $steps = $db->query("SELECT * FROM email_funnel_steps WHERE is_active = 1 ORDER BY step_order ASC")->fetchAll();
    if (!$steps) {
        return ['sent' => 0, 'errors' => 0, 'debug' => 'Нет активных шагов воронки'];
    }

    $subDateCol = function_exists('dbFirstExistingColumn') ? dbFirstExistingColumn('subscribers', ['created_at', 'subscribed_at']) : 'subscribed_at';
    $subscribers = $db->query("SELECT id, email, unsubscribe_token, {$subDateCol} AS created_at FROM subscribers WHERE is_active = 1")->fetchAll();
    if (!$subscribers) {
        return ['sent' => 0, 'errors' => 0, 'debug' => 'Нет активных подписчиков'];
    }

    $offersBlock = buildOffersBlock($db);
    $sent = 0;
    $errors = 0;
    $skippedAlreadySent = 0;
    $skippedTooEarly = 0;
    $skippedPrevNotSent = 0;
    $errorDetails = [];

    foreach ($subscribers as $sub) {
        if ($sent >= $batchLimit) break;

        $subCreated = strtotime($sub['created_at']);

        foreach ($steps as $step) {
            // Уже отправляли этот шаг?
            $already = $db->prepare("SELECT id FROM email_funnel_log WHERE subscriber_id = ? AND step_id = ?");
            $already->execute([$sub['id'], $step['id']]);
            if ($already->fetch()) {
                $skippedAlreadySent++;
                continue;
            }

            // Пора ли отправлять?
            $sendAfter = $subCreated + ($step['delay_hours'] * 3600);
            if (time() < $sendAfter) {
                $skippedTooEarly++;
                break;
            }

            // Предыдущий шаг отправлен?
            if ($step['step_order'] > 1) {
                $prevStep = null;
                foreach ($steps as $s) {
                    if ($s['step_order'] < $step['step_order']) $prevStep = $s;
                }
                if ($prevStep) {
                    $prevSent = $db->prepare("SELECT id FROM email_funnel_log WHERE subscriber_id = ? AND step_id = ? AND status = 'sent'");
                    $prevSent->execute([$sub['id'], $prevStep['id']]);
                    if (!$prevSent->fetch()) {
                        $skippedPrevNotSent++;
                        break;
                    }
                }
            }

            // Отправляем!
            $unsubLink = SITE_URL . '/unsubscribe?token=' . $sub['unsubscribe_token'];
            $body = $step['body_html'];
            if (str_contains((string)($body), '{{offers}}')) {
                $body = str_replace('{{offers}}', $offersBlock, $body);
            }

            $fullHtml = buildEmailHtml($body, '', $unsubLink, 0, (int)$sub['id'], false);
            $result = sendOneEmail($sub['email'], $step['subject'], $fullHtml, $unsubLink);

            $status = $result['ok'] ? 'sent' : 'failed';
            $errMsg = $result['error'] ?? null;

            // Логируем
            try {
                $db->prepare("INSERT INTO email_funnel_log (subscriber_id, step_id, email, status, error_message) VALUES (?,?,?,?,?)")
                   ->execute([$sub['id'], $step['id'], $sub['email'], $status, $errMsg ? mb_substr($errMsg, 0, 500) : null]);
            } catch (Exception $e) {
                // Если колонки error_message нет
                $db->prepare("INSERT INTO email_funnel_log (subscriber_id, step_id, email, status) VALUES (?,?,?,?)")
                   ->execute([$sub['id'], $step['id'], $sub['email'], $status]);
            }

            if ($result['ok']) {
                $sent++;
            } else {
                $errors++;
                $errorDetails[] = $sub['email'] . ': ' . ($errMsg ?? 'unknown');
            }

            usleep(200000); // 0.2 сек пауза
            break; // один шаг за раз на подписчика
        }
    }

    return [
        'sent' => $sent,
        'errors' => $errors,
        'total_subscribers' => count($subscribers),
        'total_steps' => count($steps),
        'skipped_already_sent' => $skippedAlreadySent,
        'skipped_too_early' => $skippedTooEarly,
        'skipped_prev_not_sent' => $skippedPrevNotSent,
        'error_details' => array_slice($errorDetails, 0, 5),
        'debug' => $sent === 0 && $errors === 0
            ? 'Все подписчики уже получили доступные шаги или ещё рано для следующего'
            : null,
    ];
}

function getFunnelStats(): array {
    $db = getDB();
    if (!ensureFunnelTables($db)) return [];
    seedDefaultFunnelSteps($db);

    $steps = $db->query("SELECT * FROM email_funnel_steps ORDER BY step_order ASC")->fetchAll();
    $totalSubs = (int)$db->query("SELECT COUNT(*) FROM subscribers WHERE is_active = 1")->fetchColumn();

    foreach ($steps as &$step) {
        $sentCount = $db->prepare("SELECT COUNT(*) FROM email_funnel_log WHERE step_id = ? AND status = 'sent'");
        $sentCount->execute([$step['id']]);
        $step['sent_count'] = (int)$sentCount->fetchColumn();
        $step['pending_count'] = max(0, $totalSubs - $step['sent_count']);
    }

    return ['steps' => $steps, 'total_subscribers' => $totalSubs];
}

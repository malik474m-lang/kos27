<?php
/**
 * Email-воронка — автоматические цепочки писем
 *
 * Логика:
 * 1. Подписчик приходит → через delay_hours получает шаг 1
 * 2. Через delay_hours после шага 1 → получает шаг 2
 * 3. И так далее по цепочке
 * 4. Если отписался — пропускаем
 * 5. Если уже получал шаг — не дублируем
 */

require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/newsletter-helpers.php';

/**
 * Обеспечить существование таблиц воронки
 */
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

                CREATE TABLE IF NOT EXISTS `email_funnel_log` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `subscriber_id` int(11) NOT NULL,
                  `step_id` int(11) NOT NULL,
                  `email` varchar(255) NOT NULL,
                  `status` enum('sent','failed','skipped') NOT NULL DEFAULT 'sent',
                  `sent_at` timestamp NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  KEY `idx_subscriber_step` (`subscriber_id`, `step_id`),
                  KEY `idx_sent_at` (`sent_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
            $checked = true;
            return true;
        } catch (Exception $e2) {
            return false;
        }
    }
}

/**
 * Посеять дефолтные шаги воронки, если таблица пустая
 */
function seedDefaultFunnelSteps(PDO $db): void {
    $count = (int)$db->query("SELECT COUNT(*) FROM email_funnel_steps")->fetchColumn();
    if ($count > 0) return;

    $steps = [
        [
            'name' => 'Приветствие',
            'subject' => 'Добро пожаловать в ' . SITE_NAME . '!',
            'body_html' => '<h2 style="color:#1a56db">Добро пожаловать! 👋</h2>'
                . '<p>Спасибо за подписку на ' . SITE_NAME . '!</p>'
                . '<p>Мы поможем вам найти лучшие финансовые предложения:</p>'
                . '<ul><li>💵 Займы от 0% для новых клиентов</li>'
                . '<li>🏦 Кредиты с низкой ставкой</li>'
                . '<li>💳 Кредитные карты с кэшбеком</li></ul>'
                . '<p><a href="' . SITE_URL . '/zajmy" style="display:inline-block;background:#1a56db;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Посмотреть предложения →</a></p>'
                . '{{offers}}',
            'delay_hours' => 0,
            'step_order' => 1,
        ],
        [
            'name' => 'Лучшие предложения недели',
            'subject' => '🔥 Лучшие предложения этой недели',
            'body_html' => '<h2>Не пропустите лучшие условия!</h2>'
                . '<p>Подобрали для вас самые выгодные предложения:</p>'
                . '{{offers}}'
                . '<p>💡 <strong>Совет:</strong> подавайте 2-3 заявки одновременно — это увеличивает шанс одобрения и позволяет выбрать лучшие условия.</p>'
                . '<p><a href="' . SITE_URL . '/calculator" style="display:inline-block;background:#059669;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">🧮 Калькулятор займа</a></p>',
            'delay_hours' => 48,
            'step_order' => 2,
        ],
        [
            'name' => 'Как выбрать займ',
            'subject' => '📋 Как выбрать займ и не переплатить',
            'body_html' => '<h2>5 правил выгодного займа</h2>'
                . '<ol>'
                . '<li><strong>Сравнивайте ПСК</strong>, а не рекламную ставку</li>'
                . '<li><strong>Берите первый займ под 0%</strong> — многие МФО предлагают</li>'
                . '<li><strong>Проверяйте лицензию ЦБ</strong> на сайте Банка России</li>'
                . '<li><strong>Не берите больше</strong>, чем можете вернуть</li>'
                . '<li><strong>Используйте калькулятор</strong> для расчёта переплаты</li>'
                . '</ol>'
                . '<p>На нашем сайте все предложения проверены — лицензии, ставки, условия.</p>'
                . '{{offers}}'
                . '<p><a href="' . SITE_URL . '/articles" style="display:inline-block;background:#1a56db;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Читать полезные статьи →</a></p>',
            'delay_hours' => 120,
            'step_order' => 3,
        ],
        [
            'name' => 'Напоминание',
            'subject' => '💰 Ещё не оформили? Специальные условия ждут!',
            'body_html' => '<h2>Мы заметили, что вы ещё не оформили заявку</h2>'
                . '<p>Возможно, вы не нашли подходящее предложение? Вот обновлённый список с лучшими условиями:</p>'
                . '{{offers}}'
                . '<p>Первый займ во многих МФО — <strong>под 0%!</strong> Просто выберите предложение и заполните короткую анкету (5 минут).</p>'
                . '<p><a href="' . SITE_URL . '/zajmy" style="display:inline-block;background:#059669;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600">Выбрать предложение →</a></p>',
            'delay_hours' => 168,
            'step_order' => 4,
        ],
    ];

    $stmt = $db->prepare("INSERT INTO email_funnel_steps (name, subject, body_html, delay_hours, step_order, is_active) VALUES (?,?,?,?,?,1)");
    foreach ($steps as $s) {
        $stmt->execute([$s['name'], $s['subject'], $s['body_html'], $s['delay_hours'], $s['step_order']]);
    }
}

/**
 * Обработать воронку — отправить письма подписчикам, которым пора
 * Вызывается из cron
 */
function processFunnel(int $batchLimit = 50): array {
    $db = getDB();
    if (!ensureFunnelTables($db)) {
        return ['error' => 'Таблицы воронки не созданы'];
    }

    seedDefaultFunnelSteps($db);

    $steps = $db->query("SELECT * FROM email_funnel_steps WHERE is_active = 1 ORDER BY step_order ASC")->fetchAll();
    if (!$steps) return ['sent' => 0, 'skipped' => 0, 'message' => 'Нет активных шагов'];

    $subscribers = $db->query("SELECT id, email, unsubscribe_token, created_at FROM subscribers WHERE is_active = 1")->fetchAll();
    if (!$subscribers) return ['sent' => 0, 'skipped' => 0, 'message' => 'Нет подписчиков'];

    $offersBlock = buildOffersBlock($db);
    $sent = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($subscribers as $sub) {
        if ($sent >= $batchLimit) break;

        $subCreated = strtotime($sub['created_at']);

        foreach ($steps as $step) {
            // Проверяем — уже отправляли?
            $already = $db->prepare("SELECT id FROM email_funnel_log WHERE subscriber_id = ? AND step_id = ?");
            $already->execute([$sub['id'], $step['id']]);
            if ($already->fetch()) {
                continue; // уже отправлено
            }

            // Проверяем — пора ли отправлять?
            $sendAfter = $subCreated + ($step['delay_hours'] * 3600);
            if (time() < $sendAfter) {
                break; // ещё рано для этого и всех следующих шагов
            }

            // Проверяем — предыдущий шаг был отправлен?
            if ($step['step_order'] > 1) {
                $prevStep = null;
                foreach ($steps as $s) {
                    if ($s['step_order'] < $step['step_order']) $prevStep = $s;
                }
                if ($prevStep) {
                    $prevSent = $db->prepare("SELECT id FROM email_funnel_log WHERE subscriber_id = ? AND step_id = ? AND status = 'sent'");
                    $prevSent->execute([$sub['id'], $prevStep['id']]);
                    if (!$prevSent->fetch()) {
                        break; // предыдущий шаг не отправлен — ждём
                    }
                }
            }

            // Отправляем
            $unsubLink = SITE_URL . '/unsubscribe?token=' . $sub['unsubscribe_token'];
            $body = $step['body_html'];

            if (str_contains($body, '{{offers}}')) {
                $body = str_replace('{{offers}}', $offersBlock, $body);
            }

            $fullHtml = buildEmailHtml($body, '', $unsubLink, 0, (int)$sub['id'], false);
            $result = sendOneEmail($sub['email'], $step['subject'], $fullHtml, $unsubLink);

            $status = $result['ok'] ? 'sent' : 'failed';

            $db->prepare("INSERT INTO email_funnel_log (subscriber_id, step_id, email, status) VALUES (?,?,?,?)")
               ->execute([$sub['id'], $step['id'], $sub['email'], $status]);

            if ($result['ok']) {
                $sent++;
            } else {
                $errors++;
            }

            break; // один шаг за раз на подписчика
        }
    }

    return ['sent' => $sent, 'skipped' => $skipped, 'errors' => $errors, 'total_subscribers' => count($subscribers)];
}

/**
 * Статистика воронки
 */
function getFunnelStats(): array {
    $db = getDB();
    if (!ensureFunnelTables($db)) return [];

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

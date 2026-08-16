<?php
/**
 * КосмоБонус — бонусная программа
 * 1 бонус = 1 рубль
 */

function ensureKosmoBonusTables(): void {
    static $checked = false;
    if ($checked) return;
    $db = getDB();

    try { $db->query("SELECT kosmobonus_enabled FROM offers LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE offers ADD COLUMN kosmobonus_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER license"); } catch (Exception $e2) {} }
    try { $db->query("SELECT kosmobonus_amount FROM offers LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE offers ADD COLUMN kosmobonus_amount INT NOT NULL DEFAULT 0 AFTER kosmobonus_enabled"); } catch (Exception $e2) {} }
    try { $db->query("SELECT kosmobonus_conditions FROM offers LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE offers ADD COLUMN kosmobonus_conditions TEXT DEFAULT NULL AFTER kosmobonus_amount"); } catch (Exception $e2) {} }

    try { $db->query("SELECT bonus_balance FROM users LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE users ADD COLUMN bonus_balance INT NOT NULL DEFAULT 0"); } catch (Exception $e2) {} }

    try { $db->query("SELECT 1 FROM bonus_transactions LIMIT 1"); }
    catch (Exception $e) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS bonus_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                offer_id INT DEFAULT NULL,
                click_stat_id INT DEFAULT NULL,
                postback_id INT DEFAULT NULL,
                withdrawal_request_id INT DEFAULT NULL,
                amount INT NOT NULL,
                type ENUM('accrual','withdrawal','manual','reversal') NOT NULL DEFAULT 'accrual',
                status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
                description VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                confirmed_at TIMESTAMP NULL DEFAULT NULL,
                KEY idx_user_id (user_id),
                KEY idx_click_stat_id (click_stat_id),
                KEY idx_postback_id (postback_id),
                KEY idx_withdrawal_request_id (withdrawal_request_id),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Exception $e2) {}
    }
    try { $db->query("SELECT withdrawal_request_id FROM bonus_transactions LIMIT 1"); }
    catch (Exception $e) { try { $db->exec("ALTER TABLE bonus_transactions ADD COLUMN withdrawal_request_id INT DEFAULT NULL AFTER postback_id"); } catch (Exception $e2) {} }

    try { $db->query("SELECT 1 FROM bonus_withdraw_requests LIMIT 1"); }
    catch (Exception $e) {
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS bonus_withdraw_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount INT NOT NULL,
                bank_name VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                cardholder_name VARCHAR(255) NOT NULL,
                status ENUM('pending','paid','rejected','cancelled') NOT NULL DEFAULT 'pending',
                admin_comment VARCHAR(500) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL DEFAULT NULL,
                KEY idx_user_id (user_id),
                KEY idx_status (status),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Exception $e2) {}
    }

    $checked = true;
}

function kosmoBonusFindTransactionByClick(int $userId, int $clickStatId): ?array {
    $db = getDB();
    ensureKosmoBonusTables();
    $stmt = $db->prepare("SELECT * FROM bonus_transactions WHERE user_id = ? AND click_stat_id = ? AND type = 'accrual' LIMIT 1");
    $stmt->execute([$userId, $clickStatId]);
    return $stmt->fetch() ?: null;
}

function kosmoBonusAccrue(int $userId, int $offerId, int $clickStatId, int $postbackId): array {
    $db = getDB();
    ensureKosmoBonusTables();

    $stmt = $db->prepare("SELECT kosmobonus_enabled, kosmobonus_amount, title FROM offers WHERE id = ?");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
    if (!$offer || !$offer['kosmobonus_enabled'] || (int)$offer['kosmobonus_amount'] <= 0) {
        return ['ok' => false, 'reason' => 'Оффер не участвует в КосмоБонус'];
    }

    $existing = kosmoBonusFindTransactionByClick($userId, $clickStatId);
    if ($existing) {
        return ['ok' => true, 'tx_id' => (int)$existing['id'], 'amount' => (int)$existing['amount'], 'status' => $existing['status'], 'existing' => true];
    }

    $amount = (int)$offer['kosmobonus_amount'];
    $db->prepare("INSERT INTO bonus_transactions (user_id, offer_id, click_stat_id, postback_id, amount, type, status, description) VALUES (?,?,?,?,?,'accrual','pending',?)")
       ->execute([$userId, $offerId, $clickStatId, $postbackId, $amount, 'КосмоБонус за оформление ' . $offer['title']]);

    return ['ok' => true, 'tx_id' => (int)$db->lastInsertId(), 'amount' => $amount, 'status' => 'pending', 'existing' => false];
}

function kosmoBonusConfirm(int $transactionId): bool {
    $db = getDB();
    ensureKosmoBonusTables();
    $tx = $db->prepare("SELECT * FROM bonus_transactions WHERE id = ? AND status = 'pending' AND type = 'accrual'");
    $tx->execute([$transactionId]);
    $row = $tx->fetch();
    if (!$row) return false;

    $db->prepare("UPDATE bonus_transactions SET status = 'confirmed', confirmed_at = NOW() WHERE id = ?")->execute([$transactionId]);
    $db->prepare("UPDATE users SET bonus_balance = bonus_balance + ? WHERE id = ?")->execute([(int)$row['amount'], (int)$row['user_id']]);
    return true;
}

function kosmoBonusCancel(int $transactionId): bool {
    $db = getDB();
    ensureKosmoBonusTables();
    $tx = $db->prepare("SELECT * FROM bonus_transactions WHERE id = ? AND status IN ('pending','confirmed') AND type = 'accrual'");
    $tx->execute([$transactionId]);
    $row = $tx->fetch();
    if (!$row) return false;

    if ($row['status'] === 'confirmed') {
        $db->prepare("UPDATE users SET bonus_balance = GREATEST(0, bonus_balance - ?) WHERE id = ?")->execute([(int)$row['amount'], (int)$row['user_id']]);
    }
    $db->prepare("UPDATE bonus_transactions SET status = 'cancelled' WHERE id = ?")->execute([$transactionId]);
    return true;
}

function kosmoBonusHandlePostback(int $userId, int $offerId, int $clickStatId, int $postbackId, string $status): array {
    ensureKosmoBonusTables();
    $accrual = kosmoBonusAccrue($userId, $offerId, $clickStatId, $postbackId);
    if (!$accrual['ok']) return $accrual;
    $txId = (int)($accrual['tx_id'] ?? 0);
    if ($txId <= 0) return ['ok' => false, 'reason' => 'Не удалось определить транзакцию бонуса'];

    if ($status === 'approved') {
        kosmoBonusConfirm($txId);
        return ['ok' => true, 'tx_id' => $txId, 'status' => 'confirmed', 'amount' => (int)($accrual['amount'] ?? 0)];
    }
    if (in_array($status, ['rejected', 'cancelled'], true)) {
        kosmoBonusCancel($txId);
        return ['ok' => true, 'tx_id' => $txId, 'status' => 'cancelled', 'amount' => (int)($accrual['amount'] ?? 0)];
    }

    return ['ok' => true, 'tx_id' => $txId, 'status' => 'pending', 'amount' => (int)($accrual['amount'] ?? 0)];
}

function kosmoBonusBalance(int $userId): int {
    $db = getDB();
    ensureKosmoBonusTables();
    $stmt = $db->prepare("SELECT bonus_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return (int)($row['bonus_balance'] ?? 0);
}

function kosmoBonusHistory(int $userId, int $limit = 50): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $limit = max(1, min(200, (int)$limit));
    $stmt = $db->prepare("SELECT bt.*, o.title as offer_title FROM bonus_transactions bt LEFT JOIN offers o ON bt.offer_id = o.id WHERE bt.user_id = ? ORDER BY bt.created_at DESC LIMIT {$limit}");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function kosmoBonusCreateWithdrawalRequest(int $userId, int $amount, string $bankName, string $phone, string $cardholderName): array {
    $db = getDB();
    ensureKosmoBonusTables();

    $amount = abs((int)$amount);
    $bankName = trim($bankName);
    $phone = trim($phone);
    $cardholderName = trim($cardholderName);

    if ($amount <= 0) return ['ok' => false, 'error' => 'Сумма должна быть больше нуля'];
    if ($bankName === '' || $phone === '' || $cardholderName === '') return ['ok' => false, 'error' => 'Заполните все поля заявки'];

    $pending = $db->prepare("SELECT COUNT(*) FROM bonus_withdraw_requests WHERE user_id = ? AND status = 'pending'");
    $pending->execute([$userId]);
    if ((int)$pending->fetchColumn() > 0) return ['ok' => false, 'error' => 'У вас уже есть заявка на вывод в обработке'];

    $balance = kosmoBonusBalance($userId);
    if ($balance < $amount) return ['ok' => false, 'error' => 'Недостаточно бонусов на балансе'];

    try {
        $db->beginTransaction();
        $db->prepare("UPDATE users SET bonus_balance = bonus_balance - ? WHERE id = ?")->execute([$amount, $userId]);
        $db->prepare("INSERT INTO bonus_withdraw_requests (user_id, amount, bank_name, phone, cardholder_name, status) VALUES (?,?,?,?,?,'pending')")
           ->execute([$userId, $amount, $bankName, $phone, $cardholderName]);
        $requestId = (int)$db->lastInsertId();
        $db->prepare("INSERT INTO bonus_transactions (user_id, withdrawal_request_id, amount, type, status, description) VALUES (?, ?, ?, 'withdrawal', 'pending', ?)")
           ->execute([$userId, $requestId, -$amount, 'Заявка на вывод бонусов']);
        $db->commit();
        return ['ok' => true, 'request_id' => $requestId, 'new_balance' => kosmoBonusBalance($userId)];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function kosmoBonusWithdrawRequestsByUser(int $userId, int $limit = 30): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $limit = max(1, min(100, (int)$limit));
    $stmt = $db->prepare("SELECT * FROM bonus_withdraw_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function kosmoBonusAdminWithdrawalRequests(int $limit = 100): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $limit = max(1, min(300, (int)$limit));
    return $db->query("SELECT r.*, u.email, u.name FROM bonus_withdraw_requests r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT {$limit}")->fetchAll();
}

function kosmoBonusProcessWithdrawalRequest(int $requestId, string $action, string $adminComment = ''): array {
    $db = getDB();
    ensureKosmoBonusTables();

    $stmt = $db->prepare("SELECT * FROM bonus_withdraw_requests WHERE id = ? LIMIT 1");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();
    if (!$req) return ['ok' => false, 'error' => 'Заявка не найдена'];
    if ($req['status'] !== 'pending') return ['ok' => false, 'error' => 'Заявка уже обработана'];

    $action = trim($action);
    if (!in_array($action, ['paid', 'rejected'], true)) return ['ok' => false, 'error' => 'Некорректное действие'];

    try {
        $db->beginTransaction();
        if ($action === 'paid') {
            $db->prepare("UPDATE bonus_withdraw_requests SET status = 'paid', admin_comment = ?, processed_at = NOW() WHERE id = ?")
               ->execute([$adminComment ?: null, $requestId]);
            $db->prepare("UPDATE bonus_transactions SET status = 'confirmed', confirmed_at = NOW(), description = ? WHERE withdrawal_request_id = ? AND type = 'withdrawal'")
               ->execute([$adminComment ?: 'Выплачено пользователю', $requestId]);
        } else {
            $db->prepare("UPDATE users SET bonus_balance = bonus_balance + ? WHERE id = ?")->execute([(int)$req['amount'], (int)$req['user_id']]);
            $db->prepare("UPDATE bonus_withdraw_requests SET status = 'rejected', admin_comment = ?, processed_at = NOW() WHERE id = ?")
               ->execute([$adminComment ?: null, $requestId]);
            $db->prepare("UPDATE bonus_transactions SET status = 'cancelled', description = ? WHERE withdrawal_request_id = ? AND type = 'withdrawal'")
               ->execute([$adminComment ?: 'Заявка отклонена', $requestId]);
        }
        $db->commit();
        return ['ok' => true, 'status' => $action, 'new_balance' => kosmoBonusBalance((int)$req['user_id'])];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function kosmoBonusManualAccrual(int $userId, int $amount, string $description = ''): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $amount = abs((int)$amount);
    if ($amount <= 0) return ['ok' => false, 'error' => 'Сумма должна быть больше нуля'];

    $description = trim($description) ?: 'Ручное начисление бонусов';
    $db->prepare("UPDATE users SET bonus_balance = bonus_balance + ? WHERE id = ?")->execute([$amount, $userId]);
    $db->prepare("INSERT INTO bonus_transactions (user_id, amount, type, status, description, confirmed_at) VALUES (?, ?, 'manual', 'confirmed', ?, NOW())")
       ->execute([$userId, $amount, $description]);

    return ['ok' => true, 'amount' => $amount, 'new_balance' => kosmoBonusBalance($userId)];
}

function kosmoBonusWithdraw(int $userId, int $amount, string $description = ''): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $amount = abs((int)$amount);
    if ($amount <= 0) return ['ok' => false, 'error' => 'Сумма должна быть больше нуля'];

    $balance = kosmoBonusBalance($userId);
    if ($balance < $amount) return ['ok' => false, 'error' => 'Недостаточно бонусов на балансе'];

    $description = trim($description) ?: 'Ручное списание бонусов';
    $db->prepare("UPDATE users SET bonus_balance = bonus_balance - ? WHERE id = ?")->execute([$amount, $userId]);
    $db->prepare("INSERT INTO bonus_transactions (user_id, amount, type, status, description, confirmed_at) VALUES (?, ?, 'withdrawal', 'confirmed', ?, NOW())")
       ->execute([$userId, -$amount, $description]);

    return ['ok' => true, 'amount' => $amount, 'new_balance' => kosmoBonusBalance($userId)];
}

function kosmoBonusAdminHistory(int $limit = 100): array {
    $db = getDB();
    ensureKosmoBonusTables();
    $limit = max(1, min(300, (int)$limit));
    $stmt = $db->query("SELECT bt.*, u.email, u.name, o.title as offer_title FROM bonus_transactions bt LEFT JOIN users u ON bt.user_id = u.id LEFT JOIN offers o ON bt.offer_id = o.id ORDER BY bt.created_at DESC LIMIT {$limit}");
    return $stmt->fetchAll();
}

function isKosmoBonusOffer(array $offer): bool {
    return !empty($offer['kosmobonus_enabled']) && (int)($offer['kosmobonus_amount'] ?? 0) > 0;
}

function renderKosmoBonusBadge(array $offer): string {
    if (!isKosmoBonusOffer($offer)) return '';
    $amount = (int)$offer['kosmobonus_amount'];
    ob_start(); ?>
    <div class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-amber-400 to-orange-500 px-4 py-1.5 text-sm font-bold text-white shadow-sm">
        <span>🎁</span>
        <span>КосмоБонус +<?= $amount ?> ₽</span>
    </div>
    <?php return ob_get_clean();
}

function renderKosmoBonusBlock(array $offer): string {
    if (!isKosmoBonusOffer($offer)) return '';
    $amount = (int)$offer['kosmobonus_amount'];
    $conditions = trim((string)($offer['kosmobonus_conditions'] ?? ''));
    ob_start(); ?>
    <div class="mt-6 rounded-2xl border-2 border-amber-300 bg-gradient-to-r from-amber-50 to-orange-50 p-6">
        <div class="flex items-center gap-3 mb-3">
            <span class="text-3xl">🎁</span>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Акция КосмоБонус</h3>
                <p class="text-2xl font-extrabold text-orange-600">+<?= $amount ?> бонусов</p>
            </div>
        </div>
        <p class="text-sm text-gray-700 mb-2">Оформите <strong><?= e($offer['title']) ?></strong> через наш сайт и получите <strong><?= $amount ?> бонусов</strong> на свой счёт. 1 бонус = 1 рубль.</p>
        <?php if ($conditions): ?>
        <div class="bg-white rounded-lg p-3 mt-3 text-sm text-gray-600 border border-amber-200">
            <p class="font-semibold text-gray-800 mb-1">Условия акции:</p>
            <p><?= nl2br(e($conditions)) ?></p>
        </div>
        <?php endif; ?>
        <p class="text-xs text-gray-500 mt-3">Для участия необходима <a href="/register" class="text-primary hover:underline">регистрация</a> на сайте. Бонусы начисляются после подтверждения заявки партнёром.</p>
    </div>
    <?php return ob_get_clean();
}

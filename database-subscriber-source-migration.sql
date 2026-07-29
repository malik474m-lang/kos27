-- Источник подписки (exit_popup, footer, register)
ALTER TABLE `subscribers` ADD COLUMN `source` varchar(50) DEFAULT 'footer' AFTER `unsubscribe_token`;

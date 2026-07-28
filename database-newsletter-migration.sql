-- Рассылки
CREATE TABLE IF NOT EXISTS `newsletters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(500) NOT NULL,
  `body_html` text NOT NULL,
  `status` enum('draft','sending','sent','failed') NOT NULL DEFAULT 'draft',
  `sent_count` int(11) NOT NULL DEFAULT 0,
  `failed_count` int(11) NOT NULL DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Токен отписки для подписчиков
ALTER TABLE `subscribers` ADD COLUMN `unsubscribe_token` varchar(64) DEFAULT NULL;

-- Генерируем токены для существующих подписчиков
UPDATE `subscribers` SET unsubscribe_token = MD5(CONCAT(id, email, RAND(), NOW())) WHERE unsubscribe_token IS NULL;

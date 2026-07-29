-- Лог отправки рассылок
CREATE TABLE IF NOT EXISTS `newsletter_send_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) NOT NULL,
  `subscriber_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message` varchar(500) DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_newsletter` (`newsletter_id`),
  KEY `idx_email` (`email`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

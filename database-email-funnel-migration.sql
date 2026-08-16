-- Email-воронка: автоматические цепочки писем
CREATE TABLE IF NOT EXISTS `email_funnel_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Название шага',
  `subject` varchar(500) NOT NULL,
  `body_html` text NOT NULL,
  `delay_hours` int(11) NOT NULL DEFAULT 24 COMMENT 'Задержка после предыдущего шага (часы)',
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

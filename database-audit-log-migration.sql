-- История изменений (аудит-лог)
CREATE TABLE IF NOT EXISTS `admin_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL COMMENT 'ID админа',
  `admin_name` varchar(100) DEFAULT NULL COMMENT 'Имя админа (для истории)',
  `action` varchar(50) NOT NULL COMMENT 'Действие: create, update, delete, send, apply',
  `entity` varchar(50) NOT NULL COMMENT 'Сущность: offer, tag, category, newsletter, postback, smart_rating',
  `entity_id` int(11) DEFAULT NULL COMMENT 'ID сущности',
  `entity_name` varchar(255) DEFAULT NULL COMMENT 'Название (для истории)',
  `changes` JSON DEFAULT NULL COMMENT 'Что изменилось (старое/новое)',
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity`, `entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

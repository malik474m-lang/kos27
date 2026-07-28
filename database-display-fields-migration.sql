-- Видимость стандартных полей оффера
ALTER TABLE `offers` ADD COLUMN `display_fields` text DEFAULT NULL COMMENT 'JSON: visibility of standard fields';

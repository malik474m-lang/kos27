-- Универсальные дополнительные поля для офферов (JSON)
ALTER TABLE `offers` ADD COLUMN `extra_fields` text DEFAULT NULL COMMENT 'JSON: [{label,value,visible}]';

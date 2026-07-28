ALTER TABLE `offers` ADD COLUMN `rate_unit` varchar(10) NOT NULL DEFAULT 'day' COMMENT 'day|year';

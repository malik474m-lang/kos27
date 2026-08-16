-- Миграция: Справочная информация для офферов
-- Телефон, Адрес, Торговая марка, Лицензия

ALTER TABLE `offers`
    ADD COLUMN `phone` VARCHAR(50) DEFAULT NULL AFTER `affiliate_url`,
    ADD COLUMN `address` VARCHAR(500) DEFAULT NULL AFTER `phone`,
    ADD COLUMN `trademark` VARCHAR(255) DEFAULT NULL AFTER `address`,
    ADD COLUMN `license` VARCHAR(255) DEFAULT NULL AFTER `trademark`;

-- Добавляем meta_title и meta_description в таблицу offers для SEO
ALTER TABLE `offers` ADD COLUMN `meta_title` VARCHAR(500) DEFAULT NULL AFTER `seo_keywords`;
ALTER TABLE `offers` ADD COLUMN `meta_description` TEXT DEFAULT NULL AFTER `meta_title`;

-- Миграция: E-E-A-T поля для статей
-- Выполнить на хостинге: mysql -u USER -p DATABASE < database-article-eeat-migration.sql

ALTER TABLE `articles`
    ADD COLUMN `author_name` VARCHAR(255) DEFAULT 'Редакция Космозайм' AFTER `cover_image`,
    ADD COLUMN `author_title` VARCHAR(255) DEFAULT 'Финансовый редактор' AFTER `author_name`,
    ADD COLUMN `reviewer_name` VARCHAR(255) DEFAULT NULL AFTER `author_title`,
    ADD COLUMN `reviewer_title` VARCHAR(255) DEFAULT NULL AFTER `reviewer_name`,
    ADD COLUMN `fact_checked_at` TIMESTAMP NULL DEFAULT NULL AFTER `reviewer_title`,
    ADD COLUMN `sources` TEXT DEFAULT NULL AFTER `fact_checked_at`;

-- Обновить существующие статьи с дефолтными значениями
UPDATE `articles` SET 
    `author_name` = 'Редакция Космозайм',
    `author_title` = 'Финансовый редактор',
    `reviewer_name` = 'Анна Соколова',
    `reviewer_title` = 'Главный редактор',
    `fact_checked_at` = `updated_at`,
    `sources` = '[{"title":"Банк России","url":"https://cbr.ru/"},{"title":"Реестр МФО","url":"https://cbr.ru/microfinance/registry/"}]'
WHERE `author_name` IS NULL OR `author_name` = '';

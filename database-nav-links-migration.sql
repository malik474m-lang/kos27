-- Навигационные ссылки как категории (без офферов, только для меню)
INSERT INTO `categories` (`name`, `slug`, `icon`, `h1`, `show_in_header`, `show_in_footer`, `is_active`, `sort_order`) VALUES
('Сравнение', 'compare', '⚖️', 'Сравнение предложений', 1, 1, 1, 10),
('Калькулятор', 'calculator', '🧮', 'Калькулятор займа', 1, 1, 1, 11),
('Статьи', 'articles', '📰', 'Полезные статьи', 1, 1, 1, 12),
('FAQ', 'faq', '❓', 'Частые вопросы', 0, 1, 1, 13),
('Глоссарий', 'glossary', '📖', 'Глоссарий терминов', 0, 1, 1, 14)
ON DUPLICATE KEY UPDATE name=VALUES(name);

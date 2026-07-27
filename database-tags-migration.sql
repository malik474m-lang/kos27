-- Таблица тегов (типов предложений)
CREATE TABLE IF NOT EXISTS `offer_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `h1` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `icon` varchar(10) DEFAULT NULL,
  `category` enum('microloans','credits','credit_cards','debit_cards') NOT NULL DEFAULT 'microloans',
  `features` text DEFAULT NULL COMMENT 'JSON массив [{icon,title,text}]',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Начальные данные (перенос из loan-types.php)
INSERT INTO `offer_tags` (`slug`, `title`, `h1`, `description`, `meta_description`, `icon`, `category`, `features`, `content`, `sort_order`) VALUES
('bez-otkaza', 'Займы без отказа', 'Займы без отказа на карту онлайн', 'МФО с высоким процентом одобрения', 'Займы без отказа на карту за 5 минут. Одобрение 99%.', '✅', 'microloans', '[{"icon":"✅","title":"99% одобрение","text":"Высокий процент одобрения"},{"icon":"⚡","title":"5 минут","text":"Быстрое решение"},{"icon":"📱","title":"Онлайн","text":"Без визита в офис"},{"icon":"🔓","title":"Без справок","text":"Только паспорт"}]', 'Займы без отказа — это предложения от МФО с максимально лояльными требованиями к заёмщикам.', 1),
('bez-protsentov', 'Займы без процентов', 'Займы без процентов — первый займ под 0%', 'Бесплатные займы для новых клиентов', 'Займы без процентов на карту. Первый займ под 0% до 30 дней.', '🆓', 'microloans', '[{"icon":"🆓","title":"0% ставка","text":"Первый займ бесплатно"},{"icon":"📅","title":"До 30 дней","text":"Льготный период"},{"icon":"💳","title":"На карту","text":"Мгновенный перевод"},{"icon":"🔄","title":"Повторный","text":"Скидки постоянным"}]', 'Многие МФО предлагают первый займ под 0% — вы возвращаете только сумму, которую взяли.', 2),
('na-kartu', 'Займы на карту', 'Займы на карту мгновенно онлайн', 'Получите деньги на банковскую карту', 'Займы на карту мгновенно. Перевод за 10 минут на любую карту.', '💳', 'microloans', '[{"icon":"💳","title":"На карту","text":"Visa, Mastercard, МИР"},{"icon":"⚡","title":"10 минут","text":"Быстрый перевод"},{"icon":"🌙","title":"24/7","text":"Круглосуточно"},{"icon":"📱","title":"Онлайн","text":"Без визита"}]', 'Займы на карту — самый популярный способ получения денег.', 3),
('s-plohoj-kreditnoj-istoriej', 'Займы с плохой КИ', 'Займы с плохой кредитной историей', 'Займы даже с испорченной кредитной историей', 'Займы с плохой кредитной историей. Одобрение без проверки КИ.', '📊', 'microloans', '[{"icon":"📊","title":"Без проверки КИ","text":"Лояльные требования"},{"icon":"✅","title":"Высокое одобрение","text":"До 95% заявок"},{"icon":"💰","title":"До 100 000 ₽","text":"Достаточные суммы"},{"icon":"📱","title":"Онлайн","text":"Удобное оформление"}]', 'Если у вас плохая кредитная история — многие МФО готовы выдать займ.', 4),
('dlya-pensionerov', 'Займы для пенсионеров', 'Займы для пенсионеров онлайн', 'Специальные условия для пенсионеров', 'Займы для пенсионеров без отказа. Возраст до 75 лет.', '👴', 'microloans', '[{"icon":"👴","title":"До 75 лет","text":"Расширенный возраст"},{"icon":"💰","title":"До 50 000 ₽","text":"Доступные суммы"},{"icon":"📋","title":"По паспорту","text":"Минимум документов"},{"icon":"🏠","title":"Без залога","text":"Без обеспечения"}]', 'Пенсионеры могут получить займ в большинстве МФО.', 5),
('studentam', 'Займы студентам', 'Займы студентам онлайн', 'Займы для студентов от 18 лет', 'Займы студентам на карту. От 18 лет, без подтверждения дохода.', '🎓', 'microloans', '[{"icon":"🎓","title":"От 18 лет","text":"Для студентов"},{"icon":"📱","title":"Онлайн","text":"Быстрое оформление"},{"icon":"💳","title":"На карту","text":"Мгновенный перевод"},{"icon":"🆓","title":"0% первый","text":"Без процентов"}]', 'Студенты от 18 лет могут оформить займ онлайн.', 6)
ON DUPLICATE KEY UPDATE title=VALUES(title);

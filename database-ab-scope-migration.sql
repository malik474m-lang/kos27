ALTER TABLE `ab_tests` ADD COLUMN `category_scope` varchar(100) NOT NULL DEFAULT 'all' COMMENT 'all|microloans|credits|credit_cards|debit_cards';

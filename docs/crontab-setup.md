# Настройка Cron-задач для Космозайм

## Все задачи обслуживания (рекомендуется)

```crontab
# === ОБСЛУЖИВАНИЕ ===
# Все задачи разом — каждый день в 4:00
0 4 * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php all >> data/maintenance.log 2>&1

# === ИЛИ ПО ОТДЕЛЬНОСТИ ===

# Проверка битых ссылок — каждый день в 3:00
0 3 * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php link-check >> data/maintenance.log 2>&1

# Умный рейтинг — каждый день в 5:00
0 5 * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php smart-rating >> data/maintenance.log 2>&1

# Очистка логов — каждый день в 4:30
30 4 * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php clean-logs >> data/maintenance.log 2>&1

# Очистка кэша — каждый день в 4:35
35 4 * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php clean-cache >> data/maintenance.log 2>&1

# Бэкап базы — каждое воскресенье в 2:00
0 2 * * 0  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php backup >> data/maintenance.log 2>&1

# Проверка работоспособности — каждые 10 минут
*/10 * * * *  cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php health-ping >> data/maintenance.log 2>&1

# === ГЕНЕРАЦИЯ КОНТЕНТА (уже существует) ===
# Автогенерация отзывов — через планировщик в админке
# Автогенерация статей — через планировщик в админке
```

## Установка на Jino

1. Зайти в панель Jino → Хостинг → Cron-задачи
2. Добавить задачу:
   - **Команда:** `cd ~/domains/ВАШ_САЙТ && php cron/maintenance-cron.php all`
   - **Расписание:** `0 4 * * *` (каждый день в 4:00)

Или через SSH:
```bash
crontab -e
# Вставить нужные строки из списка выше
```

## Проверка работы

```bash
# Запустить вручную
cd ~/domains/ВАШ_САЙТ
php cron/maintenance-cron.php all

# Посмотреть лог
tail -50 data/maintenance.log

# Запустить конкретную задачу
php cron/maintenance-cron.php link-check
php cron/maintenance-cron.php smart-rating
php cron/maintenance-cron.php backup
```

## Что делает каждая задача

| Задача | Описание | Сроки хранения |
|--------|----------|----------------|
| `link-check` | Проверяет все партнёрские ссылки (HEAD → GET fallback) | Хранит последнюю + 30 дней |
| `smart-rating` | Пересчитывает sort_order по кликам/конверсиям/отзывам | — |
| `clean-logs` | Чистит admin_login_log (30д), audit_log (90д), send_log (60д), page_views (90д), click_stats (180д) | — |
| `clean-cache` | Удаляет page_cache (>1ч), api_cache (>1ч), geo_cache (>7д) | — |
| `backup` | mysqldump → ZIP, хранит 30 дней, автоудаление старых | 30 дней |
| `health-ping` | Проверяет /, /api/health, /zajmy + MySQL | Пишет alert-файл при сбое |

# Настройка Cron-задач KosmoEngine

## 🕐 Рекомендуемые cron-задачи

После установки добавьте следующие задачи в crontab:

```bash
# Откройте crontab для редактирования
crontab -e
```

### Добавьте строки:

```bash
# ===== KOSMOENGINE CRON =====

# Автогенерация статей (раз в 6 часов)
0 */6 * * * curl -s "https://ВАШЕ_ДОМЕН/api/cron-generate?secret=CRON_SECRET&type=article" > /dev/null 2>&1

# Автогенерация отзывов (раз в 4 часа)
0 */4 * * * curl -s "https://ВАШЕ_ДОМЕН/api/cron-generate?secret=CRON_SECRET&type=review" > /dev/null 2>&1

# Техническое обслуживание (ежедневно в 3:00)
0 3 * * * php /path/to/site/cron/maintenance-cron.php > /dev/null 2>&1

# Генерация статей по расписанию (ежедневно в 10:00)
0 10 * * * php /path/to/site/cron/article-cron.php > /dev/null 2>&1

# Генерация отзывов по расписанию (ежедневно в 12:00)
0 12 * * * php /path/to/site/cron/review-cron.php > /dev/null 2>&1

# ===== END KOSMOENGINE =====
```

## 📝 Важно

1. **Замените `ВАШЕ_ДОМЕН`** на ваш домен (например: `example.com`)
2. **Замените `CRON_SECRET`** на значение из файла `.env`
3. **Замените `/path/to/site/`** на реальный путь к сайту

## 🔑 Где найти CRON_SECRET?

После установки откройте файл `.env` в корне сайта:

```bash
cat /path/to/site/.env | grep CRON_SECRET
```

## ⚡ Альтернатива для shared-хостинга

Если у вас shared-хостинг (Jino, Beget, TimeWeb и др.), используйте панель управления:

1. Перейдите в раздел **Cron** / **Планировщик**
2. Добавьте URL-задачи:
   - `https://ваш-сайт.ru/api/cron-generate?secret=XXX&type=article`
   - `https://ваш-сайт.ru/api/cron-generate?secret=XXX&type=review`
3. Установите интервал: каждые 4-6 часов

## 📊 Что делают задачи

| Задача | Описание |
|--------|----------|
| `article-cron.php` | Генерация статей с помощью AI |
| `review-cron.php` | Генерация отзывов с помощью AI |
| `maintenance-cron.php` | Очистка кэша, бэкапы, оптимизация |
| `cron-generate` API | Альтернативный триггер через HTTP |

## 🔧 Проверка работы

В админке → **Мониторинг** вы увидите:
- Время последнего запуска cron
- Количество сгенерированного контента
- Ошибки (если есть)

---

После настройки cron система будет автоматически генерировать контент и поддерживать сайт в актуальном состоянии.

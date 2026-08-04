# Развёртывание проекта kosmozaim.ru

## 1. Стек проекта

Проект построен на PHP + MySQL и состоит из:
- публичного сайта на чистом PHP;
- админки в формате SPA на PHP + JS;
- интеграций с AI-сервисами (YandexGPT / YandexART, опционально Stability, GigaChat);
- SEO-модулей, индексации, аналитики, розыгрышей и мониторинга.

## 2. Минимальные требования

### Сервер
- PHP 8.1+ (лучше 8.2+)
- MySQL / MariaDB
- mod_rewrite / корректный `.htaccess`
- cURL
- OpenSSL
- mbstring
- JSON
- возможность запуска cron

### Права на запись
Проект должен иметь права на запись в:
- `data/`
- `data/backups/`
- `data/page_cache/`
- `data/api_cache/`
- `data/geo_cache/`
- `images/`
- `images/articles/`

## 3. Структура проекта

Основные папки:
- `index.php` — фронт-контроллер и роутинг;
- `config.php` — загрузка env, настройки, DB, сессии, утилиты;
- `pages/` — публичные страницы;
- `includes/` — helper-функции, layout, SEO, индексация, mail, мониторинг;
- `_admin/` — страницы админки;
- `_api/` — API для админки и публичных действий;
- `cron/` — cron-сценарии;
- `data/` — кэш, настройки, логи, JSON-данные;
- `images/` — загружаемые изображения и картинки статей.

## 4. Переменные окружения

Используется файл `.env` в корне проекта.

Ключевые переменные:
- `DATABASE_URL`
- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
- `NEXT_PUBLIC_SITE_URL`
- `YANDEX_GPT_API_KEY`
- `YANDEX_FOLDER_ID`
- `NEXT_PUBLIC_YANDEX_METRIKA_ID`
- `NEXT_PUBLIC_GOOGLE_ANALYTICS_ID`
- `SESSION_SECRET`
- `CRON_SECRET`

Если `DATABASE_URL` не задан, проект использует связку `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS`.

## 5. База данных

### Базовый импорт
Если разворачивается новый проект:
1. создать БД;
2. импортировать `database.sql`;
3. затем при необходимости применить дополнительные миграции из корня.

### Часто используемые миграции
- `database-users-migration.sql`
- `database-tags-migration.sql`
- `database-categories-migration.sql`
- `database-city-seo-migration.sql`
- `database-city-tag-seo-migration.sql`
- `database-postback-migration.sql`
- `database-newsletter-migration.sql`
- `database-2fa-migration.sql`
- `database-faq-migration.sql`
- `database-giveaway-migration.sql`
- `database-indexing-migration.sql`

## 6. Настройки файлов и кэша

При первом запуске система сама создаёт большую часть служебных каталогов, но лучше заранее проверить существование:
- `data/api_cache`
- `data/page_cache`
- `data/backups`
- `data/geo_cache`
- `images/articles`

## 7. Настройка домена и .htaccess

В корне проекта используется `.htaccess` с роутингом через `index.php`.

Проверьте, что включён `mod_rewrite`, и запросы к сайту идут в корень проекта.

Критично:
- реальных файлов и директорий rewrite не должен ломать;
- служебные директории должны быть недоступны напрямую;
- `robots.txt`, `sitemap.xml`, `llms.txt` должны открываться публично.

## 8. Первый вход в админку

URL:
- `/admin`
- `/admin/login`

Если таблица `admin_users` пуста, при первом запросе login API может создать пользователя `admin` со стандартным паролем `admin123`.

После первого входа обязательно:
- сменить пароль;
- включить 2FA;
- добавить свой IP в белый список (если используете ограничение по IP).

## 9. Настройки после развёртывания

В админке → `Настройки` заполните:
- название сайта;
- URL сайта;
- логотип и favicon;
- YandexGPT / YandexART ключи;
- SMTP;
- email обратной связи;
- провайдер картинок статей;
- шаблон промпта для картинок статей.

## 10. Внешние интеграции

### Яндекс
- YandexGPT / YandexART
- Яндекс.Вебмастер API
- Яндекс.Метрика

### Google
- Google Search Console API
- Google Indexing API
- Google Analytics

### Дополнительно
- Stability AI (опционально для генерации картинок)
- GigaChat / Kandinsky (опционально для генерации картинок)

## 11. Проверка после запуска

Что проверить вручную:
- главная страница `/`;
- каталог `/zajmy`;
- оффер `/offer/...`;
- статья `/articles/...`;
- админка `/admin`;
- `/robots.txt`;
- `/sitemap.xml`;
- `/llms.txt`;
- отправка формы обратной связи;
- SMTP тест в настройках;
- индексация Google / Яндекс;
- мониторинг.

## 12. Что проверить в Мониторинге

Вкладка `Мониторинг` должна показывать:
- состояние SMTP;
- статус внешних сервисов;
- SEO-интеграции;
- планировщик;
- бэкапы;
- кэш;
- последние ошибки.

## 13. Рекомендуемый порядок ввода в эксплуатацию

1. Импорт базы.
2. Проверка `.env`.
3. Проверка прав записи.
4. Проверка публичных страниц.
5. Вход в админку.
6. Смена пароля + 2FA.
7. Настройка SMTP.
8. Настройка Yandex / Google интеграций.
9. Проверка cron.
10. Проверка мониторинга.

## 14. Быстрый чек-лист

- [ ] База импортирована
- [ ] `.env` заполнен
- [ ] `data/` доступна на запись
- [ ] `images/articles/` доступна на запись
- [ ] `/admin` открывается
- [ ] SMTP настроен
- [ ] Мониторинг показывает зелёные статусы
- [ ] Cron настроен
- [ ] Индексация подключена
- [ ] Бэкап создаётся

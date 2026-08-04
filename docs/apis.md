# Внутренние API и интеграции проекта

## 1. Общие принципы

Проект использует:
- публичные API `/api/...`
- админские API `/api/admin/...`

Все админские действия требуют авторизации администратора, кроме login/logout/check и некоторых служебных маршрутов.

## 2. Публичные API

### `/api/health`
Проверка доступности приложения.

### `/api/subscribe`
Подписка на рассылку.

### `/api/offers`
Выдача публичных офферов.

### `/api/reviews`
Отправка публичного отзыва.

### `/api/postback`
Приём postback-конверсий от партнёрок.

### `/api/giveaway/active`
Информация об активном розыгрыше для сайта.

### `/api/cron-generate`
Крон-эндпоинт для авто-генерации отзывов и статей.
Требует `CRON_SECRET`.

## 3. Ключевые админские API

### Контент
- `/api/admin/offers`
- `/api/admin/articles`
- `/api/admin/tags`
- `/api/admin/categories`
- `/api/admin/reviews`

### SEO
- `/api/admin/city-seo`
- `/api/admin/city-tag-seo`
- `/api/admin/meta-generate`
- `/api/admin/tag-seo-generate`
- `/api/admin/seo-audit`
- `/api/admin/content-quality`

### Индексация и позиции
- `/api/admin/indexing`
- `/api/admin/google-indexing`
- `/api/admin/yandex-webmaster`
- `/api/admin/positions`
- `/api/admin/page-checker`

### Аналитика
- `/api/admin/stats`
- `/api/admin/funnel`
- `/api/admin/analytics`
- `/api/admin/smart-rating`

### Розыгрыши
- `/api/admin/giveaway`

### Система
- `/api/admin/settings`
- `/api/admin/system-monitor`
- `/api/admin/change-password`
- `/api/admin/two-factor`
- `/api/admin/security`
- `/api/admin/check`
- `/api/admin/logout`

## 4. AI-интеграции

### YandexGPT
Используется для:
- генерации статей;
- генерации отзывов;
- генерации SEO-текстов;
- генерации FAQ;
- улучшения качества контента;
- генерации meta-тегов;
- генерации SEO-комплектов для тегов.

Требует:
- `YANDEX_GPT_API_KEY`
- `YANDEX_FOLDER_ID`

### YandexART
Используется для генерации изображений статей.

### Stability AI
Опциональный провайдер картинок статей.
Используется API key, хранимый в настройках сайта.

### GigaChat / Kandinsky
Опциональный провайдер картинок статей.
Использует:
- authorization key;
- временный access token;
- `chat/completions` + `files/{id}/content`.

## 5. Google интеграции

### Google Search Console API
Используется для:
- позиций по запросам;
- позиций по страницам.

### Google Indexing API
Используется для:
- отправки новых/обновлённых URL;
- автоматической отправки после создания контента.

### Service Account
Настройки хранятся в:
- `data/google-service-account.json`

## 6. Яндекс интеграции

### Яндекс.Вебмастер API
Используется для:
- переобхода URL;
- массовой отправки новых URL;
- получения позиций и запросов.

Настройки хранятся в:
- `data/yandex-webmaster.json`

Поля:
- `oauth_token`
- `user_id`
- `host_id`
- `client_id`

## 7. Почта

Используется единый helper:
- `includes/mailer.php`

Поддержка:
- SMTP
- fallback на `mail()`

Настройки:
- `smtp_enabled`
- `smtp_host`
- `smtp_port`
- `smtp_user`
- `smtp_pass`
- `smtp_secure`
- `mail_from`
- `mail_from_name`
- `contact_email`

## 8. Постбеки и конверсии

### `postback_conversions`
Хранит:
- `offer_id`
- `status`
- `payout`
- `click_id`
- `aff_sub`
- `ip`
- `created_at`

Используется для:
- аналитики;
- воронки;
- EPC;
- розыгрышей;
- approval rate.

## 9. Качество контента

`/api/admin/content-quality`

Поддерживает действия:
- `analyze`
- `improve`
- `improve_until`
- `cleanup_only`

Используется в формах:
- статей
- тегов
- офферов

## 10. Логи и мониторинг

### Error log
- `data/error-log.json`

### Scheduler log
- `data/scheduler-fire.log`

### Article image log
- `data/article-image-log.json`

### Maintenance log
- `data/maintenance.log`

## 11. Кэш

### Страничный кэш
- `data/page_cache/`

### API-кэш
- `data/api_cache/`

### Geo-кэш
- `data/geo_cache/`

### Разные JSON-кэши
- офферы
- теги
- города
- перелинковка

## 12. Безопасность

### Админка
- сессии администратора
- 2FA
- IP whitelist
- rate-limit входа
- аудит-лог действий

### Пользователи
- отдельные пользовательские сессии
- регистрация / логин
- подтверждение email

## 13. Что важно помнить

- многие API очищают page/api cache после изменений;
- индексация и SEO сильно завязаны на внешние сервисы;
- логика проекта активно использует fallback, если внешний сервис недоступен;
- для production важно следить за Мониторингом и cron.

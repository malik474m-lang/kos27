# Сервер лицензирования — Космозайм

## Установка на serv.kosmozaim.ru

### 1. Загрузить файлы
Скопировать содержимое `license-server/` в корень домена `serv.kosmozaim.ru`:
```
~/domains/serv.kosmozaim.ru/
├── .htaccess
├── index.php
├── config.php
├── api/
│   ├── activate.php
│   ├── verify.php
│   ├── deactivate.php
│   └── heartbeat.php
└── admin/
    ├── index.php
    └── api.php
```

### 2. Создать БД
В phpMyAdmin создать БД `license_server` и выполнить `database.sql`.
Или через SSH:
```bash
mysql -u USER -p < database.sql
```

### 3. Настроить config.php
Изменить параметры подключения к БД:
```php
$host = 'localhost';
$name = 'ваша_база';
$user = 'ваш_пользователь';
$pass = 'ваш_пароль';
```

**ОБЯЗАТЕЛЬНО сменить ключи безопасности:**
```php
LICENSE_SIGN_KEY    — для подписи ответов
LICENSE_ENCRYPT_KEY — для шифрования токенов
LICENSE_SALT        — для хэширования
ADMIN_API_TOKEN     — для API админки
```

### 4. Установить пароль админа
В phpMyAdmin выполнить:
```sql
UPDATE admins SET password_hash = '$2y$12$...' WHERE username = 'admin';
```
Хэш можно сгенерировать: `php -r "echo password_hash('ваш_пароль', PASSWORD_BCRYPT);"`

### 5. Проверить
```
https://serv.kosmozaim.ru/           → {"server":"KZM License Server","status":"online"}
https://serv.kosmozaim.ru/admin      → Страница логина
```

## API

| Эндпоинт | Метод | Описание |
|----------|-------|----------|
| `/api/activate` | POST | Активация лицензии |
| `/api/verify` | POST | Проверка лицензии |
| `/api/heartbeat` | POST | Фоновая проверка |
| `/api/deactivate` | POST | Деактивация |
| `/api/status` | GET | Статус сервера |

## Безопасность
- Rate-limit на все эндпоинты
- HMAC-SHA256 подпись ответов
- AES-256-CBC шифрование токенов
- Привязка лицензии к домену
- Логирование всех действий
- Защита admin через сессию + rate-limit

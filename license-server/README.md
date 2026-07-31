# KosmoEngine License Server

## Установка
1. Скопируйте файлы на serv.kosmozaim.ru
2. Создайте БД MySQL и импортируйте database.sql
3. Скопируйте .env.example в .env и настройте
4. Войдите: admin / admin123
5. **СМЕНИТЕ ПАРОЛЬ!**

## API
- POST /api/check - проверка лицензии
- POST /api/activate - активация
- POST /api/info - информация

## Безопасность
- Защита от брутфорса (10 попыток = блок 15 мин)
- 2FA (TOTP)
- Привязка сессии к IP
- Аудит действий
- Блокировка при смене домена

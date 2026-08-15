# 📱 Космозайм — Мобильное приложение

Кроссплатформенное мобильное приложение (iOS + Android) для сайта [kosmozaim.ru](https://kosmozaim.ru).

Построено на **React Native (Expo)** и работает с существующим PHP API бекенда.

## 📋 Функции приложения

### Экраны:
- **🏠 Главная** — hero-блок, категории (Займы/Кредиты/Карты), лучшие предложения, баннер розыгрыша, баннер калькулятора
- **📋 Каталог** — просмотр офферов по категориям с фильтрами (сумма, срок, заёмщик)
- **🧮 Калькулятор** — интерактивный калькулятор с слайдерами, автоматический подбор подходящих предложений
- **📰 Статьи** — лента статей с обложками
- **👤 Профиль** — авторизация/регистрация, подписка на рассылку, избранное, настройки
- **❤️ Избранное** — сохранённые предложения (AsyncStorage)
- **📄 Детальный оффер** — полная карточка предложения с метриками, отзывами, кнопкой «Оформить»

### Возможности:
- Pull-to-refresh на всех экранах
- Фильтрация офферов по параметрам
- Добавление в избранное
- Отправка отзывов
- Регистрация/вход пользователя
- Подписка на email-рассылку
- Переход к оформлению через `/click/{id}`
- Адаптивный дизайн (iPhone, Android)

## 🗂 Структура проекта

```
mobile-app/
├── App.tsx                    # Главный файл — навигация, провайдеры
├── app.json                   # Expo конфигурация
├── package.json               # Зависимости
├── tsconfig.json              # TypeScript
├── babel.config.js            # Babel
│
├── src/
│   ├── api/
│   │   ├── client.ts          # HTTP-клиент (fetch)
│   │   ├── endpoints.ts       # Все API вызовы
│   │   └── types.ts           # TypeScript типы (Offer, Article, etc.)
│   │
│   ├── components/
│   │   ├── OfferCard.tsx      # Карточка оффера (аналог PHP offer-card.php)
│   │   ├── ArticleCard.tsx    # Карточка статьи
│   │   ├── CategoryCard.tsx   # Карточка категории
│   │   ├── GradientHeader.tsx # Градиентный заголовок
│   │   └── LoadingScreen.tsx  # Экран загрузки
│   │
│   ├── screens/
│   │   ├── HomeScreen.tsx         # Главная
│   │   ├── CatalogScreen.tsx      # Каталог офферов
│   │   ├── OfferDetailScreen.tsx  # Детали оффера
│   │   ├── CalculatorScreen.tsx   # Калькулятор займа
│   │   ├── ArticlesScreen.tsx     # Список статей
│   │   ├── FavoritesScreen.tsx    # Избранное
│   │   └── ProfileScreen.tsx      # Профиль/авторизация
│   │
│   ├── hooks/
│   │   ├── useAuth.ts         # Авторизация (Context + AsyncStorage)
│   │   └── useFavorites.ts    # Избранное (AsyncStorage)
│   │
│   ├── utils/
│   │   └── format.ts          # Утилиты форматирования (аналоги PHP)
│   │
│   ├── constants/
│   │   └── config.ts          # API URL, цвета, константы
│   │
│   └── assets/                # Иконки, splash screen
│
└── server-api/                # PHP-файлы для сервера
    ├── articles-api.php       # Новый API endpoint для статей
    └── router-patch.php       # Патч для _api/router.php (CORS + новые routes)
```

## 🚀 Быстрый старт

### 1. Установка

```bash
cd mobile-app
npm install
```

### 2. Добавить Slider (для калькулятора)

```bash
npx expo install @react-native-community/slider
```

### 3. Запуск

```bash
# Expo Go (для разработки)
npx expo start

# iOS симулятор
npx expo start --ios

# Android эмулятор
npx expo start --android
```

### 4. Сборка для Store

```bash
# Установить EAS CLI
npm install -g eas-cli

# Сборка для iOS
eas build --platform ios

# Сборка для Android
eas build --platform android
```

## 🔧 Настройка серверной части

### Необходимые изменения на сервере kosmozaim.ru:

#### 1. Добавить CORS в `_api/router.php`

В начало файла (после `header('Content-Type: ...')`) добавить:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
```

#### 2. Добавить API endpoint для статей

Скопировать `server-api/articles-api.php` → `~/domains/kosmozaim.ru/_api/articles.php`

Добавить в `_api/router.php` строку:
```php
if ($apiUri === '/articles') { require __DIR__ . '/articles.php'; exit; }
```

## 📱 API endpoints, используемые приложением

| Endpoint | Метод | Описание |
|----------|-------|----------|
| `/api/health` | GET | Проверка доступности |
| `/api/offers` | GET | Список офферов (?category=microloans) |
| `/api/offers?ids=1,2,3` | GET | Офферы по ID (для избранного) |
| `/api/articles` | GET | Список статей **[НОВЫЙ]** |
| `/api/geo` | GET | Гео-определение |
| `/api/giveaway/active` | GET | Активный розыгрыш |
| `/api/subscribe` | POST | Подписка на рассылку |
| `/api/reviews` | POST | Отправка отзыва |
| `/api/user/login` | POST | Авторизация |
| `/api/user/register` | POST | Регистрация |
| `/api/user/verify` | POST | Подтверждение email |
| `/api/user/logout` | POST | Выход |
| `/api/user/profile` | GET | Профиль пользователя |
| `/click/{id}` | GET | Переход к партнёру (через Linking.openURL) |

## 🎨 Дизайн

Приложение использует цветовую схему сайта:
- **Primary:** `#1a56db` (синий)
- **Accent:** `#059669` (зелёный, кнопка «Оформить»)
- **Gradient:** `#1a56db` → `#7e3af2` (Hero)
- **Background:** `#f9fafb`

Иконки: emoji-based (как на сайте)

## 📝 Что можно добавить

- Push-уведомления (Expo Notifications)
- Биометрическая авторизация (FaceID/TouchID)
- Офлайн-кэш (AsyncStorage кэширование API)
- WebView для деталей статей
- Deeplinks (kosmozaim://offer/slug)
- Интеграция с Apple Pay / Google Pay
- Виджеты для iOS/Android
- Сравнение офферов

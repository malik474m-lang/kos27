# Документация KosmoEngine / kosmozaim.ru

Этот раздел содержит техническую документацию по проекту kosmozaim.ru.

## Содержание

### Основные документы
- [setup.md](./setup.md) — развёртывание проекта, базовые требования, первый запуск
- [admin-guide.md](./admin-guide.md) — руководство по админке и основным модулям
- [apis.md](./apis.md) — описание внутренних API и интеграций
- [cron.md](./cron.md) — cron-задачи, планировщик и обслуживание
- [stabilization-checklist.md](./stabilization-checklist.md) — чек-лист стабилизации после релизов
- [production-ready-audit.md](./production-ready-audit.md) — аудит готовности к production
- [white-label-porting.md](./white-label-porting.md) — перенос на новый домен / white-label

### Готовые конфигурации
- [crontab-setup.md](./crontab-setup.md) — готовые примеры cron для хостинга
- [email-setup-jino.txt](./email-setup-jino.txt) — настройка SPF/DKIM/DMARC/BIMI на Jino

### Аудит и перенос
- [production-audit.md](./production-audit.md) — production-ready чек-лист проекта
- [white-label-migration.md](./white-label-migration.md) — перенос на новый домен / white-label

### Оценка проекта
- [ocenka-proekta-i-kak-povysit-stoimost.txt](./ocenka-proekta-i-kak-povysit-stoimost.txt) — оценка проекта и рекомендации по росту стоимости

## Для кого эти документы

- владелец проекта;
- технический специалист, который будет сопровождать сайт;
- покупатель/инвестор, которому нужно быстро понять архитектуру;
- разработчик, который будет переносить проект на другой домен или сервер.

## Ключевые разделы проекта

- публичный сайт на PHP;
- админка с единым SPA-интерфейсом;
- AI-генерация контента и изображений;
- SEO-инструменты, индексация и позиции;
- воронка, аналитика, партнёрские ссылки;
- планировщик, бэкапы, мониторинг и безопасность.

## Быстрый старт

1. Открыть [setup.md](./setup.md) для развёртывания
2. Открыть [admin-guide.md](./admin-guide.md) для работы с админкой
3. Открыть [production-audit.md](./production-audit.md) для проверки готовности
4. Открыть [white-label-migration.md](./white-label-migration.md) для переноса

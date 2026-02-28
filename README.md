# DFD1 Site

Финальная зафиксированная версия сайта Department of Functional Diagnostics No. 1 / OFD1 PavlovMed.

## Структура

- `backend/` - Laravel + Filament admin, текущая серверная часть live-проекта.
- `frontend/` - Vue 3 + Vite исходники витрины.
- `exports/` - JSON-экспорт публичного контента live-сайта на момент фиксации.

## Backend

Основные характеристики:

- Laravel + Filament admin
- публичные API для витрины
- медиа в `storage/app/public`
- production deploy на Beget shared hosting

Рабочий production path на сервере:

- Laravel root: `~/ofd1-pavlovmed.ru/fdc-admin/`
- document root: `~/ofd1-pavlovmed.ru/fdc-admin/public`

На Beget для artisan-команд используется `php8.2`.

Примеры:

```bash
php8.2 artisan migrate --force
php8.2 artisan optimize:clear
php8.2 artisan storage:link
```

## Frontend

Vue 3 + Vite storefront, работающий поверх API Laravel.

Сборка:

```bash
npm install
npm run build
```

## Exports

Каталог `exports/` содержит контрольный JSON-снимок live API:

- `settings.json`
- `blocks.json`
- `doctors.json`
- `services.json`
- `gallery.json`
- `reviews.json`

## Secrets

Секреты в репозиторий не включены. Используйте `.env.example` как шаблон.

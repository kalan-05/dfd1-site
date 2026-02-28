# FDC (Отделение функциональной диагностики №1)

Сайт отделения на `Vue 3 + Vite` с PHP-обработчиком формы отзывов.

## Стек

- Frontend: Vue 3, Vue Router, Vite, Sass, Swiper
- Backend (форма отзывов): PHP 8+, PHPMailer

## Быстрый старт

1. Установить frontend-зависимости:
   ```bash
   npm install
   ```
2. Установить backend-зависимости:
   ```bash
   composer install
   ```
3. Создать локальный `.env`:
   ```bash
   cp .env.example .env
   ```

## Обязательные переменные

### Frontend

- `VITE_REVIEW_ENDPOINT=/php/send-review.php`
- `VITE_HCAPTCHA_SITEKEY=...`
- `VITE_HCAPTCHA_REQUIRED=true`

### Backend (PHP)

- `HCAPTCHA_REQUIRED=true`
- `HCAPTCHA_SECRET=...`
- `SMTP_HOST`, `SMTP_PORT`, `SMTP_SECURE`, `SMTP_USER`, `SMTP_PASS`
- `MAIL_FROM_EMAIL`, `MAIL_TO_EMAIL`

## Локальный запуск

1. Запустить PHP-сервер из корня проекта:
   ```bash
   php -S localhost:5174 -t public
   ```
2. В отдельном терминале запустить Vite:
   ```bash
   npm run dev
   ```

Vite проксирует запросы `/php/*` на `http://localhost:5174`.

## Почему могла быть ошибка "отзыв не отправлен"

- Не запущен PHP-сервер на `localhost:5174`.
- Не настроены SMTP-переменные (почта не отправляется).
- Не заполнены ключи hCaptcha при `HCAPTCHA_REQUIRED=true`.

## Поведение отправки отзыва

- Отзыв всегда сохраняется в `storage/reviews/reviews.jsonl` после успешной валидации.
- Ошибка SMTP больше не блокирует прием отзыва: письмо может не уйти, но отзыв будет принят.
- При включенной hCaptcha отзыв без валидного токена отклоняется.

## Production сборка frontend

```bash
npm run build
```

## Безопасность

- Не храните SMTP-пароли и ключи капчи в репозитории.
- Держите `SMTP_DEBUG=0` в production.
- `storage/logs` и `storage/reviews` не должны быть публично доступны.

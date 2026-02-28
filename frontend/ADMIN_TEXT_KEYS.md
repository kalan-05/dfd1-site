# Редактирование текста через админку (FDC)

Теперь витрина читает текст из `PageBlocks` + `SiteSettings`.

## 1) Основные блоки (`Сайт -> Блоки страницы`)
- `hero`
  - `title` -> верхняя строка на первом экране
  - `content` -> главный заголовок
- `about`
  - `title` -> заголовок секции
  - `content` -> вводный абзац
  - остальные тексты секции `О нас` -> через `meta` (ключи ниже)
- `services`
  - `title`, `content`
  - список услуг берется из `Сайт -> Услуги`
- `doctors`
  - `title`, `content`
  - врачи берутся из `Сайт -> Врачи`
- `gallery`
  - кнопки слайдера можно менять через `meta`
  - фото берутся из `Сайт -> Галерея`
- `reviews`
  - заголовок и все подписи формы через `meta`
  - опубликованные отзывы берутся из API `/api/reviews`
- `contact`
  - `title`
  - контакты берутся из `Сайт -> Настройки сайта`
- `map`
  - `title` -> большой заголовок над картой
  - `content` или `meta.subtitle` -> подзаголовок

## 2) Дополнительные (опциональные) блоки
Можно создать блоки:
- `header` (для текста шапки)
- `footer` (для текста футера)

## 3) Meta-ключи

### `about` meta
- `block1_title`
- `block1_items` (каждый пункт с новой строки)
- `block1_history_title`
- `block1_history_text`
- `history_text` (абзацы с новой строки)
- `block2_title`
- `block2_group1_title`
- `block2_group1_items` (строка = пункт)
- `block2_group2_title`
- `block2_group2_items` (строка = пункт)
- `block3_lead`
- `block3_diagnosis`
- `block3_text`
- `block4_title`
- `block4_items` (строка = пункт)
- `final_text` (абзацы с новой строки)

### `doctors` meta
- `subtitle`
- `team_count_label`
- `team_image_alt`

### `gallery` meta
- `prev_label`
- `next_label`

### `reviews` meta
- `doctor_prefix`
- `prev_label`
- `next_label`
- `prev_aria_label`
- `next_aria_label`
- `form_title`
- `name_label`
- `name_placeholder`
- `doctor_label`
- `doctor_placeholder`
- `rating_label`
- `rating_placeholder`
- `review_text_label`
- `review_text_placeholder`
- `submit_label`
- `submitting_label`
- `success_message`
- `error_message`
- `network_error_message`
- `spam_message`
- `captcha_missing_message`
- `captcha_required_message`
- `anonymous_label`
- `initial_reviews` (массив стартовых отзывов, редактируется из админки)

### `map` meta
- `subtitle` (приоритетнее `content`)
- `map_aria_label`
- `fallback_text`
- `fallback_link_text`
- `menu_label` (название пункта "Карта" в футере)

### `header` meta (если создадите блок `header`)
- `booking_label`
- `booking_url`
- `department_url`
- `logo_title`
- `logo_lines` (строки через Enter)
- `logo_alt`

### `footer` (если создадите блок `footer`)
- `title` -> левая колонка, заголовок
- `content` -> левая колонка, текст
- meta:
  - `developer_label`
  - `developer_url`
  - `developer_aria_label`
  - `copyright`

## 4) Важно
- Названия пунктов меню в шапке/футере берутся из `title` блоков `about/services/doctors/reviews/contact`.
- Телефон, email, адрес, график, SEO, координаты карты редактируются в `Настройки сайта`.

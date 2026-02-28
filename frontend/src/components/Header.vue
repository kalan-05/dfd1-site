<template>
  <header class="header" data-js-header>
    <div class="header__body">
      <div class="header__body-inner container">
        <div class="logo-block">
          <RouterLink
            class="header__logo logo"
            :to="{ path: '/' }"
            aria-label="Перейти на главную"
            title="Перейти на главную"
            data-goal="cta_click"
            data-goal-params='{"position":"header","target":"logo"}'
          >
            <img
              class="logo__image"
              :src="logoUrl"
              width="275"
              height="268"
              :alt="logoImageAlt"
              title="Перейти на главную страницу"
            />
          </RouterLink>

          <a
            class="logo__title"
            :href="departmentPageUrl"
            target="_blank"
            aria-label="Страница отделения"
            :title="logoTitleText"
          >
            <p class="logo__text">
              <template v-for="(line, index) in logoTitleLines" :key="`logo-line-${index}`">
                {{ line }}
                <br v-if="index < logoTitleLines.length - 1">
              </template>
            </p>
          </a>
        </div>

        <button
          class="header__contact-btn"
          type="button"
          @click="openExternalBooking"
          :aria-label="bookingLabel"
          :title="bookingLabel"
          data-goal="cta_click"
          data-goal-params='{"position":"header","cta":"contact_button"}'
        >
          {{ bookingLabel }}
        </button>

        <div class="header__overlay" data-js-header-overlay>
          <nav class="header__menu">
            <ul class="header__menu-list">
              <li v-for="(item, index) in menuItems" :key="item.key" class="header__menu-item">
                <RouterLink
                  class="header__menu-link"
                  :class="{ 'is-active': index === 0 }"
                  :to="{ path: '/', hash: item.hash }"
                  :aria-label="`Перейти к разделу ${item.label}`"
                  :title="item.label"
                  data-goal="cta_click"
                  :data-goal-params="menuGoalParams(item.key)"
                >
                  <span>{{ item.label }}</span>
                </RouterLink>
              </li>
            </ul>
          </nav>
        </div>

        <button
          class="burger-button visible-mobile"
          type="button"
          aria-label="Открыть меню"
          title="Открыть меню"
          data-js-header-burger-button
          data-goal="cta_click"
          data-goal-params='{"position":"header","cta":"burger_open"}'
        >
          <span class="burger-button__line" aria-hidden="true"></span>
          <span class="burger-button__line" aria-hidden="true"></span>
          <span class="burger-button__line" aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import logoUrl from '@/assets/image/LogoDFD1.webp';
import { fetchBlocks, fetchSettings } from '@/js/siteContentApi';

const blocks = ref([]);
const settings = ref(null);

function normalizeMeta(rawMeta) {
  if (!rawMeta || typeof rawMeta !== 'object') {
    return {};
  }

  if (Array.isArray(rawMeta)) {
    return rawMeta.reduce((acc, row) => {
      if (!row || typeof row !== 'object') {
        return acc;
      }

      const key = String(row.key ?? '').trim();
      if (!key) {
        return acc;
      }

      acc[key] = row.value ?? '';
      return acc;
    }, {});
  }

  return rawMeta;
}

function toLines(value, fallback) {
  const raw = String(value || fallback || '');
  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}

function findBlock(key) {
  if (!Array.isArray(blocks.value)) {
    return null;
  }
  return blocks.value.find((item) => item?.key === key) || null;
}

function blockTitle(key, fallback) {
  const block = findBlock(key);
  const title = String(block?.title || '').trim();
  return title || fallback;
}

const headerMeta = computed(() => normalizeMeta(findBlock('header')?.meta));

const bookingLabel = computed(() => String(headerMeta.value.booking_label || '').trim() || 'Записаться');
const bookingUrl = computed(() => String(headerMeta.value.booking_url || '').trim() || 'https://www.1spbgmu.ru/klinika/platnye-uslugi');
const departmentPageUrl = computed(() => String(headerMeta.value.department_url || '').trim() || 'https://www.1spbgmu.ru/klinika/kliniki-pspbgmu/106-glavnaya/3715-otdelenie-funktsionalnoj-diagnostiki-1');

const logoTitleText = computed(() => String(headerMeta.value.logo_title || '').trim() || settings.value?.site_name || 'Медицинский центр');
const logoTitleLines = computed(() => {
  const customText = String(headerMeta.value.logo_lines || '').trim();
  return toLines(customText, logoTitleText.value);
});
const logoImageAlt = computed(() => String(headerMeta.value.logo_alt || '').trim() || 'Логотип медицинского центра');

const menuItems = computed(() => [
  { key: 'about', hash: '#about', label: blockTitle('about', 'О нас') },
  { key: 'services', hash: '#services', label: blockTitle('services', 'Диагностика') },
  { key: 'pricing', hash: '#pricing', label: blockTitle('doctors', 'Врачи') },
  { key: 'reviews', hash: '#reviews', label: blockTitle('reviews', 'Отзывы') },
  { key: 'contact', hash: '#contact', label: blockTitle('contact', 'Контакты') },
]);

function openExternalBooking(event) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  window.open(bookingUrl.value, '_blank', 'noopener');
}

function menuGoalParams(key) {
  return JSON.stringify({
    position: 'header_menu',
    nav: key,
  });
}

onMounted(async () => {
  const [settingsResponse, blocksResponse] = await Promise.all([
    fetchSettings(),
    fetchBlocks(),
  ]);

  settings.value = settingsResponse || null;
  blocks.value = Array.isArray(blocksResponse) ? blocksResponse : [];
});
</script>

<template>
  <footer class="footer">
    <div class="footer__container container">
      <div class="footer__column footer__column--about">
        <p class="footer__title">{{ footerTitle }}</p>
        <p class="footer__about">{{ footerAbout }}</p>
      </div>

      <nav class="footer__column footer__column--menu footer__menu" aria-label="Навигация по разделам сайта">
        <a v-for="item in menuItems" :key="item.key" :href="item.hash" class="footer__menu-link">{{ item.label }}</a>
      </nav>

      <a
        class="footer__column footer__column--developer footer__link"
        :href="developerUrl"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="developerAriaLabel"
      >
        <div class="footer__developer-row">
          <p class="footer__developer">{{ developerLabel }}</p>

          <div class="footer__imglogokav">
            <img
              class="logokavweb"
              :src="logoKavweb"
              width="36"
              height="23"
              alt="Логотип разработчика"
              loading="lazy"
              decoding="async"
            >
          </div>
        </div>

        <p class="footer__copy">{{ copyrightText }}</p>
      </a>
    </div>
  </footer>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import logoKavweb from '@/assets/image/kavLogoBlack.webp';
import { fetchBlocks } from '@/js/siteContentApi';

const blocks = ref([]);

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

function findBlock(key) {
  if (!Array.isArray(blocks.value)) {
    return null;
  }

  return blocks.value.find((item) => item?.key === key) || null;
}

function blockTitle(key, fallback) {
  const title = String(findBlock(key)?.title || '').trim();
  return title || fallback;
}

function blockMetaValue(key, metaKey, fallback = '') {
  const block = findBlock(key);
  const meta = normalizeMeta(block?.meta);
  const value = String(meta[metaKey] || '').trim();
  return value || fallback;
}

const footerBlock = computed(() => findBlock('footer'));
const footerMeta = computed(() => normalizeMeta(footerBlock.value?.meta));

const footerTitle = computed(() => {
  const value = String(footerBlock.value?.title || '').trim();
  return value || 'Медицинский центр.';
});

const footerAbout = computed(() => {
  const value = String(footerBlock.value?.content || '').trim();
  return value || 'Медицинские услуги и консультации специалистов.';
});

const developerLabel = computed(() => String(footerMeta.value.developer_label || '').trim() || 'Разработчик');
const developerUrl = computed(() => String(footerMeta.value.developer_url || '').trim() || 'https://kavweb.ru');
const developerAriaLabel = computed(() => String(footerMeta.value.developer_aria_label || '').trim() || 'Перейти на сайт разработчика');
const copyrightText = computed(() => {
  const value = String(footerMeta.value.copyright || '').trim();
  return value || '© 2025. Все права защищены.';
});

const mapMenuLabel = computed(() => {
  const label = blockMetaValue('map', 'menu_label', '');
  return label || 'Карта';
});

const menuItems = computed(() => [
  { key: 'about', hash: '#about', label: blockTitle('about', 'О нас') },
  { key: 'services', hash: '#services', label: blockTitle('services', 'Диагностика') },
  { key: 'pricing', hash: '#pricing', label: blockTitle('doctors', 'Врачи') },
  { key: 'reviews', hash: '#reviews', label: blockTitle('reviews', 'Отзывы') },
  { key: 'contact', hash: '#contact', label: blockTitle('contact', 'Контакты') },
  { key: 'map', hash: '#map-section', label: mapMenuLabel.value },
]);

onMounted(async () => {
  const blocksResponse = await fetchBlocks();
  blocks.value = Array.isArray(blocksResponse) ? blocksResponse : [];
});
</script>

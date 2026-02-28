<template>
  <section class="section contact container" :id="$attrs.id || 'contact'">
    <h2 class="h2">{{ sectionTitle }}</h2>

    <div class="contact__grid">
      <div class="contact__info">
        <div class="contacts-item">
          <div class="icon-container">
            <img
              class="contact-icon phone"
              :src="phoneIcon"
              alt="Телефон для связи"
              title="Телефон для связи"
              loading="lazy"
              decoding="async"
            >
          </div>
          <a
            :href="phoneHref"
            class="contacts-link"
            :aria-label="`Позвонить по телефону ${phoneText}`"
            title="Позвонить"
          >
            {{ phoneText }}
          </a>
        </div>

        <div class="contacts-item">
          <div class="icon-container">
            <img
              class="contact-icon address"
              :src="addressIcon"
              alt="Адрес"
              title="Адрес"
              loading="lazy"
              decoding="async"
            >
          </div>
          <span class="contacts-text">
            {{ addressText }}
          </span>
        </div>
      </div>

      <div class="contacts-column">
        <div class="contacts-item">
          <div class="icon-container">
            <img
              class="contact-icon mail"
              :src="emailIcon"
              alt="Электронная почта"
              title="Электронная почта"
              loading="lazy"
              decoding="async"
            >
          </div>
          <a
            :href="emailHref"
            class="contacts-link"
            aria-label="Написать на электронную почту"
            title="Написать письмо"
          >
            {{ emailText }}
          </a>
        </div>

        <div class="contacts-item">
          <div class="icon-container">
            <img
              class="contact-icon work"
              :src="workIcon"
              alt="Режим работы"
              title="Режим работы"
              loading="lazy"
              decoding="async"
            >
          </div>
          <span class="contacts-text">{{ worktimeText }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import phoneIcon from '@/assets/icons/telefon_32.webp';
import addressIcon from '@/assets/icons/address.webp';
import emailIcon from '@/assets/icons/mailto_32.webp';
import workIcon from '@/assets/icons/working.webp';

const props = defineProps({
  title: {
    type: String,
    default: 'Контакты',
  },
  settings: {
    type: Object,
    default: () => ({}),
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
});

const phones = computed(() => {
  if (Array.isArray(props.settings.phones) && props.settings.phones.length > 0) {
    return props.settings.phones;
  }

  return [];
});

const phoneText = computed(() => phones.value[0] || '');
const phoneHref = computed(() => {
  const digits = phoneText.value.replace(/[^\d+]/g, '');
  return digits ? `tel:${digits}` : '#';
});

const emailText = computed(() => props.settings.email || '');
const emailHref = computed(() => `mailto:${emailText.value}`);

const addressText = computed(() => props.settings.address_main || '');
const worktimeText = computed(() => props.settings.worktime_main || '');
const sectionTitle = computed(() => props.title || 'Контакты');
</script>

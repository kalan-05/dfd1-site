<template>
  <section class="section about container" :id="$attrs.id || 'about'">
    <div class="about__wrapper">
      <div class="about__title">
        <h2>{{ sectionTitle }}</h2>
      </div>

      <div class="about__title-blog" :style="introStyle">
        <p>{{ sectionIntro }}</p>
      </div>

      <div class="about__blog blog-1">
        <div class="about__text text_1">
          <h5>{{ block1Title }}</h5>
          <ul class="text__blog">
            <li v-for="item in block1Items" :key="item">{{ item }}</li>
          </ul>
          <h5 class="h2_b1">{{ block1HistoryTitle }}</h5>
          <p>{{ block1HistoryText }}</p>
        </div>
        <div class="about__img_r">
          <img
            class="photo photo_1"
            :src="about1"
            width="843"
            height="602"
            alt="about"
            loading="lazy"
            decoding="async"
          />
        </div>
      </div>

      <div class="about__history">
        <p v-for="(paragraph, index) in historyParagraphs" :key="`history-${index}`">
          {{ paragraph }}
        </p>
      </div>

      <div class="title-blog"></div>

      <div class="about__blog blog-2">
        <div class="about__img_l">
          <img
            class="photo photo_2"
            :src="about2"
            width="960"
            height="686"
            alt="about"
            loading="lazy"
            decoding="async"
          />
        </div>
        <div class="about__text">
          <article>
            <h5>{{ block2Title }}</h5>
            <h6>{{ block2Group1Title }}</h6>
            <ul class="text-blog">
              <li v-for="item in block2Group1Items" :key="item">{{ item }}</li>
            </ul>
          </article>
          <article>
            <h6>{{ block2Group2Title }}</h6>
            <ul class="text-blog">
              <li v-for="item in block2Group2Items" :key="item">{{ item }}</li>
            </ul>
          </article>
        </div>
      </div>

      <div class="about__blog blog-3">
        <div class="about__text">
          <p>{{ block3Lead }}</p>
          <p>{{ block3Diagnosis }}</p>
          <p>{{ block3Text }}</p>
        </div>
        <div class="about__img_r">
          <img
            class="photo photo_3"
            :src="about3"
            width="960"
            height="686"
            alt="about"
            loading="lazy"
            decoding="async"
          />
        </div>
      </div>

      <div class="about__blog blog-4">
        <div class="about__img_l">
          <img
            class="photo photo_4"
            :src="about4"
            width="666"
            height="476"
            alt="about"
            loading="lazy"
            decoding="async"
          />
        </div>
        <div class="about__text article4">
          <h5 class="text-article4">{{ block4Title }}</h5>
          <ul>
            <li v-for="item in block4Items" :key="item">{{ item }}</li>
          </ul>
        </div>
      </div>

      <p v-for="(paragraph, index) in finalParagraphs" :key="`final-${index}`">
        {{ paragraph }}
      </p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import about1 from '@/assets/image/about-1.webp';
import about2 from '@/assets/image/about-2.webp';
import about3 from '@/assets/image/about-3.webp';
import about4 from '@/assets/image/about-4.webp';

const props = defineProps({
  title: {
    type: String,
    default: 'О нас',
  },
  content: {
    type: String,
    default: '',
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
});

const defaultIntro = '';

const defaultMeta = {
  block1_title: '',
  block1_items: '',
  block1_history_title: '',
  block1_history_text: '',
  history_text: '',
  block2_title: '',
  block2_group1_title: '',
  block2_group1_items: '',
  block2_group2_title: '',
  block2_group2_items: '',
  block3_lead: '',
  block3_diagnosis: '',
  block3_text: '',
  block4_title: '',
  block4_items: '',
  final_text: '',
};

function splitToList(value) {
  return String(value || '')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);
}

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

const meta = computed(() => normalizeMeta(props.meta));

const introStyle = computed(() => {
  const value = String(meta.value.content_alignment || '').trim().toLowerCase();
  return {
    textAlign: ['left', 'center', 'right', 'justify'].includes(value) ? value : 'left',
  };
});

function readMeta(key) {
  const value = meta.value[key];
  if (typeof value === 'string' && value.trim() !== '') {
    return value.trim();
  }

  return defaultMeta[key] || '';
}

const sectionTitle = computed(() => props.title || 'О нас');
const sectionIntro = computed(() => props.content || defaultIntro);

const block1Title = computed(() => readMeta('block1_title'));
const block1Items = computed(() => splitToList(readMeta('block1_items')));
const block1HistoryTitle = computed(() => readMeta('block1_history_title'));
const block1HistoryText = computed(() => readMeta('block1_history_text'));

const historyParagraphs = computed(() => splitToList(readMeta('history_text')));

const block2Title = computed(() => readMeta('block2_title'));
const block2Group1Title = computed(() => readMeta('block2_group1_title'));
const block2Group1Items = computed(() => splitToList(readMeta('block2_group1_items')));
const block2Group2Title = computed(() => readMeta('block2_group2_title'));
const block2Group2Items = computed(() => splitToList(readMeta('block2_group2_items')));

const block3Lead = computed(() => readMeta('block3_lead'));
const block3Diagnosis = computed(() => readMeta('block3_diagnosis'));
const block3Text = computed(() => readMeta('block3_text'));

const block4Title = computed(() => readMeta('block4_title'));
const block4Items = computed(() => splitToList(readMeta('block4_items')));
const finalParagraphs = computed(() => splitToList(readMeta('final_text')));
</script>

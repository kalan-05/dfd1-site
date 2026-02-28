<template>
  <section class="section map-section container" :id="$attrs.id || 'map-section'">
    <h3>{{ sectionTitle }}</h3>
    <h5>{{ sectionSubtitle }}</h5>

    <div
      ref="mapRoot"
      class="map-section__map"
      :aria-label="mapAriaLabel"
    ></div>

    <p v-if="!isMapReady && loadTried" class="map-section__fallback">
      {{ mapFallbackText }}
      <a :href="fallbackMapUrl" target="_blank" rel="noopener">{{ mapFallbackLinkText }}</a>
    </p>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { loadYandexMaps } from '@/plugins/yandexMapsLoader';

const props = defineProps({
  title: {
    type: String,
    default: 'Как нас найти',
  },
  subtitle: {
    type: String,
    default: 'Медицинский центр',
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
const sectionTitle = computed(() => props.title);
const sectionSubtitle = computed(() => props.subtitle);

const mapAriaLabel = computed(() => String(meta.value.map_aria_label || '').trim() || 'Карта проезда отделения');
const mapFallbackText = computed(() => String(meta.value.fallback_text || '').trim() || 'Не удалось загрузить интерактивную карту.');
const mapFallbackLinkText = computed(() => String(meta.value.fallback_link_text || '').trim() || 'Открыть место на Яндекс.Картах');

const lat = computed(() => Number(props.settings?.map?.lat ?? 59.9386));
const lng = computed(() => Number(props.settings?.map?.lng ?? 30.3141));
const zoom = computed(() => Number(props.settings?.map?.zoom ?? 16) || 16);
const center = computed(() => [lat.value, lng.value]);

const fallbackMapUrl = computed(() => `https://yandex.ru/maps/?pt=${lng.value},${lat.value}&z=${zoom.value}&l=map`);
const primaryPhone = computed(() => {
  if (Array.isArray(props.settings?.phones) && props.settings.phones.length > 0) {
    return props.settings.phones[0];
  }
  return '';
});
const addressText = computed(() => props.settings?.address_main || 'Адрес не указан');

const mapRoot = ref(null);
const isMapReady = ref(false);
const loadTried = ref(false);
const mapInitStarted = ref(false);

let mapInstance = null;
let placemarkInstance = null;
let mapLoadObserver = null;

function initMap(ymaps) {
  const rootEl = mapRoot.value;
  if (!rootEl) {
    throw new Error('[MapSection] mapRoot is missing');
  }

  mapInstance = new ymaps.Map(
    rootEl,
    {
      center: center.value,
      zoom: zoom.value,
      controls: ['zoomControl', 'fullscreenControl'],
    },
    {
      suppressMapOpenBlock: true,
    }
  );

  placemarkInstance = new ymaps.Placemark(
    center.value,
    {
      hintContent: sectionSubtitle.value,
      balloonContent: `
        <strong>${sectionSubtitle.value}</strong><br/>
        ${addressText.value}<br/>
        Тел.: ${primaryPhone.value}
      `,
    },
    {
      preset: 'islands#redIcon',
      draggable: false,
    }
  );

  mapInstance.geoObjects.add(placemarkInstance);
  mapInstance.setCenter(center.value, zoom.value, { checkZoomRange: true });
}

async function startMapInit() {
  if (mapInitStarted.value) {
    return;
  }

  mapInitStarted.value = true;
  loadTried.value = false;

  try {
    const ymaps = await loadYandexMaps();
    initMap(ymaps);
    isMapReady.value = true;
  } catch (error) {
    console.error('[MapSection] map init error:', error);
    isMapReady.value = false;
  } finally {
    loadTried.value = true;
  }
}

function setupDeferredMapLoad() {
  const rootEl = mapRoot.value;
  if (!rootEl) {
    return;
  }

  if (typeof window === 'undefined' || typeof window.IntersectionObserver === 'undefined') {
    void startMapInit();
    return;
  }

  mapLoadObserver = new window.IntersectionObserver(
    (entries) => {
      if (!entries.some((entry) => entry.isIntersecting)) {
        return;
      }

      mapLoadObserver?.disconnect();
      mapLoadObserver = null;
      void startMapInit();
    },
    {
      root: null,
      rootMargin: '250px 0px',
      threshold: 0.01,
    }
  );

  mapLoadObserver.observe(rootEl);
}

onMounted(() => {
  setupDeferredMapLoad();
});

onBeforeUnmount(() => {
  mapLoadObserver?.disconnect();
  mapLoadObserver = null;

  try {
    if (mapInstance?.destroy) {
      mapInstance.destroy();
    }
  } catch (error) {
    console.warn('[MapSection] destroy warning:', error);
  } finally {
    mapInstance = null;
    placemarkInstance = null;
  }
});
</script>

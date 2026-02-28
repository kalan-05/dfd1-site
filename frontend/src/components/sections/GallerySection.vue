<template>
  <section class="section gallery container" :id="$attrs.id || 'gallery'">
    <Swiper
      ref="swiperRef"
      @swiper="onSwiper"
      :modules="swiperModules"
      :loop="isLoopEnabled"
      :loop-prevents-sliding="false"
      :watch-overflow="false"
      :observer="true"
      :observe-parents="true"
      :space-between="16"
      :autoplay="autoplayOptions"
      :navigation="false"
      :breakpoints="breakpoints"
      class="gallery__swiper"
    >
      <SwiperSlide v-for="(image, index) in images" :key="image.id || index">
        <figure
          class="gallery__figure"
          @click="onFigureClick(index)"
          :class="{ 'is-zoomed': isZoomed && currentImage === index }"
        >
          <img
            :src="image.src"
            width="840"
            height="1132"
            :alt="image.alt"
            loading="lazy"
            decoding="async"
            class="gallery__image"
          />
          <figcaption class="gallery__caption gallery__caption--stack">
            <span v-for="(part, i) in splitAlt(image.alt)" :key="i" class="spin">
              {{ part }}
            </span>
          </figcaption>
        </figure>
      </SwiperSlide>
    </Swiper>

    <div v-if="images.length > 1" class="gallery__controls">
      <button type="button" class="gallery__control" @click="slidePrev">
        {{ prevLabel }}
      </button>
      <button type="button" class="gallery__control" @click="slideNext">
        {{ nextLabel }}
      </button>
    </div>

    <div v-if="isZoomed" class="overlay" @click.self="closeZoomedImage">
      <img
        :src="images[currentImage].src"
        width="840"
        height="1132"
        :alt="images[currentImage].alt || 'Изображение'"
        class="zoomed-image"
        @click.stop
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Swiper, SwiperSlide } from 'swiper/vue';
import 'swiper/swiper-bundle.css';
import { Autoplay } from 'swiper/modules';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
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
const prevLabel = computed(() => String(meta.value.prev_label || '').trim() || '← Назад');
const nextLabel = computed(() => String(meta.value.next_label || '').trim() || 'Вперед →');

const images = computed(() => {
  if (!Array.isArray(props.items) || props.items.length === 0) {
    return [];
  }

  const normalized = props.items
    .filter((item) => item?.image_url)
    .map((item, index) => ({
      id: item.id || index + 1,
      src: item.image_url,
      alt: item.alt || item.caption || `Фото ${index + 1}`,
    }));

  return normalized;
});

const splitAlt = (alt) => (alt ? alt.split(',').map((part) => part.trim()).filter(Boolean) : []);

const swiperRef = ref(null);
const swiperInstance = ref(null);
const isZoomed = ref(false);
const currentImage = ref(null);

const autoplayOptions = {
  delay: 8000,
  disableOnInteraction: false,
  pauseOnMouseEnter: true,
};

const swiperModules = [Autoplay];
const breakpoints = {
  0: { slidesPerView: 1 },
  767.98: { slidesPerView: 2 },
  1023.98: { slidesPerView: 3 },
};

const isLoopEnabled = computed(() => images.value.length > 1);

function openZoom(index) {
  isZoomed.value = true;
  currentImage.value = index;
  if (swiperInstance.value?.autoplay) {
    swiperInstance.value.autoplay.stop();
  }
}

function closeZoomedImage() {
  isZoomed.value = false;
  currentImage.value = null;
  if (swiperInstance.value?.autoplay) {
    swiperInstance.value.autoplay.start();
  }
}

function onSwiper(swiper) {
  swiperInstance.value = swiper;
}

function slideBy(delta) {
  const sw = swiperInstance.value;
  const total = images.value.length;

  if (!sw || total <= 1) {
    return;
  }

  if (sw.params.loop) {
    const next = (sw.realIndex + delta + total) % total;
    sw.slideToLoop(next);
    return;
  }

  const next = (sw.activeIndex + delta + total) % total;
  sw.slideTo(next);
}

function slidePrev() {
  slideBy(-1);
}

function slideNext() {
  slideBy(1);
}

function onFigureClick(index) {
  if (!isZoomed.value) {
    openZoom(index);
    return;
  }

  closeZoomedImage();
}

function handleEscClose(event) {
  if (event.key === 'Escape' && isZoomed.value) {
    closeZoomedImage();
  }
}

onMounted(() => window.addEventListener('keydown', handleEscClose));
onUnmounted(() => window.removeEventListener('keydown', handleEscClose));
</script>

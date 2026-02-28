<template>
  <section
    class="reviews-section container"
    :id="$attrs.id || 'reviews'"
    :data-doctor-prefix="doctorPrefix"
    :data-anonymous-label="anonymousLabel"
  >
    <div class="reviews__title">
      <h2>{{ sectionTitle }}</h2>
    </div>

    <div class="reviews-system">
      <div class="reviews-column">
        <div class="reviews-wrapper">
          <article
            v-for="(review, index) in initialReviews"
            :key="`review-${index}`"
            class="review-item"
            :data-index="index"
          >
            <h4 class="review-author">{{ review.author_name }}</h4>
            <div class="review-rating">
              <span
                v-for="star in 5"
                :key="`star-${index}-${star}`"
                class="star"
                :class="star <= Number(review.rating || 5) ? 'filled' : 'empty'"
              >☆</span>
            </div>
            <p v-if="review.doctor_name" class="review-doctor">{{ doctorPrefix }} {{ review.doctor_name }}</p>
            <p class="review-text">{{ review.text }}</p>
          </article>
        </div>

        <div class="reviews-nav">
          <button class="nav-button prev button" :aria-label="prevAriaLabel">{{ prevButtonLabel }}</button>
          <button class="nav-button next button" :aria-label="nextAriaLabel">{{ nextButtonLabel }}</button>
        </div>
      </div>

      <form
        class="review-form"
        id="reviewForm"
        method="POST"
        action="/api/reviews"
        novalidate
        :data-captcha-missing-message="captchaMissingMessage"
        :data-captcha-required-message="captchaRequiredMessage"
        :data-spam-message="spamMessage"
        :data-submitting-label="submittingLabel"
        :data-success-message="successMessage"
        :data-error-message="errorMessage"
        :data-network-error-message="networkErrorMessage"
      >
        <h4 class="form-title">{{ formTitle }}</h4>

        <div class="form-group">
          <label for="userName" class="form-label">{{ nameLabel }}</label>
          <input
            type="text"
            id="userName"
            name="userName"
            class="form-input"
            required
            :placeholder="namePlaceholder"
          />
        </div>

        <div class="form-group">
          <label for="doctorSelect" class="form-label">{{ doctorLabel }}</label>
          <select id="doctorSelect" name="doctorSelect" class="form-input" required>
            <option value="" disabled selected>{{ doctorPlaceholder }}</option>
            <option
              v-for="doctor in doctorOptions"
              :key="doctor.id || doctor.full_name"
              :value="doctor.full_name"
            >
              {{ doctor.full_name }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="reviewRating" class="form-label">{{ ratingLabel }}</label>
          <input
            type="number"
            id="reviewRating"
            name="reviewRating"
            class="form-input"
            min="1"
            max="5"
            required
            :placeholder="ratingPlaceholder"
          />
        </div>

        <div class="form-group">
          <label for="reviewText" class="form-label">{{ reviewTextLabel }}</label>
          <textarea
            id="reviewText"
            name="reviewText"
            class="form-textarea"
            required
            :placeholder="reviewTextPlaceholder"
          ></textarea>
        </div>

        <input type="hidden" name="csrf_token" class="antispam-field" />
        <input type="text" name="antispam" class="antispam-field" style="display:none" />

        <button type="submit" class="form-submit button">{{ submitLabel }}</button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Отзывы',
  },
  doctors: {
    type: Array,
    default: () => [],
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
});

const reviewsInstance = ref(null);
const isComponentActive = ref(true);

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

function readMeta(key, fallback = '') {
  const value = meta.value[key];
  if (typeof value === 'string' && value.trim() !== '') {
    return value.trim();
  }

  return fallback;
}

function readMetaReviews() {
  const raw = meta.value.initial_reviews;

  if (Array.isArray(raw)) {
    return raw;
  }

  if (typeof raw === 'string' && raw.trim() !== '') {
    try {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        return parsed;
      }
    } catch {
      return [];
    }
  }

  return [];
}

const sectionTitle = computed(() => props.title || 'Отзывы');
const doctorPrefix = computed(() => readMeta('doctor_prefix', 'Врач:'));

const prevButtonLabel = computed(() => readMeta('prev_label', '← Назад'));
const nextButtonLabel = computed(() => readMeta('next_label', 'Вперед →'));
const prevAriaLabel = computed(() => readMeta('prev_aria_label', 'Предыдущие отзывы'));
const nextAriaLabel = computed(() => readMeta('next_aria_label', 'Следующие отзывы'));

const formTitle = computed(() => readMeta('form_title', 'Добавить отзыв'));
const nameLabel = computed(() => readMeta('name_label', 'Ваше имя:'));
const namePlaceholder = computed(() => readMeta('name_placeholder', 'Введите ваше имя'));
const doctorLabel = computed(() => readMeta('doctor_label', 'Выберите врача:'));
const doctorPlaceholder = computed(() => readMeta('doctor_placeholder', '-- Выберите врача --'));
const ratingLabel = computed(() => readMeta('rating_label', 'Оценка (1-5):'));
const ratingPlaceholder = computed(() => readMeta('rating_placeholder', 'Оцените от 1 до 5'));
const reviewTextLabel = computed(() => readMeta('review_text_label', 'Текст отзыва:'));
const reviewTextPlaceholder = computed(() => readMeta('review_text_placeholder', 'Напишите ваш отзыв'));
const submitLabel = computed(() => readMeta('submit_label', 'Оставить отзыв'));
const submittingLabel = computed(() => readMeta('submitting_label', 'Отправка...'));
const successMessage = computed(() => readMeta('success_message', 'Спасибо! Отзыв принят на модерацию.'));
const errorMessage = computed(() => readMeta('error_message', 'Ошибка при отправке отзыва'));
const networkErrorMessage = computed(() => readMeta('network_error_message', 'Не удалось отправить отзыв.'));
const spamMessage = computed(() => readMeta('spam_message', 'Обнаружена спам-активность!'));
const captchaMissingMessage = computed(() => readMeta('captcha_missing_message', 'Капча не настроена. Сообщите администратору сайта.'));
const captchaRequiredMessage = computed(() => readMeta('captcha_required_message', 'Подтвердите, что вы не робот.'));
const anonymousLabel = computed(() => readMeta('anonymous_label', 'Аноним'));

const doctorOptions = computed(() => {
  if (Array.isArray(props.doctors) && props.doctors.length > 0) {
    return [...props.doctors]
      .sort((a, b) => (Number(a.sort_order) || 0) - (Number(b.sort_order) || 0))
      .map((doctor) => ({
        id: doctor.id,
        full_name: doctor.full_name,
      }));
  }

  return [];
});

const initialReviews = computed(() => {
  const fromMeta = readMetaReviews();

  if (Array.isArray(fromMeta) && fromMeta.length > 0) {
    return fromMeta
      .filter((item) => item && typeof item === 'object')
      .map((item) => ({
        author_name: String(item.author_name || 'Аноним').trim(),
        doctor_name: String(item.doctor_name || '').trim(),
        rating: Number(item.rating || 5),
        text: String(item.text || '').trim(),
      }));
  }

  return [];
});

onMounted(() => {
  import('@/js/reviews')
    .then(({ default: Reviews }) => {
      if (!isComponentActive.value) {
        return;
      }
      reviewsInstance.value = new Reviews();
    })
    .catch((error) => {
      console.error('Не удалось загрузить модуль отзывов', error);
    });
});

onBeforeUnmount(() => {
  isComponentActive.value = false;
  if (reviewsInstance.value && typeof reviewsInstance.value.destroy === 'function') {
    reviewsInstance.value.destroy();
    reviewsInstance.value = null;
  }
});
</script>

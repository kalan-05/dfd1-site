const DEFAULT_REVIEW_ENDPOINT = '/api/reviews';
const DEFAULT_REVIEWS_LIST_ENDPOINT = '/api/reviews';
const HCAPTCHA_SCRIPT_ID = 'hcaptcha-script';

class Reviews {
  static captchaScriptPromise = null;

  constructor() {
    this.wrapper = document.querySelector('.reviews-wrapper');
    this.section = document.querySelector('.reviews-section');
    this.initialReviews = this.extractCurrentReviews();
    this.text = this.readRuntimeText();
    this.reviews = Array.from(document.querySelectorAll('.review-item'));

    this.itemsPerPage = this.resolveItemsPerPage();
    this.startIndex = 0;
    this.step = 1;

    this.form = document.getElementById('reviewForm');
    this.lastSubmitFailed = false;

    this.prevBtn = null;
    this.nextBtn = null;

    this.navigationHandlersAttached = false;
    this.resizeHandlerAttached = false;
    this.formSubmitHandlerAttached = false;
    this.formResetHandlersAttached = false;
    this.captchaInteractionListenersAttached = false;

    this.reviewEndpoint = this.resolveReviewEndpoint();
    this.reviewsListEndpoint = this.resolveReviewsListEndpoint();
    this.captcha = this.resolveCaptchaConfig();
    this.captchaWidgetId = null;
    this.captchaContainer = null;
    this.captchaInitPromise = null;
    this.captchaInitStarted = false;

    this.handlePrevClick = this.handlePrevClick.bind(this);
    this.handleNextClick = this.handleNextClick.bind(this);
    this.handleFormSubmit = this.handleFormSubmit.bind(this);
    this.handleEscapeKey = this.handleEscapeKey.bind(this);
    this.handleDocumentClick = this.handleDocumentClick.bind(this);
    this.handleFormInteraction = this.handleFormInteraction.bind(this);
    this.handleResize = this.handleResize.bind(this);

    if (this.reviews.length > 0) {
      this.updateReviewVisibility();
      this.initReviewNavigation();
    }

    window.addEventListener('resize', this.handleResize, { passive: true });
    this.resizeHandlerAttached = true;

    if (this.form) {
      this.initFormHandler();
      this.initFormResetOnEscOrOutside();
      this.initCaptchaOnInteraction();
    }

    void this.loadPublishedReviews();
  }

  parseBoolean(value, fallback = false) {
    if (typeof value !== 'string') {
      return fallback;
    }

    const normalized = value.trim().toLowerCase();
    if (['1', 'true', 'yes', 'on'].includes(normalized)) {
      return true;
    }
    if (['0', 'false', 'no', 'off'].includes(normalized)) {
      return false;
    }

    return fallback;
  }

  resolveReviewEndpoint() {
    const fromEnv = String(import.meta.env.VITE_REVIEW_ENDPOINT || '').trim();
    return fromEnv || DEFAULT_REVIEW_ENDPOINT;
  }

  resolveReviewsListEndpoint() {
    const fromEnv = String(import.meta.env.VITE_REVIEWS_LIST_ENDPOINT || '').trim();
    return fromEnv || DEFAULT_REVIEWS_LIST_ENDPOINT;
  }

  resolveCaptchaConfig() {
    const runtimeConfig = window.__REVIEW_CAPTCHA__ || {};
    const runtimeSiteKey = String(runtimeConfig.siteKey || '').trim();
    const envSiteKey = String(import.meta.env.VITE_HCAPTCHA_SITEKEY || '').trim();

    const requiredFromRuntime = typeof runtimeConfig.required === 'boolean'
      ? runtimeConfig.required
      : this.parseBoolean(import.meta.env.VITE_HCAPTCHA_REQUIRED, true);

    return {
      siteKey: runtimeSiteKey || envSiteKey,
      required: requiredFromRuntime,
    };
  }

  refreshCaptchaConfig() {
    this.captcha = this.resolveCaptchaConfig();
    return this.captcha;
  }

  resolveItemsPerPage() {
    if (window.matchMedia('(max-width: 767.98px)').matches) {
      return 2;
    }
    return 3;
  }

  resolveCaptchaSize() {
    if (window.matchMedia('(max-width: 767.98px)').matches) {
      return 'compact';
    }
    return 'normal';
  }

  handleResize() {
    const nextItemsPerPage = this.resolveItemsPerPage();
    if (nextItemsPerPage === this.itemsPerPage) {
      return;
    }

    this.itemsPerPage = nextItemsPerPage;
    this.startIndex = 0;
    this.updateReviewVisibility();
  }

  getFallbackReviews() {
    return Array.isArray(this.initialReviews) ? this.initialReviews : [];
  }

  mergeWithFallbackReviews(sourceReviews, targetCount = 5) {
    if (!Array.isArray(sourceReviews) || sourceReviews.length === 0) {
      return [];
    }

    if (sourceReviews.length >= targetCount) {
      return sourceReviews;
    }

    const makeKey = review => `${String(review.author_name || '').trim()}|${String(review.doctor_name || '').trim()}`.toLowerCase();
    const existingKeys = new Set(sourceReviews.map(makeKey));
    const fallback = this.getFallbackReviews().filter(review => !existingKeys.has(makeKey(review)));

    return [
      ...sourceReviews,
      ...fallback.slice(0, targetCount - sourceReviews.length),
    ];
  }

  initReviewNavigation() {
    this.prevBtn = document.querySelector('.nav-button.prev');
    this.nextBtn = document.querySelector('.nav-button.next');

    if (this.prevBtn) {
      this.prevBtn.addEventListener('click', this.handlePrevClick);
      this.navigationHandlersAttached = true;
    }

    if (this.nextBtn) {
      this.nextBtn.addEventListener('click', this.handleNextClick);
      this.navigationHandlersAttached = true;
    }
  }

  handlePrevClick() {
    this.page(-this.step);
  }

  handleNextClick() {
    this.page(this.step);
  }

  page(direction) {
    const len = this.reviews.length;
    if (len === 0) {
      return;
    }

    this.startIndex = (this.startIndex + direction) % len;
    if (this.startIndex < 0) {
      this.startIndex += len;
    }
    this.updateReviewVisibility();
  }

  updateReviewVisibility() {
    const len = this.reviews.length;
    if (len === 0) {
      return;
    }

    const visible = new Set();
    for (let i = 0; i < this.itemsPerPage; i++) {
      visible.add((this.startIndex + i) % len);
    }
    this.reviews.forEach((item, idx) => {
      item.classList.toggle('is-visible', visible.has(idx));
      item.classList.toggle('is-hidden', !visible.has(idx));
    });
  }

  refreshReviewsList() {
    this.reviews = Array.from(document.querySelectorAll('.review-item'));

    if (this.reviews.length === 0) {
      return;
    }

    if (this.startIndex >= this.reviews.length) {
      this.startIndex = 0;
    }

    this.updateReviewVisibility();
  }

  createReviewMarkup(review) {
    const authorName = this.escapeHTML(String(review.author_name || '').trim() || this.text.anonymousLabel);
    const reviewText = this.escapeHTML(String(review.text || '').trim() || '');
    const doctorName = this.escapeHTML(String(review.doctor_name || '').trim());
    const rating = Math.min(5, Math.max(1, parseInt(review.rating, 10) || 5));

    return `
      <article class="review-item">
        <h4 class="review-author">${authorName}</h4>
        <div class="review-rating">
          ${this.generateRatingStars(rating)}
        </div>
        ${doctorName ? `<p class="review-doctor">${this.text.doctorPrefix} ${doctorName}</p>` : ''}
        <p class="review-text">${reviewText}</p>
      </article>
    `;
  }

  extractCurrentReviews() {
    if (!this.wrapper) {
      return [];
    }

    return Array.from(this.wrapper.querySelectorAll('.review-item'))
      .map((item) => {
        const author = String(item.querySelector('.review-author')?.textContent || '').trim();
        const doctorText = String(item.querySelector('.review-doctor')?.textContent || '').trim();
        const text = String(item.querySelector('.review-text')?.textContent || '').trim();
        const rating = item.querySelectorAll('.review-rating .star.filled').length || 5;
        const doctorPrefix = this.readDatasetValue('doctorPrefix', 'Врач:');
        const doctor = doctorText.startsWith(doctorPrefix)
          ? doctorText.slice(doctorPrefix.length).trim()
          : doctorText.replace(/^Врач:\s*/i, '').trim();

        if (!author && !text) {
          return null;
        }

        return {
          author_name: author,
          doctor_name: doctor,
          rating,
          text,
        };
      })
      .filter(Boolean);
  }

  readDatasetValue(name, fallback = '') {
    const sectionValue = this.section?.dataset?.[name];
    if (typeof sectionValue === 'string' && sectionValue.trim() !== '') {
      return sectionValue.trim();
    }

    const formValue = this.form?.dataset?.[name];
    if (typeof formValue === 'string' && formValue.trim() !== '') {
      return formValue.trim();
    }

    return fallback;
  }

  readRuntimeText() {
    return {
      anonymousLabel: this.readDatasetValue('anonymousLabel', 'Аноним'),
      doctorPrefix: this.readDatasetValue('doctorPrefix', 'Врач:'),
      captchaMissingMessage: this.readDatasetValue('captchaMissingMessage', 'Капча не настроена. Сообщите администратору сайта.'),
      captchaRequiredMessage: this.readDatasetValue('captchaRequiredMessage', 'Подтвердите, что вы не робот.'),
      spamMessage: this.readDatasetValue('spamMessage', 'Обнаружена спам-активность!'),
      submittingLabel: this.readDatasetValue('submittingLabel', 'Отправка...'),
      successMessage: this.readDatasetValue('successMessage', 'Спасибо! Отзыв принят на модерацию.'),
      errorMessage: this.readDatasetValue('errorMessage', 'Ошибка при отправке отзыва'),
      networkErrorMessage: this.readDatasetValue('networkErrorMessage', 'Не удалось отправить отзыв.'),
    };
  }

  async loadPublishedReviews() {
    if (!this.wrapper) {
      return;
    }

    try {
      const response = await fetch(this.reviewsListEndpoint, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });

      if (!response.ok) {
        return;
      }

      const data = await response.json();
      if (!Array.isArray(data) || data.length === 0) {
        return;
      }

      const reviewsToRender = this.mergeWithFallbackReviews(data, 5);
      this.wrapper.innerHTML = reviewsToRender.map(review => this.createReviewMarkup(review)).join('');
      this.startIndex = 0;
      this.refreshReviewsList();
    } catch (error) {
      console.error('Failed to load published reviews:', error);
    }
  }

  initFormHandler() {
    if (!this.form || this.formSubmitHandlerAttached) {
      return;
    }

    this.form.addEventListener('submit', this.handleFormSubmit);
    this.formSubmitHandlerAttached = true;
  }

  initCaptchaOnInteraction() {
    const captcha = this.refreshCaptchaConfig();
    if (!this.form || !captcha.siteKey || this.captchaInteractionListenersAttached) {
      return;
    }

    this.form.addEventListener('focusin', this.handleFormInteraction);
    this.form.addEventListener('pointerdown', this.handleFormInteraction, { passive: true });
    this.form.addEventListener('keydown', this.handleFormInteraction);
    this.captchaInteractionListenersAttached = true;
  }

  removeCaptchaInteractionListeners() {
    if (!this.form || !this.captchaInteractionListenersAttached) {
      return;
    }

    this.form.removeEventListener('focusin', this.handleFormInteraction);
    this.form.removeEventListener('pointerdown', this.handleFormInteraction);
    this.form.removeEventListener('keydown', this.handleFormInteraction);
    this.captchaInteractionListenersAttached = false;
  }

  handleFormInteraction() {
    void this.ensureCaptchaInitialized();
  }

  ensureCaptchaInitialized() {
    const captcha = this.refreshCaptchaConfig();
    if (!this.form || !captcha.siteKey) {
      return Promise.resolve(null);
    }

    if (this.captchaWidgetId !== null) {
      return Promise.resolve(window.hcaptcha || null);
    }

    if (this.captchaInitPromise) {
      return this.captchaInitPromise;
    }

    this.removeCaptchaInteractionListeners();
    this.captchaInitStarted = true;

    this.captchaInitPromise = this.initCaptcha()
      .catch(error => {
        console.error('hCaptcha initialization failed', error);
        return null;
      })
      .finally(() => {
        this.captchaInitStarted = false;
        this.captchaInitPromise = null;
      });

    return this.captchaInitPromise;
  }

  ensureCaptchaContainer() {
    if (!this.form) {
      return null;
    }

    const existingContainer = this.form.querySelector('[data-review-captcha]');
    if (existingContainer) {
      return existingContainer;
    }

    const submitBtn = this.form.querySelector('.form-submit');
    if (!submitBtn || !submitBtn.parentNode) {
      return null;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'form-group';
    wrapper.setAttribute('data-review-captcha', '1');

    submitBtn.parentNode.insertBefore(wrapper, submitBtn);
    return wrapper;
  }

  getCaptchaHiddenInput(name = 'h-captcha-response') {
    if (!this.form) {
      return null;
    }

    let hidden = this.form.querySelector(`input[name="${name}"]`);
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = name;
      this.form.appendChild(hidden);
    }
    return hidden;
  }

  setCaptchaToken(token) {
    const value = token || '';
    const mainHidden = this.getCaptchaHiddenInput('h-captcha-response');
    const legacyHidden = this.getCaptchaHiddenInput('captcha_token');

    if (mainHidden) {
      mainHidden.value = value;
    }
    if (legacyHidden) {
      legacyHidden.value = value;
    }
  }

  getCaptchaToken() {
    if (!window.hcaptcha || typeof window.hcaptcha.getResponse !== 'function') {
      return '';
    }

    try {
      if (this.captchaWidgetId !== null) {
        return window.hcaptcha.getResponse(this.captchaWidgetId) || '';
      }
      return window.hcaptcha.getResponse() || '';
    } catch (error) {
      console.warn('hCaptcha token read failed', error);
      return '';
    }
  }

  static loadCaptchaScript() {
    if (window.hcaptcha && typeof window.hcaptcha.render === 'function') {
      return Promise.resolve(window.hcaptcha);
    }

    if (Reviews.captchaScriptPromise) {
      return Reviews.captchaScriptPromise;
    }

    Reviews.captchaScriptPromise = new Promise((resolve, reject) => {
      const existing = document.getElementById(HCAPTCHA_SCRIPT_ID);
      if (existing) {
        existing.addEventListener('load', () => resolve(window.hcaptcha), { once: true });
        existing.addEventListener('error', reject, { once: true });
        return;
      }

      const script = document.createElement('script');
      script.id = HCAPTCHA_SCRIPT_ID;
      script.src = 'https://js.hcaptcha.com/1/api.js?render=explicit';
      script.async = true;
      script.defer = true;
      script.onload = () => resolve(window.hcaptcha);
      script.onerror = reject;
      document.head.appendChild(script);
    });

    return Reviews.captchaScriptPromise;
  }

  async initCaptcha() {
    const captcha = this.refreshCaptchaConfig();
    if (!this.form || !captcha.siteKey) {
      return;
    }

    this.captchaContainer = this.ensureCaptchaContainer();
    if (!this.captchaContainer) {
      return;
    }

    try {
      const hcaptcha = await Reviews.loadCaptchaScript();
      if (!hcaptcha || typeof hcaptcha.render !== 'function') {
        throw new Error('hCaptcha API is unavailable');
      }

      this.captchaWidgetId = hcaptcha.render(this.captchaContainer, {
        sitekey: captcha.siteKey,
        size: this.resolveCaptchaSize(),
        callback: token => this.setCaptchaToken(token),
        'expired-callback': () => this.setCaptchaToken(''),
        'error-callback': () => this.setCaptchaToken(''),
      });
    } catch (error) {
      console.error('hCaptcha initialization failed', error);
    }
  }

  validateCaptchaBeforeSubmit() {
    const captcha = this.refreshCaptchaConfig();
    if (!captcha.required) {
      return true;
    }

    if (!captcha.siteKey) {
      alert(this.text.captchaMissingMessage);
      return false;
    }

    const token = this.getCaptchaToken();
    if (!token) {
      alert(this.text.captchaRequiredMessage);
      return false;
    }

    this.setCaptchaToken(token);
    return true;
  }

  async handleFormSubmit(event) {
    if (!this.form) {
      return;
    }

    event.preventDefault();

    const antispam = this.form.querySelector('.antispam-field[name="antispam"]');
    if (antispam && antispam.value !== '') {
      alert(this.text.spamMessage);
      this.lastSubmitFailed = true;
      return;
    }

    const captcha = this.refreshCaptchaConfig();
    if (captcha.required && captcha.siteKey) {
      await this.ensureCaptchaInitialized();
    }

    if (!this.validateCaptchaBeforeSubmit()) {
      this.lastSubmitFailed = true;
      return;
    }

    const submitBtn = this.form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn ? submitBtn.textContent : '';
    if (submitBtn) {
      submitBtn.textContent = this.text.submittingLabel;
      submitBtn.disabled = true;
    }

    try {
      const formData = new FormData(this.form);
      formData.append('csrf', this.getCSRFToken());

      const urlAttr = this.form.getAttribute('action');
      const actionUrl = urlAttr && urlAttr.trim() !== '' ? urlAttr : DEFAULT_REVIEW_ENDPOINT;
      const url = this.reviewEndpoint || actionUrl;

      const response = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
        },
        body: formData,
      });

      const contentType = String(response.headers.get('content-type') || '');
      let payload = null;
      let textPayload = '';

      if (contentType.includes('application/json')) {
        payload = await response.json();
      } else {
        textPayload = await response.text();
      }

      const firstError = payload?.errors
        ? Object.values(payload.errors).flat().find(Boolean)
        : '';
      const serverMessage = String(payload?.message || firstError || textPayload || '').trim();

      if (response.ok) {
        alert(serverMessage || this.text.successMessage);
        this.form.reset();
        this.setCaptchaToken('');
        if (window.hcaptcha && typeof window.hcaptcha.reset === 'function' && this.captchaWidgetId !== null) {
          window.hcaptcha.reset(this.captchaWidgetId);
        }
        this.lastSubmitFailed = false;
      } else {
        alert(serverMessage || this.text.errorMessage);
        this.lastSubmitFailed = true;
      }
    } catch (error) {
      console.error('Review submit failed:', error);

      const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
      const hint = isLocal && this.reviewEndpoint.startsWith('/php')
        ? ' Проверьте, что локальный PHP-сервер запущен: php -S localhost:5174 -t public'
        : '';

      alert(`${this.text.networkErrorMessage}${hint}`);
      this.lastSubmitFailed = true;
    } finally {
      if (submitBtn) {
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
      }
    }
  }

  initFormResetOnEscOrOutside() {
    if (!this.form || this.formResetHandlersAttached) {
      return;
    }

    document.addEventListener('keydown', this.handleEscapeKey);
    document.addEventListener('click', this.handleDocumentClick);
    this.formResetHandlersAttached = true;
  }

  handleEscapeKey(event) {
    if (event.key !== 'Escape' || !this.lastSubmitFailed || !this.form) {
      return;
    }

    this.form.reset();
    this.setCaptchaToken('');
    this.lastSubmitFailed = false;
  }

  handleDocumentClick(event) {
    if (!this.lastSubmitFailed || !this.form) {
      return;
    }

    const isInsideForm = this.form.contains(event.target);
    if (!isInsideForm) {
      this.form.reset();
      this.setCaptchaToken('');
      this.lastSubmitFailed = false;
    }
  }

  generateRatingStars(rating) {
    return Array(5)
      .fill('☆')
      .map((star, index) => `<span class="star ${index < rating ? 'filled' : 'empty'}">${star}</span>`)
      .join('');
  }

  escapeHTML(str) {
    return str.replace(/[&<>"']/g, tag => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    }[tag] || tag));
  }

  getCSRFToken() {
    const tokenMatch = document.cookie.match(/csrf_token=([^;]+)/);
    let token = tokenMatch ? tokenMatch[1] : '';
    if (!token) {
      token = Math.random().toString(36).slice(2) + Math.random().toString(36).slice(2);
      const date = new Date();
      date.setTime(date.getTime() + 60 * 60 * 1000);
      document.cookie = `csrf_token=${token};expires=${date.toUTCString()};path=/`;
    }
    return token;
  }

  destroy() {
    this.removeCaptchaInteractionListeners();

    if (this.resizeHandlerAttached) {
      window.removeEventListener('resize', this.handleResize);
      this.resizeHandlerAttached = false;
    }

    if (this.navigationHandlersAttached) {
      if (this.prevBtn) {
        this.prevBtn.removeEventListener('click', this.handlePrevClick);
      }
      if (this.nextBtn) {
        this.nextBtn.removeEventListener('click', this.handleNextClick);
      }
      this.navigationHandlersAttached = false;
    }

    if (this.formSubmitHandlerAttached && this.form) {
      this.form.removeEventListener('submit', this.handleFormSubmit);
      this.formSubmitHandlerAttached = false;
    }

    if (this.formResetHandlersAttached) {
      document.removeEventListener('keydown', this.handleEscapeKey);
      document.removeEventListener('click', this.handleDocumentClick);
      this.formResetHandlersAttached = false;
    }

    this.prevBtn = null;
    this.nextBtn = null;
    this.captchaContainer = null;
    this.captchaWidgetId = null;
    this.captchaInitPromise = null;
    this.captchaInitStarted = false;
    this.form = null;
    this.section = null;
    this.wrapper = null;
    this.reviews = [];
  }
}

export default Reviews;


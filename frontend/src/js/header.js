// src/js/header.js
// Контроллер шапки сайта. Управляет состоянием мобильного меню,
// обработкой кликов по пунктам навигации и закрытием меню при
// взаимодействии пользователя с внешними областями.

class Header {
  // Селекторы для поиска элементов в DOM
  selectors = {
    root: '[data-js-header]',                       // Корневой элемент шапки
    overlay: '[data-js-header-overlay]',            // Оверлей для меню (подложка + контейнер)
    burgerButton: '[data-js-header-burger-button]', // Кнопка бургер
    menu: '.header__menu',                          // Меню внутри оверлея
    menuLinks: '.header__menu-link',                // Пункты меню (ссылки)
  };

  // Классы для управления состоянием
  stateClasses = {
    isActive: 'is-active', // Класс "открыто/активно"
    isLock: 'is-lock',     // Класс "запрет прокрутки" на <html>
  };

  // [FIX] Флаг защиты от двойной инициализации
  #inited = false;

  constructor() {
    // Ищем корневой элемент
    this.rootElement = document.querySelector(this.selectors.root);

    // [FIX] Защитная проверка: если шапка не найдена — выходим без падений
    if (!this.rootElement) {
      console.error('Header: не найден корневой элемент [data-js-header]');
      return;
    }

    // Ищем основные элементы внутри шапки
    this.overlayElement = this.rootElement.querySelector(this.selectors.overlay) || null;
    this.burgerButtonElement = this.rootElement.querySelector(this.selectors.burgerButton) || null;

    // [FIX] Безопасное получение меню:
    // если есть overlay — ищем меню в нём; иначе ищем в корне шапки.
    this.menuElement = (this.overlayElement
      ? this.overlayElement.querySelector(this.selectors.menu)
      : this.rootElement.querySelector(this.selectors.menu)) || null;

    // Ссылки меню
    this.menuLinks = this.rootElement.querySelectorAll(this.selectors.menuLinks);

    // [FIX] Проверка доступности критичных узлов до привязки обработчиков
    if (!this.overlayElement || !this.burgerButtonElement || !this.menuElement) {
      console.error('Header: не найдены необходимые элементы (overlay/burger/menu). Проверьте разметку.');
      return;
    }

    // [FIX] ARIA для доступности (не меняет текущую логику)
    if (!this.menuElement.id) {
      // Назначаем id, чтобы aria-controls ссылался на конкретный контейнер
      this.menuElement.id = 'header-menu';
    }
    this.burgerButtonElement.setAttribute('aria-controls', this.menuElement.id);
    this.burgerButtonElement.setAttribute('aria-expanded', 'false');
    this.overlayElement.setAttribute('aria-hidden', 'true');

    // Привязка обработчиков
    this.bindEvents();

    // [FIX] Фиксация инициализации
    this.#inited = true;
  }

  // === ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ СОСТОЯНИЯ ===

  // [FIX] Единый метод закрытия (используется в нескольких местах)
  closeMenu = () => {
    this.burgerButtonElement.classList.remove(this.stateClasses.isActive);
    this.overlayElement.classList.remove(this.stateClasses.isActive);
    document.documentElement.classList.remove(this.stateClasses.isLock);

    // ARIA-состояния
    this.burgerButtonElement.setAttribute('aria-expanded', 'false');
    this.overlayElement.setAttribute('aria-hidden', 'true');
  };

  // Метод для переключения состояния меню (открыть/закрыть)
  toggleMenu = () => {
    const willBeActive = !this.overlayElement.classList.contains(this.stateClasses.isActive);

    this.burgerButtonElement.classList.toggle(this.stateClasses.isActive, willBeActive);
    this.overlayElement.classList.toggle(this.stateClasses.isActive, willBeActive);
    document.documentElement.classList.toggle(this.stateClasses.isLock, willBeActive);

    // ARIA-состояния
    this.burgerButtonElement.setAttribute('aria-expanded', String(willBeActive));
    this.overlayElement.setAttribute('aria-hidden', String(!willBeActive));
  };

  // === ОБРАБОТЧИКИ ===

  // Обработчик клика по кнопке бургер
  onBurgerButtonClick = (event) => {
    event.stopPropagation(); // Останавливаем всплытие, чтобы глобальный клик не сработал
    this.toggleMenu();
  };

  // Обработчик клика по пункту меню
  onMenuLinkClick = (event) => {
    const linkElement = event.currentTarget; // Текущая ссылка (надёжнее, чем event.target для вложенных узлов)

    // Закрываем меню (сразу, до навигации)
    this.closeMenu();

    // Плавный скролл к якорю внутри текущей страницы
    const rawHref = linkElement.getAttribute('href');
    if (rawHref && rawHref.startsWith('#')) {
      const targetElement = document.querySelector(rawHref);
      if (targetElement) {
        event.preventDefault(); // Отключаем стандартный переход по якорю
        targetElement.scrollIntoView({ behavior: 'smooth' });
      }
    }
  };

  // Метод для закрытия меню при клике вне его области (по документу)
  closeMenuIfClickedOutside = (event) => {
    // [FIX] Закрытие по клику на подложку (overlay backdrop)
    if (event.target === this.overlayElement) {
      this.closeMenu();
      return;
    }

    const isClickInsideMenu = this.menuElement.contains(event.target);           // Клик внутри меню?
    const isClickInsideBurger = this.burgerButtonElement.contains(event.target); // Клик по кнопке?

    // Если клик был вне меню и вне кнопки — закрываем
    if (!isClickInsideMenu && !isClickInsideBurger) {
      this.closeMenu();
    }
  };

  // [FIX] Закрытие по клавише Escape (улучшает UX, не меняя логику навигации)
  onKeydown = (event) => {
    if (event.key === 'Escape' || event.key === 'Esc') {
      this.closeMenu();
      // Возвращаем фокус на бургер (доступность)
      this.burgerButtonElement.focus?.();
    }
  };

  // === ПОДПИСКА НА СОБЫТИЯ ===
  bindEvents() {
    if (this.#inited) return; // [FIX] защита от повторной инициализации

    // Клик по кнопке бургер
    this.burgerButtonElement.addEventListener('click', this.onBurgerButtonClick, { passive: false });

    // Клики по пунктам меню
    this.menuLinks.forEach((link) => {
      link.addEventListener('click', this.onMenuLinkClick, { passive: false });
    });

    // Глобальные обработчики:
    // 1) Клик по документу — для закрытия при клике "вне"
    document.addEventListener('click', this.closeMenuIfClickedOutside, { passive: true });

    // 2) Клавиатура — закрытие по Esc
    document.addEventListener('keydown', this.onKeydown, { passive: true });
  }

  // === ОЧИСТКА (для HMR и потенциального destroy) ===
  // [FIX] Метод очистки: снимаем обработчики и сбрасываем ссылки на DOM-элементы
  destroy() {
    if (!this.rootElement) {
      return;
    }

    if (this.burgerButtonElement) {
      this.burgerButtonElement.removeEventListener('click', this.onBurgerButtonClick);
      this.burgerButtonElement.classList.remove(this.stateClasses.isActive);
      this.burgerButtonElement.setAttribute('aria-expanded', 'false');
    }

    if (this.menuLinks && this.menuLinks.length > 0) {
      this.menuLinks.forEach((link) => {
        link.removeEventListener('click', this.onMenuLinkClick);
      });
    }

    if (this.overlayElement) {
      this.overlayElement.classList.remove(this.stateClasses.isActive);
      this.overlayElement.setAttribute('aria-hidden', 'true');
    }

    document.removeEventListener('click', this.closeMenuIfClickedOutside);
    document.removeEventListener('keydown', this.onKeydown);
    document.documentElement.classList.remove(this.stateClasses.isLock);

    // Обнуляем ссылки
    this.menuLinks = null;
    this.menuElement = null;
    this.burgerButtonElement = null;
    this.overlayElement = null;
    this.rootElement = null;

    this.#inited = false;
  }
}

export default Header;


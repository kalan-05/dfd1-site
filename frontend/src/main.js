// src/main.js

// [NO-CHANGE] Базовые импорты приложения
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createHead } from '@unhead/vue/client'
import './scss/main.scss'
import Header from './js/header'

// [NO-CHANGE] Создаём приложение и подключаем плагины
const app = createApp(App)
app.use(router)
app.use(createHead())

// [NO-CHANGE] Монтируем приложение
app.mount('#app')

/**
 * =======================================================================================
 *  ИНИЦИАЛИЗАЦИЯ HEADER
 *  ----------------------------------------------------------------------------
 *  [FIX] Удалён второй (дублирующий) запуск new Header() через отдельный rAF-блок,
 *  чтобы экземпляр был ровно один. Тайминг запуска сохранён: initHeader использует
 *  requestAnimationFrame, как и исходный код, — логика и поведение не меняются.
 * =======================================================================================
 */

// [HMR] Аккуратная система управления экземпляром Header
let headerInstance = null
let headerInitFrameId = null

// [HMR] Снятие обработчиков и очистка экземпляра
const destroyHeader = () => {
  if (headerInstance) {
    try {
      headerInstance.destroy()
    } catch (e) {
      console.warn('Header.destroy() завершился с предупреждением:', e)
    }
    headerInstance = null
  }
}

// [HMR] Инициализация с защитой от гонок анимационного кадра и сохранённым таймингом
const initHeader = () => {
  if (headerInitFrameId !== null) {
    cancelAnimationFrame(headerInitFrameId)
    headerInitFrameId = null
  }

  destroyHeader()

  // ВАЖНО: rAF сохраняет исходный тайминг "после рендера", как было у тебя.
  headerInitFrameId = requestAnimationFrame(() => {
    headerInitFrameId = null
    try {
      headerInstance = new Header()
    } catch (e) {
      console.error('Инициализация Header завершилась ошибкой:', e)
    }
  })
}

// Первичная инициализация
initHeader()

// Чистая утилизация при горячей перезагрузке модулей Vite (dev-режим)
if (import.meta.hot) {
  import.meta.hot.dispose(() => {
    if (headerInitFrameId !== null) {
      cancelAnimationFrame(headerInitFrameId)
      headerInitFrameId = null
    }

    destroyHeader()

    // Корректно размонтируем Vue-приложение
    try {
      app.unmount()
    } catch (e) {
      console.warn('app.unmount() завершился с предупреждением:', e)
    }
  })
}

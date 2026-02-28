// src/plugins/yandexMapsLoader.js
// Loads Yandex Maps JS API once and shares the same Promise between callers.

let ymapsPromise = null

function buildYmapsUrl(apiKey) {
  const params = new URLSearchParams({ lang: 'ru_RU' })
  if (apiKey) {
    params.set('apikey', apiKey)
  }

  return `https://api-maps.yandex.ru/2.1/?${params.toString()}`
}

export function loadYandexMaps() {
  if (typeof window !== 'undefined' && window.ymaps?.Map) {
    return Promise.resolve(window.ymaps)
  }

  if (ymapsPromise) {
    return ymapsPromise
  }

  ymapsPromise = new Promise((resolve, reject) => {
    const apiKey = (import.meta.env.VITE_YMAPS_API_KEY || '').trim()
    if (!apiKey) {
      console.warn('[yandexMapsLoader] VITE_YMAPS_API_KEY is missing. Fallback may be used.')
    }

    const fail = (error) => {
      ymapsPromise = null
      reject(error)
    }

    const existingScript = document.getElementById('ymaps-script')
    if (existingScript) {
      if (window.ymaps?.ready) {
        window.ymaps.ready(() => resolve(window.ymaps))
        return
      }

      const onLoad = () => {
        cleanup()
        if (!window.ymaps?.ready) {
          fail(new Error('ymaps not found on window after script load'))
          return
        }

        window.ymaps.ready(() => resolve(window.ymaps))
      }

      const onError = () => {
        cleanup()
        fail(new Error('Failed to load existing Yandex Maps script'))
      }

      const cleanup = () => {
        existingScript.removeEventListener('load', onLoad)
        existingScript.removeEventListener('error', onError)
      }

      existingScript.addEventListener('load', onLoad)
      existingScript.addEventListener('error', onError)
      return
    }

    const script = document.createElement('script')
    script.id = 'ymaps-script'
    script.src = buildYmapsUrl(apiKey)
    script.async = true

    script.onload = () => {
      if (!window.ymaps?.ready) {
        fail(new Error('ymaps not found on window'))
        return
      }

      window.ymaps.ready(() => resolve(window.ymaps))
    }

    script.onerror = () => fail(new Error('Failed to load Yandex Maps script'))
    document.head.appendChild(script)
  })

  return ymapsPromise
}

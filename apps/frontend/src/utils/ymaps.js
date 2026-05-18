import api from '@/api/client.js'

let readyPromise = null

export function loadYmaps() {
  if (readyPromise) return readyPromise

  readyPromise = (async () => {
    let key = ''
    try {
      const { data } = await api.get('/api/settings')
      key = data.yandex_maps_key ?? ''
    } catch {}

    await new Promise((resolve, reject) => {
      if (window.ymaps) { resolve(); return }
      if (document.querySelector('script[data-ymaps2]')) {
        const wait = () => (window.ymaps ? resolve() : setTimeout(wait, 50))
        wait(); return
      }
      const s = document.createElement('script')
      s.dataset.ymaps2 = '1'
      s.src = `https://api-maps.yandex.ru/2.1/?apikey=${key}&lang=ru_RU`
      s.onload = resolve
      s.onerror = () => reject(new Error('Yandex Maps API load failed'))
      document.head.appendChild(s)
    })

    await new Promise(resolve => window.ymaps.ready(resolve))
  })()

  return readyPromise
}

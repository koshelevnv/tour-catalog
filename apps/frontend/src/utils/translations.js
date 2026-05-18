import api from '@/api/client.js'
import { t } from '@/i18n.js'

function setNested(obj, dotKey, value) {
  const parts = dotKey.split('.')
  let cur = obj
  for (let i = 0; i < parts.length - 1; i++) {
    if (!cur[parts[i]] || typeof cur[parts[i]] !== 'object') return
    cur = cur[parts[i]]
  }
  const last = parts[parts.length - 1]
  if (typeof cur[last] === 'string') cur[last] = value
}

export async function loadTranslations() {
  try {
    const { data } = await api.get('/api/translations')
    for (const [key, value] of Object.entries(data)) {
      if (typeof value === 'string') setNested(t, key, value)
    }
  } catch {}
}

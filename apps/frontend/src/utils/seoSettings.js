import { reactive } from 'vue'
import api from '@/api/client.js'

export const seo = reactive({
  meta_title: 'Каталог туров',
  meta_description: '',
  og_image: '',
})

export async function loadSeoSettings() {
  try {
    const { data } = await api.get('/api/settings')
    if (data.meta_title)       seo.meta_title       = data.meta_title
    if (data.meta_description) seo.meta_description = data.meta_description
    if (data.og_image)         seo.og_image         = data.og_image
  } catch {}
}

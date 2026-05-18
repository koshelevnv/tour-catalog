<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  getSettings, updateSettings, uploadOgImage,
  getTranslations, saveTranslations,
  getTourTypes, createTourType, updateTourType, deleteTourType,
} from '@/api/admin.js'
import { t, defaultTranslations } from '@/i18n.js'

const s = t.admin.settings

// ─── Tabs ─────────────────────────────────────────────────────────────────────

const TABS = [
  { id: 'api',     label: 'Интеграции' },
  { id: 'prompt',  label: 'Системный промпт' },
  { id: 'site',    label: 'Сайт' },
  { id: 'types',   label: 'Типы туров' },
  { id: 'account', label: 'Аккаунт' },
  { id: 'texts',   label: 'Тексты интерфейса' },
]
const activeTab = ref('api')

// ─── Main settings form ────────────────────────────────────────────────────────

const form = ref({
  yandex_maps_key: '',
  anthropic_api_key: '',
  llm_provider: 'anthropic',
  openrouter_api_key: '',
  openrouter_model: '',
  llm_system_prompt: '',
  account_email: '',
  account_password: '',
  meta_title: '',
  meta_description: '',
  og_image: '',
  home_per_page: 12,
  home_load_mode: 'infinite',
  catalog_per_page: 12,
  catalog_load_mode: 'pagination',
  search_per_page: 12,
  search_load_mode: 'pagination',
})

const loading    = ref(true)
const saving     = ref(false)
const saved      = ref(false)
const saveError  = ref('')
const isOpenRouter = computed(() => form.value.llm_provider === 'openrouter')

const showAnthropicKey   = ref(false)
const showOpenrouterKey  = ref(false)

// ─── OG image upload ───────────────────────────────────────────────────────────

const ogUploading  = ref(false)
const ogUploadError = ref('')
const ogFileInput  = ref(null)

async function handleOgUpload(e) {
  const file = e.target.files?.[0]
  if (!file) return
  ogUploading.value  = true
  ogUploadError.value = ''
  try {
    const { data } = await uploadOgImage(file)
    form.value.og_image = data.url
  } catch {
    ogUploadError.value = 'Ошибка загрузки изображения'
  } finally {
    ogUploading.value = false
    if (ogFileInput.value) ogFileInput.value.value = ''
  }
}

function clearOgImage() {
  form.value.og_image = ''
}

async function handleSave() {
  saving.value = true
  saved.value  = false
  saveError.value = ''
  try {
    const payload = { ...form.value }
    if (!payload.account_password) delete payload.account_password
    const { data } = await updateSettings(payload)
    form.value.account_email    = data.account_email ?? form.value.account_email
    form.value.account_password = ''
    saved.value = true
    setTimeout(() => { saved.value = false }, 3000)
  } catch {
    saveError.value = s.error
  } finally {
    saving.value = false
  }
}

// ─── Tour types CRUD ──────────────────────────────────────────────────────────

const types       = ref([])
const typesLoading = ref(false)
const typesError  = ref('')

const newName    = ref('')
const newIcon    = ref('')
const creating   = ref(false)
const createError = ref('')

const editId     = ref(null)
const editName   = ref('')
const editIcon   = ref('')
const typeSaving = ref(false)

async function loadTypes() {
  typesLoading.value = true
  typesError.value = ''
  try {
    const { data } = await getTourTypes()
    types.value = data.data ?? data
  } catch {
    typesError.value = 'Не удалось загрузить типы туров'
  } finally {
    typesLoading.value = false
  }
}

async function createType() {
  createError.value = ''
  if (!newName.value.trim()) return
  creating.value = true
  try {
    const { data } = await createTourType({ name: newName.value.trim(), icon: newIcon.value.trim() || null })
    types.value.push(data)
    newName.value = ''
    newIcon.value = ''
  } catch (e) {
    createError.value = e.response?.data?.message ?? 'Ошибка при создании'
  } finally {
    creating.value = false
  }
}

function startEdit(type) {
  editId.value   = type.id
  editName.value = type.name
  editIcon.value = type.icon ?? ''
}

function cancelEdit() {
  editId.value   = null
  editName.value = ''
  editIcon.value = ''
}

async function saveEdit(type) {
  if (!editName.value.trim()) return
  typeSaving.value = true
  try {
    const { data } = await updateTourType(type.id, { name: editName.value.trim(), icon: editIcon.value.trim() || null })
    const idx = types.value.findIndex(t => t.id === type.id)
    if (idx !== -1) types.value[idx] = data
    cancelEdit()
  } catch (e) {
    typesError.value = e.response?.data?.message ?? 'Ошибка при сохранении'
  } finally {
    typeSaving.value = false
  }
}

async function removeType(type) {
  if (!confirm(`Удалить тип «${type.name}»?`)) return
  try {
    await deleteTourType(type.id)
    types.value = types.value.filter(t => t.id !== type.id)
  } catch (e) {
    typesError.value = e.response?.data?.message ?? 'Ошибка при удалении'
  }
}

const fallbackIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>`

// ─── Translations editor ───────────────────────────────────────────────────────

const SECTION_LABELS = {
  nav:         'Навигация',
  breadcrumbs: 'Хлебные крошки',
  search:      'Поиск',
  catalog:     'Каталог',
  tour:        'Страница тура',
  map:         'Карта маршрута',
  admin:       'Панель администратора',
}

const allGroups = (() => {
  const groups = {}
  for (const [key, def] of Object.entries(defaultTranslations)) {
    const section = key.split('.')[0]
    if (!groups[section]) groups[section] = []
    groups[section].push({ key, def })
  }
  return groups
})()

const overrides  = ref({})
const trFilter   = ref('')
const trSaving   = ref(false)
const trSaved    = ref(false)
const trError    = ref('')
const collapsedSections = ref(new Set(Object.keys(allGroups)))

const filteredGroups = computed(() => {
  const q = trFilter.value.trim().toLowerCase()
  if (!q) return allGroups
  const result = {}
  for (const [section, items] of Object.entries(allGroups)) {
    const filtered = items.filter(({ key, def }) =>
      key.toLowerCase().includes(q) ||
      def.toLowerCase().includes(q) ||
      (overrides.value[key] ?? '').toLowerCase().includes(q)
    )
    if (filtered.length) result[section] = filtered
  }
  return result
})

function toggleSection(section) {
  const set = collapsedSections.value
  if (set.has(section)) set.delete(section)
  else set.add(section)
  collapsedSections.value = new Set(set)
}

async function handleSaveTranslations() {
  trSaving.value = true
  trSaved.value  = false
  trError.value  = ''
  try {
    const { data } = await saveTranslations(overrides.value)
    overrides.value = data ?? {}
    trSaved.value = true
    setTimeout(() => { trSaved.value = false }, 3000)
  } catch {
    trError.value = 'Ошибка при сохранении текстов'
  } finally {
    trSaving.value = false
  }
}

// ─── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(async () => {
  const [settingsRes, trRes] = await Promise.allSettled([getSettings(), getTranslations()])
  if (settingsRes.status === 'fulfilled') {
    const data = settingsRes.value.data
    Object.keys(form.value).forEach((k) => {
      if (k !== 'account_password' && data[k] != null) form.value[k] = data[k]
    })
    if (!form.value.llm_provider) form.value.llm_provider = 'anthropic'
  }
  if (trRes.status === 'fulfilled') overrides.value = trRes.value.data ?? {}
  loading.value = false
  loadTypes()
})

// ─── Helpers ───────────────────────────────────────────────────────────────────

const inputClass = 'w-full h-9 px-3 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring'
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">{{ s.title }}</h1>

    <div v-if="loading" class="text-muted-foreground text-sm py-8">Загрузка…</div>

    <template v-else>

      <!-- Tab bar -->
      <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 mb-8">
        <div class="flex gap-1 border-b border-border min-w-max sm:min-w-0">
          <button
            v-for="tab in TABS"
            :key="tab.id"
            type="button"
            @click="activeTab = tab.id"
            class="px-3 sm:px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px shrink-0 whitespace-nowrap"
            :class="activeTab === tab.id
              ? 'border-primary text-primary'
              : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- ── Tab: Интеграции ─────────────────────────────────────────────────── -->
      <form v-if="activeTab === 'api'" @submit.prevent="handleSave" class="space-y-6 max-w-2xl">

        <!-- Yandex Maps -->
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-4">{{ s.mapsKey.title }}</h2>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.mapsKey.label }}</label>
            <input v-model="form.yandex_maps_key" type="text" :placeholder="s.mapsKey.placeholder" :class="inputClass" />
          </div>
        </section>

        <!-- LLM / AI -->
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-4">{{ s.llm.title }}</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">{{ s.llm.provider }}</label>
              <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input v-model="form.llm_provider" type="radio" value="anthropic" class="accent-primary" />
                  {{ s.llm.providerAnthropic }}
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                  <input v-model="form.llm_provider" type="radio" value="openrouter" class="accent-primary" />
                  {{ s.llm.providerOpenRouter }}
                </label>
              </div>
            </div>

            <div v-if="!isOpenRouter">
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.llm.anthropicKey }}</label>
              <div class="relative">
                <input
                  v-model="form.anthropic_api_key"
                  :type="showAnthropicKey ? 'text' : 'password'"
                  :placeholder="s.llm.anthropicKeyPlaceholder"
                  :class="inputClass"
                  class="pr-10"
                  autocomplete="off"
                />
                <button
                  type="button"
                  @click="showAnthropicKey = !showAnthropicKey"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  :title="showAnthropicKey ? 'Скрыть' : 'Показать'"
                >
                  <svg v-if="showAnthropicKey" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
            </div>

            <template v-if="isOpenRouter">
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.llm.openrouterKey }}</label>
                <div class="relative">
                  <input
                    v-model="form.openrouter_api_key"
                    :type="showOpenrouterKey ? 'text' : 'password'"
                    :placeholder="s.llm.openrouterKeyPlaceholder"
                    :class="inputClass"
                    class="pr-10"
                    autocomplete="off"
                  />
                  <button
                    type="button"
                    @click="showOpenrouterKey = !showOpenrouterKey"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                  >
                    <svg v-if="showOpenrouterKey" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.llm.openrouterModel }}</label>
                <input v-model="form.openrouter_model" type="text" :placeholder="s.llm.openrouterModelPlaceholder" :class="inputClass" />
                <p class="text-xs text-muted-foreground mt-1">{{ s.llm.openrouterModelHint }}</p>
              </div>
            </template>
          </div>
        </section>

        <div class="flex items-center gap-3">
          <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            {{ saving ? s.saving : s.save }}
          </button>
          <span v-if="saved" class="text-sm text-green-600">{{ s.saved }}</span>
          <span v-if="saveError" class="text-sm text-red-500">{{ saveError }}</span>
        </div>
      </form>

      <!-- ── Tab: Системный промпт ─────────────────────────────────────────── -->
      <form v-else-if="activeTab === 'prompt'" @submit.prevent="handleSave" class="space-y-6 max-w-2xl">
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-1">{{ s.systemPrompt.title }}</h2>
          <p class="text-sm text-muted-foreground mb-4">{{ s.systemPrompt.hint }}</p>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.systemPrompt.label }}</label>
            <textarea
              v-model="form.llm_system_prompt"
              rows="12"
              maxlength="5000"
              :placeholder="s.systemPrompt.placeholder"
              class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-y font-mono"
            />
            <p class="text-xs text-muted-foreground mt-1 text-right">{{ s.systemPrompt.charCount(form.llm_system_prompt.length) }}</p>
          </div>
        </section>
        <div class="flex items-center gap-3">
          <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            {{ saving ? s.saving : s.save }}
          </button>
          <span v-if="saved" class="text-sm text-green-600">{{ s.saved }}</span>
          <span v-if="saveError" class="text-sm text-red-500">{{ saveError }}</span>
        </div>
      </form>

      <!-- ── Tab: Сайт ───────────────────────────────────────────────────────── -->
      <form v-else-if="activeTab === 'site'" @submit.prevent="handleSave" class="space-y-6 max-w-2xl">

        <!-- SEO -->
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-4">{{ s.seo.title }}</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.seo.metaTitle }}</label>
              <input v-model="form.meta_title" type="text" :class="inputClass" placeholder="Каталог туров" />
              <p class="text-xs text-muted-foreground mt-1">{{ s.seo.metaTitleHint }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.seo.metaDescription }}</label>
              <textarea v-model="form.meta_description" rows="3" maxlength="500" class="w-full px-3 py-2 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring resize-none" />
              <p class="text-xs text-muted-foreground mt-1">{{ s.seo.metaDescriptionHint }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.seo.ogImage }}</label>
              <div class="space-y-2">
                <!-- Preview -->
                <div v-if="form.og_image" class="relative w-full max-w-xs rounded-lg overflow-hidden border border-border bg-muted">
                  <img :src="form.og_image" alt="OG preview" class="w-full h-auto object-cover" style="aspect-ratio:1200/630" />
                  <button
                    type="button"
                    @click="clearOgImage"
                    class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-colors"
                    title="Удалить"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
                <!-- Upload button -->
                <div class="flex items-center gap-3">
                  <input ref="ogFileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleOgUpload" />
                  <button
                    type="button"
                    :disabled="ogUploading"
                    @click="ogFileInput.click()"
                    class="px-4 py-2 text-sm border border-border rounded-md hover:bg-muted transition-colors disabled:opacity-50"
                  >
                    {{ ogUploading ? 'Загрузка…' : (form.og_image ? 'Заменить' : 'Загрузить изображение') }}
                  </button>
                  <span v-if="ogUploadError" class="text-xs text-red-500">{{ ogUploadError }}</span>
                </div>
                <p class="text-xs text-muted-foreground">{{ s.seo.ogImageHint }}</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Display settings -->
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-5">{{ s.display.title }}</h2>
          <div class="space-y-6">
            <div>
              <h3 class="text-sm font-medium text-foreground mb-3">{{ s.display.home }}</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm text-muted-foreground mb-1">{{ s.display.perPage }}</label>
                  <input v-model.number="form.home_per_page" type="number" min="1" max="100" :class="inputClass" class="w-32" />
                </div>
                <div>
                  <label class="block text-sm text-muted-foreground mb-2">{{ s.display.loadMode }}</label>
                  <div class="space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.home_load_mode" type="radio" value="infinite" class="accent-primary" />
                      {{ s.display.modeInfinite }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.home_load_mode" type="radio" value="load_more" class="accent-primary" />
                      {{ s.display.modeLoadMore }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.home_load_mode" type="radio" value="pagination" class="accent-primary" />
                      {{ s.display.modePagination }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <hr class="border-border" />
            <div>
              <h3 class="text-sm font-medium text-foreground mb-3">{{ s.display.catalog }}</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm text-muted-foreground mb-1">{{ s.display.perPage }}</label>
                  <input v-model.number="form.catalog_per_page" type="number" min="1" max="100" :class="inputClass" class="w-32" />
                </div>
                <div>
                  <label class="block text-sm text-muted-foreground mb-2">{{ s.display.loadMode }}</label>
                  <div class="space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.catalog_load_mode" type="radio" value="infinite" class="accent-primary" />
                      {{ s.display.modeInfinite }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.catalog_load_mode" type="radio" value="load_more" class="accent-primary" />
                      {{ s.display.modeLoadMore }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.catalog_load_mode" type="radio" value="pagination" class="accent-primary" />
                      {{ s.display.modePagination }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <hr class="border-border" />
            <div>
              <h3 class="text-sm font-medium text-foreground mb-3">{{ s.display.search }}</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm text-muted-foreground mb-1">{{ s.display.perPage }}</label>
                  <input v-model.number="form.search_per_page" type="number" min="1" max="100" :class="inputClass" class="w-32" />
                </div>
                <div>
                  <label class="block text-sm text-muted-foreground mb-2">{{ s.display.loadMode }}</label>
                  <div class="space-y-1.5">
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.search_load_mode" type="radio" value="infinite" class="accent-primary" />
                      {{ s.display.modeInfinite }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.search_load_mode" type="radio" value="load_more" class="accent-primary" />
                      {{ s.display.modeLoadMore }}
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                      <input v-model="form.search_load_mode" type="radio" value="pagination" class="accent-primary" />
                      {{ s.display.modePagination }}
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <div class="flex items-center gap-3">
          <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            {{ saving ? s.saving : s.save }}
          </button>
          <span v-if="saved" class="text-sm text-green-600">{{ s.saved }}</span>
          <span v-if="saveError" class="text-sm text-red-500">{{ saveError }}</span>
        </div>
      </form>

      <!-- ── Tab: Типы туров ─────────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'types'" class="max-w-2xl">
        <div v-if="typesLoading" class="text-muted-foreground text-sm py-8">Загрузка…</div>

        <div v-else class="space-y-6">
          <div v-if="typesError" class="text-red-500 text-sm">{{ typesError }}</div>
          <!-- Add new -->
          <div class="bg-card border border-border rounded-lg p-5 space-y-3">
            <h2 class="font-semibold text-sm">Добавить тип</h2>
            <div class="flex gap-2">
              <input
                v-model="newName"
                type="text"
                placeholder="Название нового типа"
                maxlength="255"
                class="flex-1 px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                @keydown.enter.prevent="createType"
              />
              <button
                type="button"
                :disabled="creating || !newName.trim()"
                @click="createType"
                class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
              >
                {{ creating ? 'Добавление…' : 'Добавить' }}
              </button>
            </div>
            <div class="flex gap-3 items-start">
              <div class="flex-1 space-y-1">
                <label class="text-xs text-muted-foreground">SVG-иконка (вставьте код, напр. из heroicons.com)</label>
                <textarea
                  v-model="newIcon"
                  rows="3"
                  placeholder='<svg xmlns="http://www.w3.org/2000/svg" ...>...</svg>'
                  class="w-full px-3 py-2 rounded-md border border-input bg-background text-xs font-mono focus:outline-none focus:ring-1 focus:ring-ring resize-none"
                />
              </div>
              <div class="flex flex-col items-center gap-1 pt-5">
                <div class="w-10 h-10 rounded-md border border-border bg-muted flex items-center justify-center text-foreground [&_svg]:w-5 [&_svg]:h-5" v-html="newIcon.trim() || fallbackIcon" />
                <span class="text-[10px] text-muted-foreground">превью</span>
              </div>
            </div>
            <p v-if="createError" class="text-red-500 text-xs">{{ createError }}</p>
          </div>

          <!-- List -->
          <div class="bg-card border border-border rounded-lg divide-y divide-border">
            <div v-if="types.length === 0" class="px-5 py-4 text-sm text-muted-foreground">
              Нет типов. Добавьте первый.
            </div>
            <div v-for="type in types" :key="type.id" class="px-5 py-4">
              <template v-if="editId === type.id">
                <div class="flex gap-2 mb-3">
                  <input
                    v-model="editName"
                    type="text"
                    class="flex-1 px-2 py-1.5 rounded border border-input bg-background text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    @keydown.escape="cancelEdit"
                  />
                  <button type="button" :disabled="typeSaving" @click="saveEdit(type)" class="text-sm px-3 py-1.5 bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors disabled:opacity-50">
                    {{ typeSaving ? '…' : 'Сохранить' }}
                  </button>
                  <button type="button" @click="cancelEdit" class="text-sm text-muted-foreground hover:text-foreground transition-colors">Отмена</button>
                </div>
                <div class="flex gap-3 items-start">
                  <div class="flex-1 space-y-1">
                    <label class="text-xs text-muted-foreground">SVG-иконка</label>
                    <textarea v-model="editIcon" rows="3" class="w-full px-3 py-2 rounded-md border border-input bg-background text-xs font-mono focus:outline-none focus:ring-1 focus:ring-ring resize-none" />
                  </div>
                  <div class="flex flex-col items-center gap-1 pt-5">
                    <div class="w-10 h-10 rounded-md border border-border bg-muted flex items-center justify-center text-foreground [&_svg]:w-5 [&_svg]:h-5" v-html="editIcon.trim() || fallbackIcon" />
                    <span class="text-[10px] text-muted-foreground">превью</span>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded border border-border bg-muted flex items-center justify-center text-foreground shrink-0 [&_svg]:w-4 [&_svg]:h-4" v-html="type.icon || fallbackIcon" />
                  <span class="flex-1 text-sm font-medium">{{ type.name }}</span>
                  <span class="text-xs text-muted-foreground font-mono">{{ type.slug }}</span>
                  <button type="button" @click="startEdit(type)" class="text-sm text-muted-foreground hover:text-foreground transition-colors">Изменить</button>
                  <button type="button" @click="removeType(type)" class="text-sm text-red-500 hover:text-red-700 transition-colors">Удалить</button>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Tab: Аккаунт ────────────────────────────────────────────────────── -->
      <form v-else-if="activeTab === 'account'" @submit.prevent="handleSave" class="space-y-6 max-w-2xl">
        <section class="border border-border rounded-lg p-6">
          <h2 class="text-base font-semibold mb-4">{{ s.account.title }}</h2>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.account.email }}</label>
              <input v-model="form.account_email" type="email" :class="inputClass" />
            </div>
            <div>
              <label class="block text-sm font-medium text-foreground mb-1.5">{{ s.account.password }}</label>
              <input v-model="form.account_password" type="password" :placeholder="s.account.passwordPlaceholder" :class="inputClass" />
              <p class="text-xs text-muted-foreground mt-1">{{ s.account.passwordHint }}</p>
            </div>
          </div>
        </section>
        <div class="flex items-center gap-3">
          <button type="submit" :disabled="saving" class="px-5 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            {{ saving ? s.saving : s.save }}
          </button>
          <span v-if="saved" class="text-sm text-green-600">{{ s.saved }}</span>
          <span v-if="saveError" class="text-sm text-red-500">{{ saveError }}</span>
        </div>
      </form>

      <!-- ── Tab: Тексты интерфейса ──────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'texts'" class="max-w-4xl">
        <div class="flex items-center justify-between mb-4">
          <p class="text-sm text-muted-foreground">Пустое поле = дефолтное значение.</p>
          <div class="flex items-center gap-3">
            <span v-if="trSaved" class="text-sm text-green-600">Тексты сохранены</span>
            <span v-if="trError" class="text-sm text-red-500">{{ trError }}</span>
            <button @click="handleSaveTranslations" :disabled="trSaving" class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
              {{ trSaving ? 'Сохранение…' : 'Сохранить тексты' }}
            </button>
          </div>
        </div>

        <input
          v-model="trFilter"
          type="search"
          placeholder="Поиск по ключу или тексту…"
          class="w-full h-9 px-3 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring mb-6"
        />

        <div class="space-y-3">
          <div v-for="(items, section) in filteredGroups" :key="section" class="border border-border rounded-lg overflow-hidden">
            <button
              type="button"
              class="w-full flex items-center justify-between px-4 py-3 bg-muted/40 hover:bg-muted/70 transition-colors text-left"
              @click="toggleSection(section)"
            >
              <span class="text-sm font-semibold">
                {{ SECTION_LABELS[section] ?? section }}
                <span class="text-xs text-muted-foreground font-normal ml-1">({{ items.length }})</span>
              </span>
              <svg class="w-4 h-4 text-muted-foreground transition-transform" :class="collapsedSections.has(section) ? '' : 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m18 15-6-6-6 6"/>
              </svg>
            </button>
            <div v-if="!collapsedSections.has(section)">
              <div
                v-for="({ key, def }) in items"
                :key="key"
                class="grid grid-cols-[1fr_1fr] gap-3 px-4 py-2.5 border-t border-border/50 items-center hover:bg-muted/20"
              >
                <div class="min-w-0">
                  <div class="text-xs font-mono text-muted-foreground truncate">{{ key }}</div>
                  <div class="text-xs text-muted-foreground/60 truncate mt-0.5">{{ def }}</div>
                </div>
                <input
                  :value="overrides[key] ?? ''"
                  @input="overrides[key] = $event.target.value"
                  type="text"
                  :placeholder="def"
                  class="h-7 w-full px-2 text-sm rounded border border-input bg-background placeholder:text-muted-foreground/50 focus:outline-none focus:ring-1 focus:ring-ring"
                />
              </div>
            </div>
          </div>
          <p v-if="Object.keys(filteredGroups).length === 0" class="text-sm text-muted-foreground py-4 text-center">Ничего не найдено</p>
        </div>

        <div class="flex items-center gap-3 mt-6">
          <button @click="handleSaveTranslations" :disabled="trSaving" class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
            {{ trSaving ? 'Сохранение…' : 'Сохранить тексты' }}
          </button>
          <span v-if="trSaved" class="text-sm text-green-600">Тексты сохранены</span>
        </div>
      </div>

    </template>
  </div>
</template>

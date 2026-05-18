<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import VueDraggable from 'vuedraggable'
import * as adminApi from '@/api/admin.js'
import api from '@/api/client.js'
import { loadYmaps } from '@/utils/ymaps.js'
import { t } from '@/i18n.js'
import DateRangePicker from '@/components/DateRangePicker.vue'
import RichTextEditor from '@/components/admin/RichTextEditor.vue'

const props = defineProps({
  initialTour: { type: Object, default: null },
})
const emit = defineEmits(['saved'])

const saveSuccess = ref(false)

const isEdit = computed(() => !!props.initialTour)
const saving = ref(false)
const error = ref('')
const tourTypes = ref([])
const apiUrl = import.meta.env.VITE_API_URL ?? ''

// Generate
const generatePrompt = ref('')
const generating = ref(false)
const generateError = ref('')
const generateSuccess = ref(false)

const form = reactive({
  title: '',
  slug: '',
  description: '',
  duration_days: 1,
  type_id: '',
})

// Add new tour type inline
const addingType = ref(false)
const newTypeName = ref('')
const addTypeLoading = ref(false)
const addTypeError = ref('')

async function saveNewType() {
  if (!newTypeName.value.trim()) return
  addTypeLoading.value = true
  addTypeError.value = ''
  try {
    const { data } = await adminApi.createTourType({ name: newTypeName.value.trim() })
    const created = data.data ?? data
    tourTypes.value.push(created)
    form.type_id = created.id
    addingType.value = false
    newTypeName.value = ''
  } catch (e) {
    addTypeError.value = e.response?.data?.message ?? 'Ошибка при создании типа'
  } finally {
    addTypeLoading.value = false
  }
}

function cancelAddType() {
  addingType.value = false
  newTypeName.value = ''
  addTypeError.value = ''
}

// Photos — unified draggable list
// Each item: { uid, id: number|null, url: string, file?: File, isNew: boolean }
const photos = ref([])
const deletedPhotoIds = ref([])
const photoSectionRef = ref(null)
const photoValidationError = ref(false)

const visiblePhotosCount = computed(() => photos.value.length)

watch(visiblePhotosCount, (n) => { if (n > 0) photoValidationError.value = false })

// Variants: { id?, date, date_to, price, _delete }
const variants = ref([])

function computeDateTo(dateFrom, durationDays) {
  if (!dateFrom || !durationDays || durationDays <= 1) return ''
  const d = new Date(dateFrom)
  d.setDate(d.getDate() + Number(durationDays) - 1)
  return d.toISOString().split('T')[0]
}

function variantDuration(v) {
  if (!v.date || !v.date_to) return null
  return Math.round((new Date(v.date_to) - new Date(v.date)) / 86400000) + 1
}


// Waypoints: { lat, lng, order, label }
const waypoints = ref([])

// Map
const mapEl = ref(null)
const mapReady = ref(false)
const mapError = ref(false)
let ymapInstance = null
const markerInstances = []
let routeInstance = null
let routeDebounceTimer = null
let routeToken = 0

// ─── Init ───────────────────────────────────────────────────────────────

onMounted(async () => {
  const { data: types } = await adminApi.getTourTypes()
  tourTypes.value = types.data ?? types

  if (isEdit.value) {
    const t = props.initialTour
    form.title = t.title
    form.slug = t.slug
    form.description = t.description ?? ''
    form.duration_days = t.duration_days
    form.type_id = t.type_id
    photos.value = (t.photos ?? [])
      .slice()
      .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
      .map((p) => ({
        uid: `e-${p.id}`,
        id: p.id,
        url: p.path.startsWith('http') ? p.path : `${apiUrl}/storage/${p.path}`,
        isNew: false,
      }))
    variants.value = (t.variants ?? []).map((v) => ({
      ...v,
      date_to: computeDateTo(v.date, v.duration_days ?? t.duration_days),
      _delete: false,
    }))
    waypoints.value = (t.waypoints ?? []).map((w) => ({ ...w }))
  } else {
    form.type_id = tourTypes.value[0]?.id ?? ''
    variants.value = [{ date: '', date_to: '', price: '', _delete: false }]
  }

  initMap()
})

onUnmounted(() => {
  ymapInstance?.destroy()
  ymapInstance = null
})

// ─── Map ─────────────────────────────────────────────────────────────────

async function initMap() {
  try {
    await loadYmaps()

    const defaultCenter = waypoints.value.length
      ? [
          (Math.min(...waypoints.value.map((w) => +w.lat)) +
            Math.max(...waypoints.value.map((w) => +w.lat))) / 2,
          (Math.min(...waypoints.value.map((w) => +w.lng)) +
            Math.max(...waypoints.value.map((w) => +w.lng))) / 2,
        ]
      : [54.9, 82.9]

    ymapInstance = new window.ymaps.Map(mapEl.value, {
      center: defaultCenter,
      zoom: waypoints.value.length ? 6 : 4,
    }, { controls: ['zoomControl'] })

    ymapInstance.events.add('click', (e) => {
      const coords = e.get('coords')
      waypoints.value.push({
        lat: +coords[0].toFixed(6),
        lng: +coords[1].toFixed(6),
        order: waypoints.value.length + 1,
        label: '',
      })
    })

    mapReady.value = true
    refreshMarkers()

    await nextTick()

    if (waypoints.value.length) {
      const bounds = ymapInstance.geoObjects.getBounds()
      if (bounds) ymapInstance.setBounds(bounds, { checkZoomRange: true, zoomMargin: 60 })
    }

    refreshRoute()
  } catch {
    mapError.value = true
  }
}

function clearMarkers() {
  markerInstances.forEach((m) => {
    try { ymapInstance?.geoObjects.remove(m) } catch {}
  })
  markerInstances.length = 0
}

function refreshMarkers() {
  if (!mapReady.value) return
  clearMarkers()
  waypoints.value.forEach((wp, i) => {
    const placemark = new window.ymaps.Placemark(
      [+wp.lat, +wp.lng],
      { iconContent: String(i + 1), hintContent: wp.label || '' },
      { preset: 'islands#blueStretchyIcon', draggable: true }
    )
    placemark.events.add('dragend', () => {
      const coords = placemark.geometry.getCoordinates()
      waypoints.value[i].lat = +coords[0].toFixed(6)
      waypoints.value[i].lng = +coords[1].toFixed(6)
    })
    ymapInstance.geoObjects.add(placemark)
    markerInstances.push(placemark)
  })
}

watch(waypoints, refreshMarkers, { deep: true })


const waypointCoords = computed(() => waypoints.value.map(w => `${w.lat},${w.lng}`))
watch(waypointCoords, () => {
  clearTimeout(routeDebounceTimer)
  routeDebounceTimer = setTimeout(refreshRoute, 600)
})

function refreshRoute() {
  if (!mapReady.value) return
  if (routeInstance) {
    try { ymapInstance?.geoObjects.remove(routeInstance) } catch {}
    routeInstance = null
  }
  const pts = waypoints.value.filter(w => w.lat && w.lng)
  if (pts.length < 2) return

  const token = ++routeToken
  const waypointsParam = pts.map(w => `${w.lat},${w.lng}`).join('|')

  api.get('/api/route', { params: { waypoints: waypointsParam, mode: 'pedestrian' }, timeout: 10000 })
    .then(({ data }) => {
      if (token !== routeToken || !ymapInstance) return
      routeInstance = new window.ymaps.Polyline(data.coords, {}, {
        strokeColor: '#3b82f6',
        strokeWidth: 3,
      })
      ymapInstance.geoObjects.add(routeInstance)
    })
    .catch(() => {
      if (token !== routeToken || !ymapInstance) return
      routeInstance = new window.ymaps.Polyline(
        pts.map(w => [+w.lat, +w.lng]),
        {},
        { strokeColor: '#3b82f6', strokeWidth: 3, strokeStyle: 'dash' }
      )
      ymapInstance.geoObjects.add(routeInstance)
    })
}

// ─── Variants ────────────────────────────────────────────────────────────

const activeVariants = computed(() => variants.value.filter((v) => !v._delete))

function addVariant() {
  variants.value.push({ date: '', date_to: '', price: '', _delete: false })
}

function removeVariant(index) {
  const v = variants.value[index]
  if (v.id) {
    v._delete = true
  } else {
    variants.value.splice(index, 1)
  }
}

// ─── Photos ──────────────────────────────────────────────────────────────

let uidCounter = 0

function removePhoto(photo) {
  const idx = photos.value.indexOf(photo)
  if (idx === -1) return
  if (!photo.isNew) {
    deletedPhotoIds.value.push(photo.id)
  } else {
    URL.revokeObjectURL(photo.url)
  }
  photos.value.splice(idx, 1)
  onPhotoDragEnd()
}

function handlePhotoFiles(event) {
  Array.from(event.target.files).forEach((file) => {
    photos.value.push({
      uid: `n-${++uidCounter}`,
      id: null,
      url: URL.createObjectURL(file),
      file,
      isNew: true,
    })
  })
  event.target.value = ''
}

async function onPhotoDragEnd() {
  if (!isEdit.value) return
  const existing = photos.value
    .map((p, idx) => p.isNew ? null : { id: p.id, order: idx + 1 })
    .filter(Boolean)
  if (existing.length === 0) return
  try {
    await adminApi.reorderPhotos(props.initialTour.id, existing)
  } catch {}
}

// ─── Generate ────────────────────────────────────────────────────────────

const generateFields = reactive({
  description: true,
  title: false,
  type_id: false,
  waypoints: false,
  variants: false,
})

async function generate() {
  if (!generatePrompt.value.trim()) return
  generating.value = true
  generateError.value = ''
  generateSuccess.value = false
  try {
    const { data } = await adminApi.generateTour(generatePrompt.value)
    if (generateFields.title && data.title) form.title = data.title
    if (generateFields.description && data.description) form.description = data.description
    if (generateFields.type_id && data.type_id) form.type_id = data.type_id
    if (generateFields.waypoints && data.waypoints?.length) {
      waypoints.value = data.waypoints.map((w, i) => ({
        lat: w.lat,
        lng: w.lng,
        order: i + 1,
        label: w.label ?? '',
      }))
    }
    if (generateFields.variants && data.variants?.length) {
      variants.value = data.variants.map((v) => ({
        date: v.date,
        date_to: computeDateTo(v.date, data.duration_days),
        price: v.price,
        _delete: false,
      }))
    }
    generateSuccess.value = true
  } catch (e) {
    generateError.value = e.response?.data?.error ?? e.response?.data?.message ?? t.admin.form.generate.error
  } finally {
    generating.value = false
  }
}

// ─── Save ────────────────────────────────────────────────────────────────

async function save(stayOnPage = false) {
  error.value = ''
  saveSuccess.value = false
  photoValidationError.value = false

  if (!form.title.trim()) { error.value = t.admin.form.errors.title; return }
  if (!form.type_id) { error.value = t.admin.form.errors.type; return }

  const durations = variants.value.filter(v => !v._delete).map(variantDuration).filter(Boolean)
  form.duration_days = durations.length ? Math.min(...durations) : (form.duration_days ?? 1)

  if (visiblePhotosCount.value === 0) {
    photoValidationError.value = true
    error.value = t.admin.form.errors.photo
    await nextTick()
    photoSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    return
  }

  saving.value = true
  try {
    let tourId, tourSlug

    if (isEdit.value) {
      const { data } = await adminApi.updateTour(props.initialTour.id, {
        title: form.title,
        slug: form.slug || undefined,
        description: form.description,
        duration_days: Number(form.duration_days),
        type_id: Number(form.type_id),
      })
      tourId = props.initialTour.id
      tourSlug = data.slug
    } else {
      const { data } = await adminApi.createTour({
        title: form.title,
        slug: form.slug || undefined,
        description: form.description,
        duration_days: Number(form.duration_days),
        type_id: Number(form.type_id),
      })
      tourId = data.id
      tourSlug = data.slug
    }

    // Photos: delete removed
    for (const id of deletedPhotoIds.value) {
      await adminApi.deletePhoto(tourId, id)
    }
    // Photos: upload new (in order), collect assigned IDs
    const uploadedMap = new Map()
    for (const p of photos.value.filter(p => p.isNew)) {
      const { data } = await adminApi.uploadPhoto(tourId, p.file)
      uploadedMap.set(p.uid, data.id)
    }
    // Reorder all remaining photos by their current position
    const reorderList = photos.value
      .map((p, idx) => ({
        id: p.isNew ? uploadedMap.get(p.uid) : p.id,
        order: idx + 1,
      }))
      .filter(item => item.id != null)
    if (reorderList.length > 0) {
      await adminApi.reorderPhotos(tourId, reorderList)
    }

    // Variants
    for (const v of variants.value) {
      const dur = variantDuration(v) || null
      if (v._delete && v.id) {
        await adminApi.deleteVariant(v.id)
      } else if (v.id && !v._delete && v.date && v.price) {
        await adminApi.updateVariant(v.id, { date: v.date, duration_days: dur, price: Number(v.price) })
      } else if (!v.id && !v._delete && v.date && v.price) {
        await adminApi.createVariant({ tour_id: tourId, date: v.date, duration_days: dur, price: Number(v.price) })
      }
    }

    // Waypoints
    await adminApi.syncWaypoints(
      tourId,
      waypoints.value.map((wp, i) => ({
        lat: wp.lat,
        lng: wp.lng,
        order: i + 1,
        label: wp.label || null,
      }))
    )

    if (stayOnPage && isEdit.value) {
      // Reload fresh data from server and update form state without navigating
      const { data: fresh } = await adminApi.getAdminTour(tourSlug)
      photos.value = (fresh.photos ?? [])
        .slice()
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0))
        .map(p => ({
          uid: `e-${p.id}`,
          id: p.id,
          url: `${apiUrl}/storage/${p.path}`,
          isNew: false,
        }))
      deletedPhotoIds.value = []
      variants.value = (fresh.variants ?? []).map(v => ({ ...v, _delete: false }))
      form.slug = fresh.slug
      saveSuccess.value = true
    } else {
      emit('saved', tourSlug)
    }
  } catch (e) {
    error.value = e.response?.data?.message ?? t.admin.form.errors.save
    await nextTick()
    window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form @submit.prevent="save()" class="space-y-8">

    <!-- AI Generation -->
    <section class="border-2 border-dashed border-violet-300 dark:border-violet-700 rounded-lg p-6 space-y-3 bg-violet-50/50 dark:bg-violet-950/10">
      <div class="flex items-center gap-2">
        <h2 class="font-semibold text-base text-violet-900 dark:text-violet-200">{{ t.admin.form.generate.title }}</h2>
      </div>
      <p class="text-xs text-violet-700 dark:text-violet-400">{{ t.admin.form.generate.hint }}</p>
      <div class="flex flex-wrap gap-x-4 gap-y-1.5">
        <label v-for="(checked, key) in generateFields" :key="key" class="flex items-center gap-1.5 text-sm text-violet-800 dark:text-violet-300 select-none">
          <input type="checkbox" v-model="generateFields[key]" class="accent-violet-600 w-3.5 h-3.5" />
          {{ { description: 'Описание', title: 'Название', type_id: 'Тип', waypoints: 'Точки маршрута', variants: 'Даты и цены' }[key] }}
        </label>
      </div>
      <div class="flex gap-2 items-end">
        <textarea
          v-model="generatePrompt"
          rows="1"
          maxlength="500"
          :placeholder="t.admin.form.generate.placeholder"
          class="flex-1 px-3 py-2 rounded-md border border-violet-300 dark:border-violet-600 bg-white dark:bg-background text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 resize-none overflow-hidden"
          @keydown.enter.exact.prevent="generate"
          @input="e => { e.target.style.height = 'auto'; e.target.style.height = e.target.scrollHeight + 'px' }"
        />
        <button
          type="button"
          @click="generate"
          :disabled="generating || !generatePrompt.trim()"
          class="px-4 py-2 bg-violet-600 text-white text-sm font-medium rounded-md hover:bg-violet-700 transition-colors disabled:opacity-50 whitespace-nowrap"
        >
          {{ generating ? t.admin.form.generate.submitting : t.admin.form.generate.submit }}
        </button>
      </div>
      <p v-if="generateError" class="text-sm text-red-500">{{ generateError }}</p>
      <p v-if="generateSuccess && !generateError" class="text-sm text-green-600 dark:text-green-400">{{ t.admin.form.generate.success }}</p>
    </section>

    <!-- Basic fields -->
    <section class="bg-card border border-border rounded-lg p-6 space-y-4">
      <h2 class="font-semibold text-base mb-2">{{ t.admin.form.basic.sectionTitle }}</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="text-sm font-medium block mb-1">{{ t.admin.form.basic.name }} <span class="text-red-500">*</span></label>
          <input
            v-model="form.title"
            type="text"
            required
            class="w-full px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :placeholder="t.admin.form.basic.namePlaceholder"
          />
        </div>

        <div>
          <label class="text-sm font-medium block mb-1">{{ t.admin.form.basic.slug }}</label>
          <input
            v-model="form.slug"
            type="text"
            class="w-full px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
            :placeholder="t.admin.form.basic.slugPlaceholder"
          />
        </div>

        <div>
          <label class="text-sm font-medium block mb-1">{{ t.admin.form.basic.type }} <span class="text-red-500">*</span></label>
          <div class="flex gap-2 items-center">
            <select
              v-model="form.type_id"
              required
              class="flex-1 px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option v-for="type in tourTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
            <button
              v-if="!addingType"
              type="button"
              title="Добавить новый тип"
              @click="addingType = true"
              class="shrink-0 w-8 h-8 flex items-center justify-center rounded-md border border-border text-muted-foreground hover:border-primary hover:text-primary transition-colors text-lg leading-none"
            >+</button>
          </div>
          <!-- Inline new-type form -->
          <div v-if="addingType" class="mt-2 flex gap-2 items-center">
            <input
              v-model="newTypeName"
              type="text"
              maxlength="100"
              placeholder="Название типа"
              autofocus
              class="flex-1 px-3 py-1.5 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              @keydown.enter.prevent="saveNewType"
              @keydown.esc="cancelAddType"
            />
            <button
              type="button"
              @click="saveNewType"
              :disabled="addTypeLoading || !newTypeName.trim()"
              class="px-3 py-1.5 bg-primary text-primary-foreground text-xs font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 whitespace-nowrap"
            >{{ addTypeLoading ? '...' : 'Сохранить' }}</button>
            <button
              type="button"
              @click="cancelAddType"
              class="px-3 py-1.5 border border-border text-xs rounded-md hover:bg-muted transition-colors whitespace-nowrap"
            >Отмена</button>
          </div>
          <p v-if="addTypeError" class="mt-1 text-xs text-red-500">{{ addTypeError }}</p>
        </div>


        <div class="sm:col-span-2">
          <label class="text-sm font-medium block mb-1">{{ t.admin.form.basic.description }}</label>
          <RichTextEditor v-model="form.description" />
        </div>
      </div>
    </section>

    <!-- Photos -->
    <section
      ref="photoSectionRef"
      class="bg-card rounded-lg p-6 border transition-colors"
      :class="photoValidationError ? 'border-red-500 ring-1 ring-red-500' : 'border-border'"
    >
      <div class="flex items-center justify-between mb-1">
        <h2 class="font-semibold text-base">{{ t.admin.form.photos.sectionTitle }} <span class="text-red-500">*</span></h2>
        <span v-if="photoValidationError" class="text-xs text-red-500 font-medium">{{ t.admin.form.photos.required }}</span>
      </div>
      <p class="text-xs text-muted-foreground mb-4">Перетащите фото для изменения порядка. Первое фото — обложка тура.</p>

      <div class="flex flex-wrap gap-3 items-start">
        <VueDraggable
          v-model="photos"
          item-key="uid"
          :animation="200"
          handle=".drag-handle"
          class="flex flex-wrap gap-3"
          @end="onPhotoDragEnd"
        >
          <template #item="{ element, index }">
            <div
              class="relative w-28 h-20 rounded-md overflow-hidden border group"
              :class="element.isNew ? 'border-primary' : 'border-border'"
            >
              <img :src="element.url" alt="" class="w-full h-full object-cover drag-handle cursor-grab active:cursor-grabbing" />
              <!-- Cover badge -->
              <span
                v-if="index === 0"
                class="absolute top-1 left-1 text-[9px] bg-primary text-primary-foreground rounded px-1 leading-4 pointer-events-none"
              >Обложка</span>
              <!-- New badge -->
              <span
                v-if="element.isNew"
                class="absolute bottom-1 right-1 text-[9px] bg-primary text-primary-foreground rounded px-1 leading-4 pointer-events-none"
              >{{ t.admin.form.photos.newBadge }}</span>
              <!-- Delete overlay -->
              <button
                type="button"
                @click="removePhoto(element)"
                class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                title="Удалить"
              >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="w-3 h-3">
                  <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </template>
        </VueDraggable>

        <!-- Add button -->
        <label class="w-28 h-20 rounded-md border-2 border-dashed border-border flex flex-col items-center justify-center cursor-pointer hover:border-primary hover:bg-muted/50 transition-colors text-muted-foreground text-xs shrink-0">
          <span class="text-lg leading-none mb-1">+</span>
          <span>{{ t.admin.form.photos.add }}</span>
          <input type="file" multiple accept="image/*" class="sr-only" @change="handlePhotoFiles" />
        </label>
      </div>
    </section>

    <!-- Variants -->
    <section class="bg-card border border-border rounded-lg p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-base">{{ t.admin.form.variants.sectionTitle }}</h2>
        <button
          type="button"
          @click="addVariant"
          class="text-sm px-3 py-1 border border-border rounded hover:bg-muted transition-colors"
        >
          {{ t.admin.form.variants.add }}
        </button>
      </div>

      <div v-if="activeVariants.length === 0" class="text-sm text-muted-foreground">
        {{ t.admin.form.variants.empty }}
      </div>

      <div class="space-y-2">
        <div
          v-for="(v, i) in variants"
          :key="i"
          v-show="!v._delete"
          class="flex flex-wrap sm:flex-nowrap items-center gap-2"
        >
          <div class="w-full sm:flex-1 min-w-0">
            <DateRangePicker
              :date-from="v.date"
              :date-to="v.date_to"
              :allow-past="true"
              placeholder="Дата начала — окончания"
              @update:date-from="v.date = $event"
              @update:date-to="v.date_to = $event"
            />
          </div>
          <input
            v-model.number="v.price"
            type="number"
            min="0"
            :placeholder="t.admin.form.variants.pricePlaceholder"
            class="min-w-0 flex-1 w-full sm:w-auto px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <button
            type="button"
            @click="removeVariant(i)"
            class="text-red-500 hover:text-red-700 text-lg leading-none px-1 shrink-0"
            title="Удалить"
          >
            ×
          </button>
        </div>
      </div>
    </section>

    <!-- Waypoints -->
    <section class="bg-card border border-border rounded-lg p-6">
      <h2 class="font-semibold text-base mb-2">{{ t.admin.form.waypoints.sectionTitle }}</h2>
      <p class="text-xs text-muted-foreground mb-4">{{ t.admin.form.waypoints.hint }}</p>

      <div class="relative w-full h-72 rounded-xl overflow-hidden bg-muted mb-4">
        <div
          v-if="!mapReady && !mapError"
          class="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm"
        >
          {{ t.map.loading }}
        </div>
        <div
          v-if="mapError"
          class="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm"
        >
          {{ t.map.errorManual }}
        </div>
        <div ref="mapEl" class="w-full h-full" />
      </div>

      <div v-if="waypoints.length" class="space-y-2">
        <div
          v-for="(wp, i) in waypoints"
          :key="i"
          class="flex items-center gap-2 text-sm"
        >
          <span class="w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold shrink-0">
            {{ i + 1 }}
          </span>
          <span class="text-muted-foreground font-mono text-[10px] sm:text-xs w-24 sm:w-36 shrink-0 truncate">
            {{ wp.lat }}, {{ wp.lng }}
          </span>
          <input
            v-model="wp.label"
            type="text"
            :placeholder="t.admin.form.waypoints.labelPlaceholder"
            class="min-w-0 flex-1 px-2 py-1 rounded border border-input bg-background text-sm focus:outline-none focus:ring-1 focus:ring-ring"
          />
          <button
            type="button"
            @click="waypoints.splice(i, 1)"
            class="text-red-500 hover:text-red-700 text-lg leading-none px-1 shrink-0"
            title="Удалить"
          >
            ×
          </button>
        </div>
      </div>

      <!-- Manual add (fallback or map unavailable) -->
      <button
        type="button"
        @click="waypoints.push({ lat: 0, lng: 0, order: waypoints.length + 1, label: '' })"
        class="mt-3 text-sm text-muted-foreground hover:text-foreground transition-colors underline-offset-2 hover:underline"
      >
        {{ t.admin.form.waypoints.addManual }}
      </button>
    </section>

    <!-- Submit -->
    <div class="flex items-center flex-wrap gap-3">
      <button
        type="submit"
        :disabled="saving"
        class="px-6 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
      >
        {{ saving ? t.admin.form.actions.saving : isEdit ? t.admin.form.actions.saveAndExit : t.admin.form.actions.create }}
      </button>
      <button
        v-if="isEdit"
        type="button"
        :disabled="saving"
        @click="save(true)"
        class="px-6 py-2 bg-secondary text-secondary-foreground text-sm font-medium rounded-md hover:bg-secondary/80 transition-colors disabled:opacity-50 border border-border"
      >
        {{ saving ? t.admin.form.actions.saving : t.admin.form.actions.apply }}
      </button>
      <a
        href="/admin/tours"
        class="text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        {{ t.admin.form.actions.cancel }}
      </a>
      <div class="ml-auto flex items-center gap-3">
        <p v-if="saveSuccess && !error" class="text-sm text-green-600 dark:text-green-400">{{ t.admin.form.actions.saved }}</p>
        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
      </div>
    </div>

  </form>
</template>

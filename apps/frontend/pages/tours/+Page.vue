<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useData } from 'vike-vue/useData'
import TourCard from '@/components/TourCard.vue'
import TourFilters from '@/components/TourFilters.vue'
import TourPagination from '@/components/TourPagination.vue'
import api from '@/api/client.js'
import { t } from '@/i18n.js'

const data = useData()

const tours      = ref([...data.tours])
const page       = ref(data.pagination?.current_page ?? 1)
const hasMore    = ref(data.pagination ? data.pagination.current_page < data.pagination.last_page : false)
const loading    = ref(false)
const sortOrder  = ref(data.filters?.sort || 'date_desc')

const loadMode = computed(() => data.loadMode ?? 'pagination')
const isPagination = computed(() => loadMode.value === 'pagination')

// Sync when Vike navigates to a new page (e.g. /tours?page=2)
watch(() => data.tours, (newTours) => {
  tours.value = [...newTours]
  page.value  = data.pagination?.current_page ?? 1
  hasMore.value = data.pagination
    ? data.pagination.current_page < data.pagination.last_page
    : false
  sortOrder.value = data.filters?.sort || 'date_desc'
})

function buildParams() {
  const f = data.filters
  const p = {}
  if (f.type)         p.type         = f.type
  if (f.duration_min) p.duration_min = f.duration_min
  if (f.duration_max) p.duration_max = f.duration_max
  if (f.price_min)    p.price_min    = f.price_min
  if (f.price_max)    p.price_max    = f.price_max
  if (f.date_from)    p.date_from    = f.date_from
  if (f.date_to)      p.date_to      = f.date_to
  if (sortOrder.value) p.sort        = sortOrder.value
  if (data.perPage)   p.per_page     = data.perPage
  return p
}

function applySort() {
  const qs = new URLSearchParams(buildParams()).toString()
  window.location.href = '/tours' + (qs ? '?' + qs : '')
}

const sentinel = ref(null)
let observer = null

async function loadMore() {
  if (loading.value || !hasMore.value) return
  observer?.unobserve(sentinel.value)
  loading.value = true
  try {
    const nextPage = page.value + 1
    const res = await api.get('/api/tours', { params: { ...buildParams(), page: nextPage } })
    tours.value.push(...res.data.data)
    page.value = nextPage
    hasMore.value = nextPage < res.data.meta.last_page
  } finally {
    loading.value = false
    await nextTick()
    if (hasMore.value && sentinel.value) observer?.observe(sentinel.value)
  }
}

onMounted(() => {
  if (!data.isSearch && loadMode.value === 'infinite') {
    observer = new IntersectionObserver(
      ([entry]) => { if (entry.isIntersecting) loadMore() },
      { rootMargin: '100px' }
    )
    if (sentinel.value) observer.observe(sentinel.value)
  }
})

onUnmounted(() => { observer?.disconnect() })

function onCardEnter(el, done) {
  const idx = el.dataset.idx ? parseInt(el.dataset.idx) : 0
  el.style.transitionDelay = `${idx * 40}ms`
  done()
}
function onCardLeave(el, done) {
  el.style.transitionDelay = '0ms'
  done()
}
</script>

<template>
  <div class="container mx-auto px-4 py-10">
    <div class="flex items-center gap-3 mb-8">
      <h1 class="text-3xl font-bold">Каталог туров</h1>
      <span
        v-if="data.isSearch"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8" /><path stroke-linecap="round" d="M21 21l-4.35-4.35" />
        </svg>
        Семантический поиск
      </span>
    </div>

    <div v-if="data.isSearch" class="mb-6 flex items-center gap-3">
      <p class="text-muted-foreground">
        Результаты по запросу: <span class="font-medium text-foreground">«{{ data.searchQuery }}»</span>
      </p>
      <a href="/tours" class="text-sm text-primary hover:underline">← Все туры</a>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
      <aside v-if="!data.isSearch" class="lg:w-64 shrink-0">
        <TourFilters
          variant="sidebar"
          base-path="/tours"
          :tour-types="data.tourTypes"
          :filters="data.filters"
          :filter-meta="data.filterMeta"
          :total-count="data.pagination?.total ?? null"
        />
      </aside>

      <div class="flex-1 min-w-0">
        <!-- Count + Sort -->
        <div v-if="!data.isSearch && data.pagination" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-6">
          <p class="text-sm text-muted-foreground">
            {{ t.catalog.foundLabel }} <span class="font-medium text-foreground">{{ data.pagination.total }}</span>
          </p>
          <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs text-muted-foreground shrink-0">{{ t.catalog.sortLabel }}:</span>
            <select v-model="sortOrder" @change="applySort"
              class="h-8 rounded-md border border-input bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring cursor-pointer">
              <option value="date_desc">{{ t.catalog.sortDateDesc }}</option>
              <option value="date_asc">{{ t.catalog.sortDateAsc }}</option>
              <option value="price_asc">{{ t.catalog.sortPriceAsc }}</option>
              <option value="price_desc">{{ t.catalog.sortPriceDesc }}</option>
              <option value="duration_asc">{{ t.catalog.sortDurationAsc }}</option>
              <option value="duration_desc">{{ t.catalog.sortDurationDesc }}</option>
              <option value="title_asc">{{ t.catalog.sortTitleAsc }}</option>
              <option value="title_desc">{{ t.catalog.sortTitleDesc }}</option>
            </select>
          </div>
        </div>

        <p
          v-if="!tours || tours.length === 0"
          class="text-muted-foreground text-center py-20"
        >
          По вашему запросу туры не найдены.
        </p>

        <template v-else>
          <TransitionGroup
            name="card"
            tag="div"
            class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6"
            @enter="onCardEnter"
            @leave="onCardLeave"
          >
            <TourCard
              v-for="(tour, idx) in tours"
              :key="tour.id"
              :tour="tour"
              :data-idx="idx"
            />
          </TransitionGroup>

          <!-- Pagination mode -->
          <TourPagination
            v-if="!data.isSearch && isPagination && data.pagination && data.pagination.last_page > 1"
            :pagination="data.pagination"
            :filters="data.filters"
            base-path="/tours"
            class="mt-10"
          />

          <!-- Infinite scroll sentinel -->
          <div v-if="!data.isSearch && loadMode === 'infinite'" ref="sentinel" class="h-1 mt-8" />

          <!-- Load more button -->
          <div v-if="!data.isSearch && loadMode === 'load_more' && hasMore" class="flex justify-center mt-8">
            <button
              @click="loadMore"
              :disabled="loading"
              class="px-6 py-2.5 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
            >
              {{ loading ? '…' : t.admin.settings.display.loadMore }}
            </button>
          </div>

          <!-- Loading spinner -->
          <div v-if="loading && !isPagination" class="flex justify-center py-8">
            <svg class="w-8 h-8 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </div>

          <p v-if="!isPagination && !hasMore && !data.isSearch" class="text-center text-xs text-muted-foreground py-6">
            {{ t.catalog.allShown }}
          </p>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card-enter-active {
  transition: opacity 0.35s ease, transform 0.35s ease;
}
.card-enter-from {
  opacity: 0;
  transform: translateY(14px);
}
</style>

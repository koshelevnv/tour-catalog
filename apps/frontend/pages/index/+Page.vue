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
const page       = ref(1)
const hasMore    = ref(data.pagination ? 1 < data.pagination.last_page : false)
const loading    = ref(false)
const totalCount = ref(data.pagination?.total ?? data.tours.length)
const sortOrder  = ref(data.filters?.sort || 'date_desc')

const loadMode     = computed(() => data.loadMode ?? 'infinite')
const isPagination = computed(() => loadMode.value === 'pagination')

watch(() => data.tours, (newTours) => {
  tours.value      = [...newTours]
  page.value       = 1
  hasMore.value    = data.pagination ? 1 < data.pagination.last_page : false
  totalCount.value = data.pagination?.total ?? newTours.length
  sortOrder.value  = data.filters?.sort || 'date_desc'
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
  if (f.sort)         p.sort         = f.sort
  if (data.perPage)   p.per_page     = data.perPage
  return p
}

function applySearchSort() {
  const f = data.filters
  const params = new URLSearchParams()
  params.set('q', data.searchQuery)
  if (f.type)         params.set('type',         f.type)
  if (f.duration_min) params.set('duration_min', f.duration_min)
  if (f.duration_max) params.set('duration_max', f.duration_max)
  if (f.price_min)    params.set('price_min',    f.price_min)
  if (f.price_max)    params.set('price_max',    f.price_max)
  if (f.date_from)    params.set('date_from',    f.date_from)
  if (f.date_to)      params.set('date_to',      f.date_to)
  if (sortOrder.value) params.set('sort',        sortOrder.value)
  window.location.href = '/?' + params.toString()
}

const sentinel = ref(null)
let observer = null

async function loadMore() {
  if (loading.value || !hasMore.value) return
  observer?.unobserve(sentinel.value)
  loading.value = true
  try {
    const nextPage = page.value + 1
    let res
    if (data.isSearch) {
      const f = data.filters
      const params = { q: data.searchQuery, per_page: data.perPage, page: nextPage }
      if (f.type)         params.type         = f.type
      if (f.duration_min) params.duration_min = f.duration_min
      if (f.duration_max) params.duration_max = f.duration_max
      if (f.price_min)    params.price_min    = f.price_min
      if (f.price_max)    params.price_max    = f.price_max
      if (f.date_from)    params.date_from    = f.date_from
      if (f.date_to)      params.date_to      = f.date_to
      if (f.sort)         params.sort         = f.sort
      res = await api.get('/api/tours/search', { params })
    } else {
      res = await api.get('/api/tours', { params: { ...buildParams(), page: nextPage } })
    }
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
  if (loadMode.value === 'infinite') {
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
  <div class="container mx-auto px-4 py-8">

    <!-- ── SEARCH MODE: sidebar layout (like catalog) ── -->
    <template v-if="data.isSearch">

      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <h1 class="text-2xl font-bold">{{ t.catalog.title }}</h1>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
            </svg>
            {{ t.search.badge }}
          </span>
        </div>
        <p class="text-muted-foreground text-sm">
          {{ t.search.resultsFor }} <span class="font-medium text-foreground">«{{ data.searchQuery }}»</span>
          &ensp;<a href="/" class="text-primary hover:underline text-xs">{{ t.search.backToAll }}</a>
        </p>
      </div>

      <div class="flex flex-col lg:flex-row gap-8">
        <aside class="lg:w-64 shrink-0">
          <TourFilters
            variant="sidebar"
            base-path="/"
            :tour-types="data.tourTypes"
            :filters="data.filters"
            :filter-meta="data.filterMeta"
            :extra-params="{ q: data.searchQuery }"
          />
        </aside>

        <div class="flex-1 min-w-0">
          <!-- Count + Sort -->
          <div v-if="data.pagination" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-6">
            <p class="text-sm text-muted-foreground">
              {{ t.catalog.foundLabel }} <span class="font-medium text-foreground">{{ data.pagination.total }}</span>
            </p>
            <div class="flex items-center gap-2 shrink-0">
              <span class="text-xs text-muted-foreground shrink-0">{{ t.catalog.sortLabel }}:</span>
              <select v-model="sortOrder" @change="applySearchSort"
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

          <p v-if="!tours || tours.length === 0" class="text-center text-muted-foreground py-20">
            {{ t.catalog.empty }}
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

            <TourPagination
              v-if="isPagination && data.pagination && data.pagination.last_page > 1"
              :pagination="data.pagination"
              :filters="data.filters"
              base-path="/"
              :extra-params="{ q: data.searchQuery }"
              class="mt-10"
            />

            <div v-if="loadMode === 'infinite'" ref="sentinel" class="h-1 mt-8" />

            <div v-if="loadMode === 'load_more' && hasMore" class="flex justify-center mt-8">
              <button
                @click="loadMore"
                :disabled="loading"
                class="px-6 py-2.5 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
              >
                {{ loading ? '…' : t.admin.settings.display.loadMore }}
              </button>
            </div>

            <div v-if="loading && !isPagination" class="flex justify-center py-8">
              <svg class="w-8 h-8 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
            </div>

            <p v-if="!isPagination && !hasMore" class="text-center text-xs text-muted-foreground py-6">
              {{ t.catalog.allShown }}
            </p>
          </template>
        </div>
      </div>
    </template>

    <!-- ── CATALOG MODE: bar filters + grid ── -->
    <template v-else>
      <TourFilters
        variant="bar"
        base-path="/"
        :tour-types="data.tourTypes"
        :filters="data.filters"
        :filter-meta="data.filterMeta"
        :total-count="totalCount"
      />

      <TransitionGroup
        v-if="tours && tours.length > 0"
        name="card"
        tag="div"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
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

      <p v-else class="text-center text-muted-foreground py-20">
        {{ t.catalog.empty }}
      </p>

      <TourPagination
        v-if="isPagination && data.pagination && data.pagination.last_page > 1"
        :pagination="data.pagination"
        :filters="data.filters"
        base-path="/"
        class="mt-10"
      />

      <div v-if="loadMode === 'infinite'" ref="sentinel" class="h-1 mt-8" />

      <div v-if="loadMode === 'load_more' && hasMore" class="flex justify-center mt-8">
        <button
          @click="loadMore"
          :disabled="loading"
          class="px-6 py-2.5 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50"
        >
          {{ loading ? '…' : t.admin.settings.display.loadMore }}
        </button>
      </div>

      <div v-if="loading && !isPagination" class="flex justify-center py-8">
        <svg class="w-8 h-8 animate-spin text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
        </svg>
      </div>

      <p v-if="!isPagination && !hasMore && tours.length > 0" class="text-center text-xs text-muted-foreground py-6">
        {{ t.catalog.allShown }}
      </p>
    </template>

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

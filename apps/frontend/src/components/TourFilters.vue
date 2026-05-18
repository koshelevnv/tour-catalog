<script setup>
import { ref, computed, watch } from 'vue'
import DateRangePicker from '@/components/DateRangePicker.vue'
import RangeSlider from '@/components/RangeSlider.vue'
import { t } from '@/i18n.js'

const props = defineProps({
  tourTypes:   { type: Array,  required: true },
  filters:     { type: Object, required: true },
  basePath:    { type: String, default: '/' },
  variant:     { type: String, default: 'sidebar' }, // 'bar' | 'sidebar'
  totalCount:  { type: Number, default: null },
  extraParams: { type: Object, default: () => ({}) },
  filterMeta: {
    type: Object,
    default: () => ({ min_duration: 1, max_duration: 30, min_price: 0, max_price: 999999 }),
  },
})

const allIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/></svg>`

const selectedType = ref(props.filters.type ?? '')
const durationMin  = ref(props.filters.duration_min ? Number(props.filters.duration_min) : null)
const durationMax  = ref(props.filters.duration_max ? Number(props.filters.duration_max) : null)
const priceMin     = ref(props.filters.price_min ? Number(props.filters.price_min) : null)
const priceMax     = ref(props.filters.price_max ? Number(props.filters.price_max) : null)
const dateFrom     = ref(props.filters.date_from ?? '')
const dateTo       = ref(props.filters.date_to ?? '')
const sortOrder    = ref(props.filters.sort || 'date_desc')

// effective values — fall back to meta bounds when filter is unset
const effDurMin = computed(() => durationMin.value ?? props.filterMeta.min_duration)
const effDurMax = computed(() => durationMax.value ?? props.filterMeta.max_duration)
const effPrMin  = computed(() => priceMin.value  ?? props.filterMeta.min_price)
const effPrMax  = computed(() => priceMax.value  ?? props.filterMeta.max_price)

watch(() => props.filters, (f) => {
  selectedType.value = f.type ?? ''
  durationMin.value  = f.duration_min ? Number(f.duration_min) : null
  durationMax.value  = f.duration_max ? Number(f.duration_max) : null
  priceMin.value     = f.price_min ? Number(f.price_min) : null
  priceMax.value     = f.price_max ? Number(f.price_max) : null
  dateFrom.value     = f.date_from ?? ''
  dateTo.value       = f.date_to ?? ''
  sortOrder.value    = f.sort || 'date_desc'
}, { deep: true })

function buildParams() {
  const p = {}
  if (selectedType.value) p.type = selectedType.value
  // only send if different from global bounds (avoid noisy URLs)
  if (durationMin.value !== null && durationMin.value !== props.filterMeta.min_duration)
    p.duration_min = durationMin.value
  if (durationMax.value !== null && durationMax.value !== props.filterMeta.max_duration)
    p.duration_max = durationMax.value
  if (priceMin.value !== null && priceMin.value !== props.filterMeta.min_price)
    p.price_min = priceMin.value
  if (priceMax.value !== null && priceMax.value !== props.filterMeta.max_price)
    p.price_max = priceMax.value
  if (dateFrom.value) p.date_from = dateFrom.value
  if (dateTo.value)   p.date_to   = dateTo.value
  if (sortOrder.value) p.sort = sortOrder.value
  return p
}

function applySort() { navigate(buildParams()) }

function navigate(params) {
  const merged = { ...props.extraParams, ...params }
  const qs = new URLSearchParams(merged).toString()
  window.location.href = props.basePath + (qs ? '?' + qs : '')
}

function apply() { navigate(buildParams()) }
function reset() {
  const qs = new URLSearchParams(props.extraParams).toString()
  window.location.href = props.basePath + (qs ? '?' + qs : '')
}

function selectType(slug) {
  const params = buildParams()
  if (slug) params.type = slug
  else delete params.type
  navigate(params)
}

function onDurMin(v) { durationMin.value = v }
function onDurMax(v) { durationMax.value = v }
function onPrMin(v)  { priceMin.value  = v }
function onPrMax(v)  { priceMax.value  = v }
</script>

<template>
  <!-- ─── BAR variant (horizontal, for index page) ─── -->
  <template v-if="variant === 'bar'">
    <!-- Category chips -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-4 scrollbar-none">
      <button
        @click="selectType('')"
        :class="[
          'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium shrink-0 transition-colors',
          !selectedType
            ? 'bg-primary text-primary-foreground'
            : 'bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground'
        ]"
      >
        <span v-html="allIcon" class="shrink-0 [&_svg]:w-4 [&_svg]:h-4" />
        {{ t.catalog.allTypes }}
      </button>
      <button
        v-for="tt in tourTypes"
        :key="tt.id"
        @click="selectType(tt.slug)"
        :class="[
          'flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium shrink-0 transition-colors',
          selectedType === tt.slug
            ? 'bg-primary text-primary-foreground'
            : 'bg-muted text-muted-foreground hover:bg-accent hover:text-accent-foreground'
        ]"
      >
        <span v-html="tt.icon || allIcon" class="shrink-0 [&_svg]:w-4 [&_svg]:h-4" />
        {{ tt.name }}
      </button>
    </div>

    <!-- Horizontal filter bar -->
    <div class="flex flex-wrap items-end gap-x-6 gap-y-4 mb-6 p-4 rounded-xl border border-border bg-card">
      <!-- Duration slider -->
      <div class="flex flex-col gap-2 min-w-[180px] flex-1">
        <span class="text-xs font-medium text-muted-foreground">{{ t.catalog.daysLabel }}</span>
        <RangeSlider
          :min="filterMeta.min_duration" :max="filterMeta.max_duration"
          :model-min="effDurMin" :model-max="effDurMax"
          suffix="дней"
          @update:model-min="onDurMin" @update:model-max="onDurMax"
        />
      </div>
      <!-- Price slider -->
      <div class="flex flex-col gap-2 min-w-[180px] flex-1">
        <span class="text-xs font-medium text-muted-foreground">{{ t.catalog.priceLabel }}</span>
        <RangeSlider
          :min="filterMeta.min_price" :max="filterMeta.max_price"
          :model-min="effPrMin" :model-max="effPrMax"
          suffix="₽"
          @update:model-min="onPrMin" @update:model-max="onPrMax"
        />
      </div>
      <!-- Date range -->
      <div class="flex flex-col gap-2 min-w-[180px]">
        <span class="text-xs font-medium text-muted-foreground">{{ t.catalog.departureDates }}</span>
        <DateRangePicker
          :date-from="dateFrom" :date-to="dateTo"
          @update:date-from="dateFrom = $event"
          @update:date-to="dateTo = $event"
        />
      </div>
      <div class="flex gap-2 ml-auto">
        <button @click="apply"
          class="h-8 px-4 rounded-md bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors">
          {{ t.catalog.apply }}
        </button>
        <button @click="reset"
          class="h-8 px-4 rounded-md border border-border text-muted-foreground text-sm font-medium hover:bg-muted transition-colors">
          {{ t.catalog.reset }}
        </button>
      </div>
    </div>

    <!-- Count + Sort -->
    <div v-if="totalCount !== null" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-5">
      <p class="text-sm text-muted-foreground">
        {{ t.catalog.foundLabel }} <span class="font-medium text-foreground">{{ totalCount }}</span>
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
  </template>

  <!-- ─── SIDEBAR variant (vertical card, for /tours page) ─── -->
  <template v-else>
    <div class="rounded-xl border border-border bg-card p-5 space-y-5">
      <h2 class="font-semibold text-foreground">{{ t.catalog.filters }}</h2>

      <!-- Type select -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-muted-foreground">{{ t.catalog.type }}</label>
        <select v-model="selectedType"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">{{ t.catalog.allTypes }}</option>
          <option v-for="tt in tourTypes" :key="tt.id" :value="tt.slug">{{ tt.name }}</option>
        </select>
      </div>

      <!-- Duration slider -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-muted-foreground">{{ t.catalog.daysLabel }}</label>
        <RangeSlider
          :min="filterMeta.min_duration" :max="filterMeta.max_duration"
          :model-min="effDurMin" :model-max="effDurMax"
          suffix="дней"
          @update:model-min="onDurMin" @update:model-max="onDurMax"
        />
      </div>

      <!-- Price slider -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-muted-foreground">{{ t.catalog.priceLabel }}</label>
        <RangeSlider
          :min="filterMeta.min_price" :max="filterMeta.max_price"
          :model-min="effPrMin" :model-max="effPrMax"
          suffix="₽"
          @update:model-min="onPrMin" @update:model-max="onPrMax"
        />
      </div>

      <!-- Departure dates -->
      <div class="space-y-1.5">
        <label class="text-sm font-medium text-muted-foreground">{{ t.catalog.departureDates }}</label>
        <DateRangePicker
          :date-from="dateFrom" :date-to="dateTo"
          @update:date-from="dateFrom = $event"
          @update:date-to="dateTo = $event"
        />
      </div>

      <div class="flex flex-col gap-2 pt-1">
        <button @click="apply"
          class="w-full rounded-md bg-primary text-primary-foreground px-4 py-2 text-sm font-medium hover:bg-primary/90 transition-colors">
          {{ t.catalog.apply }}
        </button>
        <button @click="reset"
          class="w-full rounded-md border border-border text-muted-foreground px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
          {{ t.catalog.reset }}
        </button>
      </div>
    </div>
  </template>
</template>

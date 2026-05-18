<script setup>
import { computed } from 'vue'

const props = defineProps({
  pagination:  { type: Object, required: true },
  filters:     { type: Object, required: true },
  basePath:    { type: String, default: '/tours' },
  extraParams: { type: Object, default: () => ({}) },
})

function pageUrl(page) {
  const params = new URLSearchParams(props.extraParams)
  if (props.filters.type)         params.set('type',         props.filters.type)
  if (props.filters.duration_min) params.set('duration_min', props.filters.duration_min)
  if (props.filters.duration_max) params.set('duration_max', props.filters.duration_max)
  if (props.filters.price_min)    params.set('price_min',    props.filters.price_min)
  if (props.filters.price_max)    params.set('price_max',    props.filters.price_max)
  if (props.filters.date_from)    params.set('date_from',    props.filters.date_from)
  if (props.filters.date_to)      params.set('date_to',      props.filters.date_to)
  if (props.filters.sort)         params.set('sort',         props.filters.sort)
  if (page > 1)                   params.set('page',         page)
  const q = params.toString()
  return props.basePath + (q ? '?' + q : '')
}

const pages = computed(() => {
  const { current_page, last_page } = props.pagination
  const delta = 2
  const left = Math.max(1, current_page - delta)
  const right = Math.min(last_page, current_page + delta)
  const range = []
  for (let i = left; i <= right; i++) range.push(i)
  return range
})
</script>

<template>
  <nav class="flex items-center justify-center gap-1" aria-label="Pagination">
    <a
      v-if="pagination.current_page > 1"
      :href="pageUrl(pagination.current_page - 1)"
      class="px-3 py-2 rounded-md border border-border text-sm text-muted-foreground hover:bg-muted transition-colors"
      aria-label="Previous page"
    >
      ←
    </a>

    <template v-if="pages[0] > 1">
      <a :href="pageUrl(1)" class="px-3 py-2 rounded-md border border-border text-sm text-muted-foreground hover:bg-muted transition-colors">1</a>
      <span v-if="pages[0] > 2" class="px-2 text-muted-foreground text-sm">…</span>
    </template>

    <a
      v-for="page in pages"
      :key="page"
      :href="pageUrl(page)"
      class="px-3 py-2 rounded-md border text-sm transition-colors"
      :class="
        page === pagination.current_page
          ? 'bg-primary text-primary-foreground border-primary font-medium'
          : 'border-border text-muted-foreground hover:bg-muted'
      "
      :aria-current="page === pagination.current_page ? 'page' : undefined"
    >
      {{ page }}
    </a>

    <template v-if="pages[pages.length - 1] < pagination.last_page">
      <span v-if="pages[pages.length - 1] < pagination.last_page - 1" class="px-2 text-muted-foreground text-sm">…</span>
      <a :href="pageUrl(pagination.last_page)" class="px-3 py-2 rounded-md border border-border text-sm text-muted-foreground hover:bg-muted transition-colors">{{ pagination.last_page }}</a>
    </template>

    <a
      v-if="pagination.current_page < pagination.last_page"
      :href="pageUrl(pagination.current_page + 1)"
      class="px-3 py-2 rounded-md border border-border text-sm text-muted-foreground hover:bg-muted transition-colors"
      aria-label="Next page"
    >
      →
    </a>
  </nav>
</template>

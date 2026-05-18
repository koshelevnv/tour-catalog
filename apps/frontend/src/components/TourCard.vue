<script setup>
import { t, formatDuration } from '@/i18n.js'

defineProps({
  tour: { type: Object, required: true },
})

const apiUrl = import.meta.env.VITE_API_URL ?? ''

function photoUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `${apiUrl}/storage/${path}`
}

function formatPrice(price) {
  if (!price) return '—'
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}

</script>

<template>
  <a
    :href="`/tours/${tour.slug}`"
    class="group flex flex-col rounded-xl border border-border bg-card overflow-hidden hover:shadow-md transition-shadow"
  >
    <div class="aspect-[4/3] bg-muted overflow-hidden">
      <img
        v-if="photoUrl(tour.cover)"
        :src="photoUrl(tour.cover)"
        :alt="tour.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      <div
        v-else
        class="w-full h-full flex items-center justify-center text-muted-foreground text-sm"
      >
        {{ t.tour.noPhoto }}
      </div>
    </div>

    <div class="p-4 flex flex-col gap-2 flex-1">
      <span
        v-if="tour.type"
        class="text-xs font-medium text-primary bg-primary/10 rounded-full px-2.5 py-0.5 w-fit"
      >
        {{ tour.type.name }}
      </span>
      <h3 class="font-semibold text-foreground leading-snug line-clamp-2">
        {{ tour.title }}
      </h3>
      <div
        class="flex items-center justify-between mt-auto pt-3 border-t border-border text-sm"
      >
        <span class="text-muted-foreground">
          {{ formatDuration(tour.duration_min, tour.duration_max) }}
        </span>
        <span class="font-semibold text-foreground">
          {{ t.tour.priceFrom }}&nbsp;{{ formatPrice(tour.price_from) }}
        </span>
      </div>
    </div>
  </a>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useData } from 'vike-vue/useData'
import { Head } from 'vike-vue/Head'
import TourMap from '@/components/TourMap.vue'
import AppBreadcrumbs from '@/components/AppBreadcrumbs.vue'
import { t, formatDuration } from '@/i18n.js'
import { seo } from '@/utils/seoSettings.js'

const { tour } = useData()

if (!tour) {
  if (typeof window !== 'undefined') window.location.replace('/tours')
}

const apiUrl = import.meta.env.VITE_API_URL ?? ''
const photos = tour?.photos ?? []

function photoUrl(path) {
  if (!path) return null
  if (path.startsWith('http')) return path
  return `${apiUrl}/storage/${path}`
}

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU').format(Number(price)) + ' ₽'
}

function formatDate(dateStr) {
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(dateStr))
}


const priceFrom = computed(() => {
  if (!tour?.variants?.length) return null
  return Math.min(...tour.variants.map((v) => Number(v.price)))
})

// Lightbox
const lightboxOpen = ref(false)
const lightboxIndex = ref(0)

function openLightbox(index) {
  lightboxIndex.value = index
  lightboxOpen.value = true
}

function closeLightbox() {
  lightboxOpen.value = false
}

function prevPhoto() {
  lightboxIndex.value = (lightboxIndex.value - 1 + photos.length) % photos.length
}

function nextPhoto() {
  lightboxIndex.value = (lightboxIndex.value + 1) % photos.length
}

function handleKeydown(e) {
  if (!lightboxOpen.value) return
  if (e.key === 'Escape') closeLightbox()
  if (e.key === 'ArrowLeft') prevPhoto()
  if (e.key === 'ArrowRight') nextPhoto()
}

onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => document.removeEventListener('keydown', handleKeydown))

const desc = computed(() => tour?.description?.slice(0, 160) ?? '')
const cover = computed(() => {
  const p = tour?.photos?.[0]?.path
  if (!p) return null
  return p.startsWith('http') ? p : `${apiUrl}/storage/${p}`
})
</script>

<template>
  <Head>
    <title>{{ tour.title }} — {{ seo.meta_title }}</title>
    <meta name="description" :content="desc" />
    <meta property="og:title" :content="tour.title" />
    <meta property="og:description" :content="desc" />
    <meta v-if="cover" property="og:image" :content="cover" />
    <meta property="og:type" content="article" />
  </Head>

  <div class="container mx-auto px-4 py-10 max-w-6xl">

    <AppBreadcrumbs :items="[
      { label: t.breadcrumbs.home, href: '/' },
      { label: t.breadcrumbs.catalog, href: '/tours' },
      { label: tour.title },
    ]" />

    <!-- Gallery -->
    <section v-if="photos.length > 0" class="mb-8 rounded-xl overflow-hidden">

      <!-- 4+ photos -->
      <template v-if="photos.length >= 4">
        <!-- Mobile: одно главное фото с индикатором -->
        <div class="sm:hidden relative cursor-pointer" @click="openLightbox(0)">
          <div class="aspect-[4/3] overflow-hidden">
            <img
              :src="photoUrl(photos[0].path)"
              :alt="tour.title"
              class="w-full h-full object-cover"
            />
          </div>
          <span class="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2.5 py-1 rounded-md">
            1 / {{ photos.length }}
          </span>
        </div>
        <!-- Desktop: Airbnb grid -->
        <div class="hidden sm:grid grid-cols-4 grid-rows-2 gap-1 h-[340px] md:h-[420px]">
          <div
            class="col-span-2 row-span-2 overflow-hidden cursor-pointer"
            @click="openLightbox(0)"
          >
            <img
              :src="photoUrl(photos[0].path)"
              :alt="tour.title"
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
            />
          </div>
          <div
            v-for="(photo, i) in photos.slice(1, 5)"
            :key="photo.id"
            class="relative overflow-hidden cursor-pointer"
            @click="openLightbox(i + 1)"
          >
            <img
              :src="photoUrl(photo.path)"
              :alt="tour.title"
              class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
            />
            <div
              v-if="i === 3 && photos.length > 5"
              class="absolute inset-0 bg-black/50 flex items-center justify-center pointer-events-none"
            >
              <span class="text-white text-xl font-semibold">+{{ photos.length - 5 }}</span>
            </div>
          </div>
        </div>
      </template>

      <!-- 1–3 photos: равные колонки -->
      <div
        v-else
        class="grid gap-1 h-[220px] sm:h-[320px] md:h-[420px]"
        :class="{
          'grid-cols-1': photos.length === 1,
          'grid-cols-2': photos.length === 2,
          'grid-cols-3': photos.length === 3,
        }"
      >
        <div
          v-for="(photo, i) in photos"
          :key="photo.id"
          class="overflow-hidden cursor-pointer"
          @click="openLightbox(i)"
        >
          <img
            :src="photoUrl(photo.path)"
            :alt="tour.title"
            class="w-full h-full object-cover hover:scale-105 transition-transform duration-300"
          />
        </div>
      </div>
    </section>

    <!-- No photos placeholder -->
    <div
      v-else
      class="h-48 bg-muted rounded-xl flex items-center justify-center text-muted-foreground mb-8"
    >
      {{ t.tour.noPhotos }}
    </div>

    <!-- Title + meta -->
    <div class="mb-8">
      <div class="flex flex-wrap items-center gap-3 mb-3">
        <span
          v-if="tour.type"
          class="text-xs font-medium text-primary bg-primary/10 rounded-full px-3 py-1"
        >
          {{ tour.type.name }}
        </span>
        <span class="text-sm text-muted-foreground">
          {{ formatDuration(tour.duration_min, tour.duration_max) }}
        </span>
        <span v-if="priceFrom" class="text-sm font-semibold">
          {{ t.tour.priceFrom }}&nbsp;{{ formatPrice(priceFrom) }}
        </span>
      </div>
      <h1 class="text-3xl lg:text-4xl font-bold text-foreground leading-snug">
        {{ tour.title }}
      </h1>
    </div>

    <!-- Content grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-12">

      <!-- Description -->
      <div class="lg:col-span-2">
        <h2 class="text-xl font-semibold mb-4">{{ t.tour.description }}</h2>
        <div
          v-if="tour.description"
          class="text-foreground leading-relaxed prose-description"
          v-html="tour.description"
        />
        <p v-else class="text-muted-foreground">{{ t.tour.noDescription }}</p>
      </div>

      <!-- Variants -->
      <div class="lg:col-span-1">
        <div class="rounded-xl border border-border bg-card p-6 lg:sticky lg:top-24">
          <h2 class="text-xl font-semibold mb-4">{{ t.tour.datesAndPrices }}</h2>
          <div v-if="tour.variants?.length">
            <div
              v-for="variant in tour.variants"
              :key="variant.id"
              class="flex items-center justify-between py-3 border-b border-border last:border-0"
            >
              <span class="text-sm text-foreground">{{ formatDate(variant.date) }}</span>
              <span class="text-sm font-semibold">{{ formatPrice(variant.price) }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-muted-foreground">{{ t.tour.noDates }}</p>
        </div>
      </div>

    </div>

    <!-- Route map -->
    <section v-if="tour.waypoints?.length">
      <h2 class="text-xl font-semibold mb-4">{{ t.tour.route }}</h2>
      <TourMap :waypoints="tour.waypoints" />
    </section>

  </div>

  <!-- Lightbox -->
  <Teleport to="#teleported">
    <div
      v-if="lightboxOpen"
      class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
      @click.self="closeLightbox"
    >
      <!-- Close -->
      <button
        class="absolute top-4 right-4 text-white/80 hover:text-white transition-colors"
        aria-label="Закрыть"
        @click="closeLightbox"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6 6 18M6 6l12 12"/>
        </svg>
      </button>

      <!-- Counter -->
      <div class="absolute top-4 left-1/2 -translate-x-1/2 text-white/70 text-sm select-none">
        {{ lightboxIndex + 1 }} / {{ photos.length }}
      </div>

      <!-- Prev -->
      <button
        v-if="photos.length > 1"
        class="absolute left-3 top-1/2 -translate-y-1/2 text-white/80 hover:text-white transition-colors p-2"
        aria-label="Предыдущее фото"
        @click="prevPhoto"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m15 18-6-6 6-6"/>
        </svg>
      </button>

      <!-- Active image -->
      <img
        :src="photoUrl(photos[lightboxIndex].path)"
        :alt="tour.title"
        class="max-w-[88vw] max-h-[82vh] object-contain rounded-lg select-none"
        draggable="false"
      />

      <!-- Next -->
      <button
        v-if="photos.length > 1"
        class="absolute right-3 top-1/2 -translate-y-1/2 text-white/80 hover:text-white transition-colors p-2"
        aria-label="Следующее фото"
        @click="nextPhoto"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="m9 18 6-6-6-6"/>
        </svg>
      </button>

      <!-- Thumbnail strip -->
      <div
        v-if="photos.length > 1"
        class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 max-w-[90vw] overflow-x-auto pb-1"
      >
        <button
          v-for="(photo, i) in photos"
          :key="photo.id"
          class="shrink-0 w-12 h-12 rounded overflow-hidden border-2 transition-all"
          :class="i === lightboxIndex ? 'border-white' : 'border-transparent opacity-60 hover:opacity-100'"
          @click="lightboxIndex = i"
        >
          <img :src="photoUrl(photo.path)" :alt="tour.title" class="w-full h-full object-cover" />
        </button>
      </div>
    </div>
  </Teleport>
</template>

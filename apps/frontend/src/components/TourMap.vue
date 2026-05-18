<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { loadYmaps } from '@/utils/ymaps.js'
import { t } from '@/i18n.js'
import api from '@/api/client.js'

const props = defineProps({
  waypoints: { type: Array, default: () => [] }
})

const mapEl = ref(null)
const loading = ref(true)
const error = ref(null)
const routeMode = ref('pedestrian')
const routeNotice = ref(null)
let ymapInstance = null
let routeGeoObject = null
let buildRouteToken = 0

const ROUTE_MODES = [
  { value: 'pedestrian', label: t.map.routeModes.pedestrian },
  { value: 'auto',       label: t.map.routeModes.driving },
  { value: 'straight',   label: t.map.routeModes.straight },
]

onMounted(async () => {
  if (!props.waypoints.length) { loading.value = false; return }
  try {
    await initMap()
  } catch (e) {
    error.value = t.map.error
    loading.value = false
  }
})

onUnmounted(() => {
  ymapInstance?.destroy()
  ymapInstance = null
  routeGeoObject = null
})

async function initMap() {
  await loadYmaps()

  const coords = props.waypoints.map(wp => [+wp.lat, +wp.lng])
  const lats = coords.map(c => c[0])
  const lngs = coords.map(c => c[1])
  const center = [
    (Math.min(...lats) + Math.max(...lats)) / 2,
    (Math.min(...lngs) + Math.max(...lngs)) / 2,
  ]

  // Reveal element before creating map so it has real dimensions
  loading.value = false
  await nextTick()

  ymapInstance = new window.ymaps.Map(mapEl.value, { center, zoom: 10 }, { controls: ['zoomControl'] })

  props.waypoints.forEach((wp, i) => {
    const placemark = new window.ymaps.Placemark(
      [+wp.lat, +wp.lng],
      {
        iconContent: String(wp.order ?? i + 1),
        hintContent: wp.label || '',
      },
      { preset: 'islands#blueStretchyIcon' }
    )
    ymapInstance.geoObjects.add(placemark)
  })

  const bounds = ymapInstance.geoObjects.getBounds()
  if (bounds) {
    ymapInstance.setBounds(bounds, { checkZoomRange: true, zoomMargin: 60 })
  }

  if (coords.length > 1) {
    buildRoute(coords, routeMode.value)
  }
}

function buildRoute(coords, mode) {
  if (!ymapInstance) return

  if (routeGeoObject) {
    try { ymapInstance.geoObjects.remove(routeGeoObject) } catch {}
    routeGeoObject = null
  }

  if (coords.length < 2) return

  if (mode === 'straight') {
    routeGeoObject = new window.ymaps.Polyline(coords, {}, {
      strokeColor: '#3b82f6',
      strokeWidth: 4,
    })
    ymapInstance.geoObjects.add(routeGeoObject)
    return
  }

  const token = ++buildRouteToken

  const waypointsParam = coords.map(c => c.join(',')).join('|')

  api.get('/api/route', { params: { waypoints: waypointsParam, mode }, timeout: 10000 })
    .then(({ data }) => {
      if (token !== buildRouteToken || !ymapInstance) return
      routeGeoObject = new window.ymaps.Polyline(data.coords, {}, {
        strokeColor: '#3b82f6',
        strokeWidth: 4,
      })
      ymapInstance.geoObjects.add(routeGeoObject)
    })
    .catch(() => {
      if (token !== buildRouteToken) return
      autoFallback(coords, mode)
    })
}

function autoFallback(coords, failedMode) {
  if (failedMode === 'pedestrian') {
    routeMode.value = 'auto'
    routeNotice.value = t.map.noticeFallbackDriving
    buildRoute(coords, 'auto')
  } else if (failedMode === 'auto') {
    routeMode.value = 'straight'
    routeNotice.value = t.map.noticeFallbackStraight
    buildRoute(coords, 'straight')
  }
}

function switchRouteMode(mode) {
  routeMode.value = mode
  routeNotice.value = null
  const coords = props.waypoints.map(wp => [+wp.lat, +wp.lng])
  buildRoute(coords, mode)
}
</script>

<template>
  <div v-if="waypoints.length" class="relative w-full h-80 rounded-xl overflow-hidden bg-muted">
    <div
      v-if="loading && !error"
      class="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm z-10"
    >
      {{ t.map.loading }}
    </div>
    <div
      v-if="error"
      class="absolute inset-0 flex items-center justify-center text-muted-foreground text-sm z-10"
    >
      {{ error }}
    </div>

    <!-- Route mode switcher -->
    <div
      v-if="!loading && !error && waypoints.length > 1"
      class="absolute bottom-10 right-2 z-10 flex gap-0.5 bg-white/95 dark:bg-zinc-900/95 rounded-lg p-0.5 shadow-md"
    >
      <button
        v-for="m in ROUTE_MODES"
        :key="m.value"
        type="button"
        @click="switchRouteMode(m.value)"
        class="px-2.5 py-1 text-xs rounded-md font-medium transition-colors"
        :class="routeMode === m.value
          ? 'bg-primary text-primary-foreground'
          : 'text-muted-foreground hover:text-foreground hover:bg-muted'"
      >
        {{ m.label }}
      </button>
    </div>

    <!-- Fallback notice -->
    <div
      v-if="routeNotice && !loading"
      class="absolute bottom-2 left-2 z-10 bg-black/70 text-white text-xs px-2.5 py-1.5 rounded-md max-w-[calc(100%-1rem)]"
    >
      {{ routeNotice }}
    </div>

    <div v-show="!loading && !error" ref="mapEl" class="w-full h-full" />
  </div>
</template>

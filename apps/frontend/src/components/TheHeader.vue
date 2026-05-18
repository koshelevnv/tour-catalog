<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'

const mobileSearchOpen = ref(false)
import { navigate } from 'vike/client/router'
import api from '@/api/client.js'
import { t } from '@/i18n.js'
import { useTheme } from '@/composables/useTheme.js'

const { isDark, toggle: toggleTheme } = useTheme()

const searchQuery = ref('')

function syncQueryFromUrl() {
  searchQuery.value = new URLSearchParams(window.location.search).get('q') || ''
}

// Patch history.pushState once to emit a custom event on Vike SPA navigation
if (typeof window !== 'undefined' && !window.__locationChangePatchInstalled) {
  window.__locationChangePatchInstalled = true
  const _push = history.pushState.bind(history)
  history.pushState = function (...args) {
    _push(...args)
    window.dispatchEvent(new Event('locationchange'))
  }
  const _replace = history.replaceState.bind(history)
  history.replaceState = function (...args) {
    _replace(...args)
    window.dispatchEvent(new Event('locationchange'))
  }
}
const suggestions = ref([])
const showDropdown = ref(false)
const loading = ref(false)
const activeIndex = ref(-1)
const inputRef = ref(null)
const dropdownRef = ref(null)

let debounceTimer = null

watch(searchQuery, (val) => {
  activeIndex.value = -1
  clearTimeout(debounceTimer)
  const q = val.trim()
  if (q.length < 2) {
    suggestions.value = []
    showDropdown.value = false
    return
  }
  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const res = await api.get('/api/tours/suggest', { params: { q } })
      suggestions.value = res.data.data ?? []
      showDropdown.value = suggestions.value.length > 0 && document.activeElement === inputRef.value
    } catch {
      suggestions.value = []
      showDropdown.value = false
    } finally {
      loading.value = false
    }
  }, 250)
})

function handleSearch() {
  const q = searchQuery.value.trim()
  if (!q) return
  closeDropdown()
  window.location.href = `/?q=${encodeURIComponent(q)}`
}

function selectSuggestion(s) {
  closeDropdown()
  navigate(`/tours/${s.slug}`)
}

function onKeydown(e) {
  if (!showDropdown.value || !suggestions.value.length) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    activeIndex.value = Math.min(activeIndex.value + 1, suggestions.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    activeIndex.value = Math.max(activeIndex.value - 1, -1)
  } else if (e.key === 'Enter' && activeIndex.value >= 0) {
    e.preventDefault()
    selectSuggestion(suggestions.value[activeIndex.value])
  } else if (e.key === 'Escape') {
    closeDropdown()
  }
}

function closeDropdown() {
  showDropdown.value = false
  activeIndex.value = -1
}

function onClickOutside(e) {
  if (
    inputRef.value && !inputRef.value.contains(e.target) &&
    dropdownRef.value && !dropdownRef.value.contains(e.target)
  ) {
    closeDropdown()
  }
}

onMounted(() => {
  syncQueryFromUrl()
  document.addEventListener('mousedown', onClickOutside)
  window.addEventListener('popstate', syncQueryFromUrl)
  window.addEventListener('locationchange', syncQueryFromUrl)
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside)
  window.removeEventListener('popstate', syncQueryFromUrl)
  window.removeEventListener('locationchange', syncQueryFromUrl)
})
</script>

<template>
  <header class="sticky top-0 z-50 border-b border-border bg-card/95 backdrop-blur">
    <div class="container mx-auto px-4 h-16 flex items-center gap-3">
      <a href="/" class="text-xl font-bold text-foreground hover:text-primary/80 transition-colors shrink-0">
        {{ t.nav.brand }}
      </a>

      <!-- Desktop search form (hidden on mobile) -->
      <form @submit.prevent="handleSearch" class="flex-1 max-w-sm hidden sm:flex relative" autocomplete="off">
        <div class="relative w-full">
          <input
            ref="inputRef"
            v-model="searchQuery"
            type="search"
            :placeholder="t.search.placeholder"
            class="w-full h-9 pl-3 pr-9 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            @keydown="onKeydown"
            @focus="showDropdown = suggestions.length > 0"
          />
          <button
            v-if="!loading"
            type="submit"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            :aria-label="t.search.ariaLabel"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
            </svg>
          </button>
          <span v-else class="absolute right-2 top-1/2 -translate-y-1/2">
            <svg class="w-4 h-4 animate-spin text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
          </span>
        </div>

        <!-- Autocomplete dropdown (desktop) -->
        <div
          v-if="showDropdown"
          ref="dropdownRef"
          class="absolute top-full left-0 right-0 mt-1 rounded-md border border-border bg-card shadow-md z-50 overflow-hidden"
        >
          <button
            v-for="(s, i) in suggestions"
            :key="s.id"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm transition-colors cursor-pointer"
            :class="i === activeIndex ? 'bg-primary/10 text-primary' : 'hover:bg-muted text-foreground'"
            @mousedown.prevent="selectSuggestion(s)"
          >
            <img
              v-if="s.photo"
              :src="s.photo"
              class="w-8 h-8 rounded object-cover shrink-0"
              alt=""
            />
            <span v-else class="w-8 h-8 rounded bg-muted shrink-0 flex items-center justify-center text-muted-foreground">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 20l6-9 4 5 3-4 5 8H3z"/>
              </svg>
            </span>
            <span class="flex-1 min-w-0">
              <span class="block truncate font-medium">{{ s.title }}</span>
              <span class="block text-xs text-muted-foreground">{{ s.duration_days }} {{ t.search.days }}</span>
            </span>
          </button>

          <button
            type="submit"
            class="w-full px-3 py-2 text-left text-xs text-primary hover:bg-muted border-t border-border transition-colors cursor-pointer"
          >
            «{{ searchQuery }}» — {{ t.search.showAll }}
          </button>
        </div>
      </form>

      <nav class="flex items-center gap-2 sm:gap-4 ml-auto shrink-0">
        <!-- Каталог — скрыт на самых маленьких экранах -->
        <a
          href="/tours"
          class="hidden sm:block text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
        >
          {{ t.nav.tours }}
        </a>

        <!-- Theme toggle -->
        <button
          @click="toggleTheme"
          class="w-9 h-9 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
          :aria-label="isDark ? 'Светлая тема' : 'Тёмная тема'"
        >
          <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="4"/>
            <path stroke-linecap="round" d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
          </svg>
        </button>

        <!-- Mobile search toggle button -->
        <button
          class="sm:hidden w-9 h-9 flex items-center justify-center rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
          :aria-label="t.search.ariaLabel"
          @click="mobileSearchOpen = !mobileSearchOpen"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
        </button>

        <a
          href="/admin/tours"
          class="text-sm font-medium px-2.5 py-1.5 sm:px-3 rounded-md bg-primary text-primary-foreground hover:bg-primary/90 transition-colors whitespace-nowrap"
        >
          {{ t.nav.admin }}
        </a>
      </nav>
    </div>

    <!-- Mobile search bar (expandable) -->
    <div v-if="mobileSearchOpen" class="sm:hidden border-t border-border px-4 py-2 bg-card/95">
      <form @submit.prevent="handleSearch" class="relative" autocomplete="off">
        <input
          ref="inputRef"
          v-model="searchQuery"
          type="search"
          :placeholder="t.search.placeholder"
          class="w-full h-10 pl-3 pr-9 text-sm rounded-md border border-input bg-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
          @keydown="onKeydown"
          @focus="showDropdown = suggestions.length > 0"
        />
        <button
          v-if="!loading"
          type="submit"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
          :aria-label="t.search.ariaLabel"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="M21 21l-4.35-4.35" />
          </svg>
        </button>
        <span v-else class="absolute right-2 top-1/2 -translate-y-1/2">
          <svg class="w-4 h-4 animate-spin text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
        </span>

        <!-- Autocomplete dropdown (mobile) -->
        <div
          v-if="showDropdown"
          ref="dropdownRef"
          class="absolute top-full left-0 right-0 mt-1 rounded-md border border-border bg-card shadow-md z-50 overflow-hidden"
        >
          <button
            v-for="(s, i) in suggestions"
            :key="s.id"
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2 text-left text-sm transition-colors cursor-pointer"
            :class="i === activeIndex ? 'bg-primary/10 text-primary' : 'hover:bg-muted text-foreground'"
            @mousedown.prevent="selectSuggestion(s)"
          >
            <img v-if="s.photo" :src="s.photo" class="w-8 h-8 rounded object-cover shrink-0" alt="" />
            <span v-else class="w-8 h-8 rounded bg-muted shrink-0 flex items-center justify-center text-muted-foreground">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 20l6-9 4 5 3-4 5 8H3z"/>
              </svg>
            </span>
            <span class="flex-1 min-w-0">
              <span class="block truncate font-medium">{{ s.title }}</span>
              <span class="block text-xs text-muted-foreground">{{ s.duration_days }} {{ t.search.days }}</span>
            </span>
          </button>
          <button
            type="submit"
            class="w-full px-3 py-2 text-left text-xs text-primary hover:bg-muted border-t border-border transition-colors cursor-pointer"
          >
            «{{ searchQuery }}» — {{ t.search.showAll }}
          </button>
        </div>
      </form>
    </div>
  </header>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import { usePageContext } from 'vike-vue/usePageContext'
import { logout as apiLogout } from '@/api/admin.js'
import { t } from '@/i18n.js'

const pageContext = usePageContext()
const isLoginPage = computed(() => pageContext.urlPathname === '/admin/login')
const ready = ref(
  typeof window !== 'undefined'
    ? isLoginPage.value || !!localStorage.getItem('admin_token')
    : false
)

async function handleLogout() {
  try {
    await apiLogout()
  } catch {}
  localStorage.removeItem('admin_token')
  window.location.href = '/admin/login'
}

onMounted(() => {
  if (!isLoginPage.value && !localStorage.getItem('admin_token')) {
    window.location.replace('/admin/login')
  }
})
</script>

<template>
  <slot v-if="isLoginPage" />

  <div v-else-if="ready">
    <div class="border-b border-border bg-card overflow-x-auto">
      <div class="flex items-center justify-between gap-4 px-4 sm:px-6 py-3 min-w-max">
        <div class="flex items-center gap-4 sm:gap-6">
          <a href="/admin/tours" class="font-semibold text-foreground text-sm whitespace-nowrap">{{ t.admin.panel }}</a>
          <nav class="flex items-center gap-3 sm:gap-4 text-sm">
            <a
              href="/admin/tours"
              class="text-muted-foreground hover:text-foreground transition-colors whitespace-nowrap"
            >{{ t.admin.navTours }}</a>
            <a
              href="/admin/tours/create"
              class="text-muted-foreground hover:text-foreground transition-colors whitespace-nowrap"
            >{{ t.admin.navNewTour }}</a>
            <a
              href="/admin/settings"
              class="text-muted-foreground hover:text-foreground transition-colors whitespace-nowrap"
            >{{ t.admin.navSettings }}</a>
          </nav>
        </div>
        <button
          @click="handleLogout"
          class="text-sm text-muted-foreground hover:text-foreground transition-colors whitespace-nowrap"
        >
          {{ t.admin.logout }}
        </button>
      </div>
    </div>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import TheHeader from '@/components/TheHeader.vue'
import TheFooter from '@/components/TheFooter.vue'
import { loadTranslations } from '@/utils/translations.js'
import { loadSeoSettings } from '@/utils/seoSettings.js'
import { useTheme } from '@/composables/useTheme.js'

const { init } = useTheme()

onMounted(() => {
  init()
  loadTranslations()
  loadSeoSettings()
})
</script>

<template>
  <div class="flex flex-col min-h-screen">
    <TheHeader />
    <main class="flex-1">
      <slot />
    </main>
    <TheFooter />
  </div>
</template>

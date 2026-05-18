<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePageContext } from 'vike-vue/usePageContext'
import { getAdminTour } from '@/api/admin.js'
import TourForm from '@/components/admin/TourForm.vue'
import AppBreadcrumbs from '@/components/AppBreadcrumbs.vue'
import { t } from '@/i18n.js'

const pageContext = usePageContext()
const tour = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const { data } = await getAdminTour(pageContext.routeParams.slug)
    tour.value = data
  } catch {
    error.value = t.admin.tourEdit.notFound
  } finally {
    loading.value = false
  }
})

function onSaved() {
  window.history.back()
}
</script>

<template>
  <div>
    <AppBreadcrumbs :items="[
      { label: t.breadcrumbs.home, href: '/' },
      { label: t.breadcrumbs.admin, href: '/admin/tours' },
      { label: t.breadcrumbs.tours, href: '/admin/tours' },
      { label: tour?.title ?? '…' },
    ]" />
    <h1 class="text-2xl font-bold mb-6">{{ t.admin.tourEdit.title }}</h1>

    <div v-if="loading" class="text-muted-foreground text-sm py-8">{{ t.admin.tourEdit.loading }}</div>
    <div v-else-if="error" class="text-red-500 text-sm py-8">{{ error }}</div>
    <TourForm v-else :initial-tour="tour" @saved="onSaved" />
  </div>
</template>

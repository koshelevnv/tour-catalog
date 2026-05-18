<script setup>
import TourForm from '@/components/admin/TourForm.vue'
import AppBreadcrumbs from '@/components/AppBreadcrumbs.vue'
import { t } from '@/i18n.js'

function onSaved(slug) {
  window.location.href = `/tours/${slug}`
}
</script>

<template>
  <div>
    <AppBreadcrumbs :items="[
      { label: t.breadcrumbs.home, href: '/' },
      { label: t.breadcrumbs.admin, href: '/admin/tours' },
      { label: t.breadcrumbs.tours, href: '/admin/tours' },
      { label: t.breadcrumbs.newTour },
    ]" />
    <h1 class="text-2xl font-bold mb-6">{{ t.admin.tourCreate.title }}</h1>
    <TourForm @saved="onSaved" />
  </div>
</template>

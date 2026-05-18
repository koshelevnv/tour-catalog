<script setup>
import { ref, onMounted } from 'vue'
import { getTours, deleteTour } from '@/api/admin.js'
import { t, formatDuration } from '@/i18n.js'
import AppBreadcrumbs from '@/components/AppBreadcrumbs.vue'

const tours = ref([])
const loading = ref(true)
const deleting = ref(null)
const page = ref(1)
const lastPage = ref(1)

async function load() {
  loading.value = true
  try {
    const { data } = await getTours({ page: page.value })
    tours.value = data.data
    lastPage.value = data.meta.last_page
  } finally {
    loading.value = false
  }
}

async function handleDelete(tour) {
  if (!confirm(t.admin.tourList.confirmDelete(tour.title))) return
  deleting.value = tour.id
  try {
    await deleteTour(tour.id)
    await load()
  } finally {
    deleting.value = null
  }
}

function changePage(p) {
  page.value = p
  load()
}

onMounted(load)
</script>

<template>
  <div>
    <AppBreadcrumbs :items="[
      { label: t.breadcrumbs.home, href: '/' },
      { label: t.breadcrumbs.admin, href: '/admin/tours' },
      { label: t.breadcrumbs.tours },
    ]" />
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">{{ t.admin.tourList.title }}</h1>
      <a
        href="/admin/tours/create"
        class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 transition-colors"
      >
        {{ t.admin.tourList.newTour }}
      </a>
    </div>

    <div v-if="loading" class="text-muted-foreground text-sm py-8 text-center">{{ t.admin.tourList.loading }}</div>

    <template v-else>
      <div class="border border-border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-muted text-muted-foreground">
            <tr>
              <th class="text-left px-4 py-3 font-medium">{{ t.admin.tourList.colTitle }}</th>
              <th class="text-left px-4 py-3 font-medium hidden sm:table-cell">{{ t.admin.tourList.colType }}</th>
              <th class="text-left px-4 py-3 font-medium hidden sm:table-cell">{{ t.admin.tourList.colDays }}</th>
              <th class="text-left px-4 py-3 font-medium hidden md:table-cell">{{ t.admin.tourList.colPrice }}</th>
              <th class="px-4 py-3 w-32"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="tour in tours" :key="tour.id" class="hover:bg-muted/40 transition-colors">
              <td class="px-4 py-3 font-medium">
                <div>{{ tour.title }}</div>
                <div class="text-xs text-muted-foreground">{{ tour.slug }}</div>
              </td>
              <td class="px-4 py-3 text-muted-foreground hidden sm:table-cell">
                {{ tour.type?.name ?? '—' }}
              </td>
              <td class="px-4 py-3 text-muted-foreground hidden sm:table-cell">
                {{ formatDuration(tour.duration_min, tour.duration_max) ?? '—' }}
              </td>
              <td class="px-4 py-3 text-muted-foreground hidden md:table-cell">
                {{ tour.price_from ? `${Number(tour.price_from).toLocaleString('ru')} ₽` : '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2 justify-end">
                  <a
                    :href="`/admin/tours/${tour.slug}/edit`"
                    class="px-3 py-1 text-xs border border-border rounded hover:bg-muted transition-colors"
                  >
                    {{ t.admin.tourList.edit }}
                  </a>
                  <button
                    @click="handleDelete(tour)"
                    :disabled="deleting === tour.id"
                    class="px-3 py-1 text-xs text-red-600 border border-red-200 rounded hover:bg-red-50 dark:hover:bg-red-950 transition-colors disabled:opacity-50"
                  >
                    {{ t.admin.tourList.delete }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!tours.length">
              <td colspan="5" class="px-4 py-8 text-center text-muted-foreground text-sm">
                {{ t.admin.tourList.empty }}
                <a href="/admin/tours/create" class="text-primary underline ml-1">{{ t.admin.tourList.createFirst }}</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="lastPage > 1" class="flex gap-1 mt-4 justify-center">
        <button
          v-for="p in lastPage"
          :key="p"
          @click="changePage(p)"
          :class="[
            'px-3 py-1 text-sm rounded border transition-colors',
            p === page
              ? 'bg-primary text-primary-foreground border-primary'
              : 'border-border hover:bg-muted',
          ]"
        >
          {{ p }}
        </button>
      </div>
    </template>
  </div>
</template>

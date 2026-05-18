<script setup>
defineProps({
  items: { type: Array, required: true },
})
</script>

<template>
  <nav aria-label="breadcrumb" class="flex items-center flex-wrap gap-1 text-sm text-muted-foreground mb-6">
    <template v-for="(item, i) in items" :key="i">
      <a
        v-if="item.href"
        :href="item.href"
        class="hover:text-foreground transition-colors"
      >{{ item.label }}</a>
      <span v-else class="text-foreground font-medium truncate max-w-[130px] sm:max-w-[220px]" :title="item.label">{{ item.label }}</span>
      <svg
        v-if="i < items.length - 1"
        xmlns="http://www.w3.org/2000/svg"
        class="w-3.5 h-3.5 shrink-0 text-muted-foreground/50"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="m9 18 6-6-6-6" />
      </svg>
    </template>
  </nav>
</template>

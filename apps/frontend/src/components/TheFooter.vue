<script setup>
import { seo } from '@/utils/seoSettings.js'
</script>

<template>
  <footer class="border-t border-border bg-card">
    <div class="container mx-auto px-4 h-14 flex items-center justify-center">
      <p class="text-sm text-muted-foreground">© {{ new Date().getFullYear() }} {{ seo.meta_title }}. Все права защищены.</p>
    </div>
  </footer>
</template>

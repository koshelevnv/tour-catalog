<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  const token = localStorage.getItem('admin_token')
  window.location.replace(token ? '/admin/tours' : '/admin/login')
})
</script>

<template>
  <div />
</template>

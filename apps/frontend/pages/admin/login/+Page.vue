<script setup>
import { ref, onMounted } from 'vue'
import { login } from '@/api/admin.js'
import { t } from '@/i18n.js'

const email = ref('admin@example.com')
const password = ref('')
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

onMounted(() => {
  if (localStorage.getItem('admin_token')) {
    window.location.replace('/admin/tours')
  }
})

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await login(email.value, password.value)
    localStorage.setItem('admin_token', data.token)
    window.location.href = '/admin/tours'
  } catch {
    error.value = t.admin.login.error
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container mx-auto px-4 py-16 max-w-md">
    <div class="bg-card border border-border rounded-lg p-8 shadow-sm">
      <h1 class="text-2xl font-bold mb-6 text-center">{{ t.admin.login.title }}</h1>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="text-sm font-medium block mb-1">{{ t.admin.login.email }}</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full px-3 py-2 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">{{ t.admin.login.password }}</label>
          <div class="relative">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              class="w-full px-3 py-2 pr-10 rounded-md border border-input bg-background text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-muted-foreground hover:text-foreground transition-colors"
              tabindex="-1"
            >
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>
        <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full py-2 px-4 bg-primary text-primary-foreground rounded-md text-sm font-medium hover:bg-primary/90 transition-colors disabled:opacity-50"
        >
          {{ loading ? t.admin.login.submitting : t.admin.login.submit }}
        </button>
      </form>
    </div>
  </div>
</template>

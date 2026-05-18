import { ref } from 'vue'

const isDark = ref(true)

function applyTheme() {
  document.documentElement.classList.toggle('dark', isDark.value)
}

export function useTheme() {
  function init() {
    const stored = typeof window !== 'undefined' ? localStorage.getItem('theme') : null
    isDark.value = stored !== 'light'
    applyTheme()
  }

  function toggle() {
    isDark.value = !isDark.value
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    applyTheme()
  }

  return { isDark, toggle, init }
}

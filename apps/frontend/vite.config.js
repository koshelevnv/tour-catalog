import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vike from 'vike/plugin'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [
    vike(),
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: false,
    allowedHosts: 'all',
    proxy: {
      '/api':     { target: process.env.VITE_API_URL_SSR || 'http://backend:8000', changeOrigin: true },
      '/storage': { target: process.env.VITE_API_URL_SSR || 'http://backend:8000', changeOrigin: true },
    },
    watch: {
      usePolling: true,
      interval: 300,
    },
  },
})

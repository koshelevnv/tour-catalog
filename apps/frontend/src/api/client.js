import axios from 'axios'

const isServer = typeof window === 'undefined'
const baseURL = isServer
  ? (import.meta.env.VITE_API_URL_SSR ?? 'http://backend:8000')
  : (import.meta.env.VITE_API_URL ?? '')

const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = typeof window !== 'undefined' ? localStorage.getItem('admin_token') : null
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (
      error.response?.status === 401 &&
      typeof window !== 'undefined' &&
      !error.config?.url?.includes('/admin/login')
    ) {
      localStorage.removeItem('admin_token')
      window.location.href = '/admin/login'
    }
    return Promise.reject(error)
  },
)

export default api

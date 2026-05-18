export function useAuth() {
  const isAuthenticated = () =>
    typeof window !== 'undefined' && !!localStorage.getItem('admin_token')

  const setToken = (token) => localStorage.setItem('admin_token', token)

  const clearToken = () => localStorage.removeItem('admin_token')

  return { isAuthenticated, setToken, clearToken }
}

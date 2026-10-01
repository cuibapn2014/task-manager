import axios from 'axios'
import type { AxiosInstance } from 'axios'

/**
 * Central Axios instance.
 *
 * Base URL strategy:
 *   - In development, Vite proxies /api/* to http://localhost:8000 so we use
 *     a relative base URL (empty string). No CORS issue, no env var needed.
 *   - In production, set VITE_API_BASE_URL (e.g. https://api.example.com)
 *     and remove the Vite proxy block. The backend must then allow the
 *     frontend origin in config/cors.php.
 */
const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// ── Request interceptor — inject Bearer token ─────────────────────────────────
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ── Response interceptor — 401 → clear session and redirect to /login ─────────
http.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (
      axios.isAxiosError(error) &&
      error.response?.status === 401 &&
      window.location.pathname !== '/login'
    ) {
      localStorage.removeItem('token')
      // Avoid circular import: navigate directly instead of using the router.
      window.location.href = '/login'
    }
    return Promise.reject(error)
  },
)

export default http

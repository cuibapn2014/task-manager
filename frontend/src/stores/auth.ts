import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/authApi'
import { useTaskStore } from '@/stores/tasks'
import type { User } from '@/types/api'

export const useAuthStore = defineStore('auth', () => {
  // ── State ───────────────────────────────────────────────────────────────────
  const token = ref<string | null>(localStorage.getItem('token'))
  const user  = ref<User | null>(null)

  // ── Getters ─────────────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => token.value !== null)
  const isAdmin         = computed(() => user.value?.role === 'admin')

  // ── Actions ─────────────────────────────────────────────────────────────────

  /** Persist token to localStorage and state. */
  function _setToken(t: string): void {
    token.value = t
    localStorage.setItem('token', t)
  }

  /** Clear session from state and storage. */
  function _clearSession(): void {
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
    
    // Clear task filter state on logout
    const taskStore = useTaskStore()
    taskStore.clearFilters()
  }

  async function login(email: string, password: string): Promise<void> {
    const data  = await authApi.login({ email, password })
    _setToken(data.token)
    user.value = data.user
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      // Always clear locally even if the server call fails.
      _clearSession()
    }
  }

  /**
   * Called on every page load (before router guard runs).
   * If a token is in localStorage, validate it by calling /api/me.
   * On 401 the Axios interceptor in http.ts clears storage and redirects.
   */
  async function fetchMe(): Promise<void> {
    if (!token.value) return
    try {
      user.value = await authApi.me()
    } catch {
      _clearSession()
    }
  }

  return { token, user, isAuthenticated, isAdmin, login, logout, fetchMe }
})

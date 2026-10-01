import api from './api'
import type { AuthResponse, User } from '@/types'

export const authService = {
  async register(name: string, email: string, password: string, passwordConfirmation: string): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    })
    return data
  },

  async login(email: string, password: string): Promise<AuthResponse> {
    const { data } = await api.post<AuthResponse>('/login', { email, password })
    return data
  },

  async logout(): Promise<void> {
    await api.post('/logout')
  },

  async me(): Promise<User> {
    const { data } = await api.get<User>('/user')
    return data
  },
}

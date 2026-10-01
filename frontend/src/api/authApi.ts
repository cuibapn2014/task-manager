import http from './http'
import type { LoginPayload, LoginResponse, User } from '@/types/api'

export const authApi = {
  login(payload: LoginPayload): Promise<LoginResponse> {
    return http.post<LoginResponse>('/api/login', payload).then((r) => r.data)
  },

  logout(): Promise<void> {
    return http.post('/api/logout').then(() => undefined)
  },

  me(): Promise<User> {
    return http.get<User>('/api/me').then((r) => r.data)
  },
}

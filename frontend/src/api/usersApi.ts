import http from './http'
import type { User, Paginated } from '@/types/api'

export const usersApi = {
  /**
   * GET /api/users
   * Admin → all users; non-admin → only themselves.
   * Used to populate the assignee dropdown in TaskForm.
   */
  list(): Promise<User[]> {
    return http.get<Paginated<User>>('/api/users').then((r) => r.data.data)
  },
}

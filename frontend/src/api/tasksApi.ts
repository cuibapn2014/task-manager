import http from './http'
import type { Task, TaskFilters, TaskPayload, Paginated } from '@/types/api'

export const tasksApi = {
  /**
   * GET /api/tasks — server-side paginated + filtered list.
   * Returns Laravel Paginated<Task> wrapper.
   */
  list(filters: TaskFilters = {}): Promise<Paginated<Task>> {
    // Strip empty/undefined values so we don't pollute query string.
    const params: Record<string, string | number> = {}
    if (filters.page)        params.page        = filters.page
    if (filters.per_page)    params.per_page    = filters.per_page
    if (filters.status)      params.status      = filters.status
    if (filters.assigned_to) params.assigned_to = filters.assigned_to
    if (filters.search?.trim()) params.search   = filters.search.trim()

    return http.get<Paginated<Task>>('/api/tasks', { params }).then((r) => r.data)
  },

  /**
   * GET /api/tasks/:id — single task (flat object, no "data" wrapper).
   */
  get(id: number): Promise<Task> {
    return http.get<Task>(`/api/tasks/${id}`).then((r) => r.data)
  },

  /**
   * POST /api/tasks → 201 + Task (flat object).
   */
  create(payload: TaskPayload): Promise<Task> {
    return http.post<Task>('/api/tasks', payload).then((r) => r.data)
  },

  /**
   * PUT /api/tasks/:id → 200 + Task (flat object).
   */
  update(id: number, payload: Partial<TaskPayload>): Promise<Task> {
    return http.put<Task>(`/api/tasks/${id}`, payload).then((r) => r.data)
  },

  /**
   * DELETE /api/tasks/:id → 204 No Content.
   */
  remove(id: number): Promise<void> {
    return http.delete(`/api/tasks/${id}`).then(() => undefined)
  },
}

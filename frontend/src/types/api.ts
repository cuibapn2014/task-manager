/**
 * TypeScript types that mirror the Laravel API Resources exactly.
 *
 * Sources of truth:
 *   UserResource  → id, name, email, role
 *   TaskResource  → id, title, description, status, assigned_to, assignee?, due_date, created_at, updated_at
 *   Paginated<T>  → Laravel paginate() JSON shape
 */

// ── Enums ─────────────────────────────────────────────────────────────────────

export type Role = 'admin' | 'user'

export type TaskStatus = 'todo' | 'in_progress' | 'done'

// ── Resources ─────────────────────────────────────────────────────────────────

export interface User {
  id: number
  name: string
  email: string
  role: Role
}

export interface TaskAssignee {
  id: number
  name: string
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  assigned_to: number
  assignee?: TaskAssignee   // present when eager-loaded (all index/show calls do this)
  due_date: string | null   // YYYY-MM-DD or null
  created_at: string        // ISO 8601
  updated_at: string        // ISO 8601
}

// ── Request payloads ──────────────────────────────────────────────────────────

export interface LoginPayload {
  email: string
  password: string
}

export interface TaskPayload {
  title: string
  description?: string | null
  status?: TaskStatus
  assigned_to?: number
  due_date?: string | null   // YYYY-MM-DD sent to API
}

// ── Response wrappers ─────────────────────────────────────────────────────────

export interface LoginResponse {
  token: string
  user: User
}

/**
 * Laravel paginate() collection shape.
 * Collection endpoints (GET /api/tasks) wrap items in "data"
 * plus meta/links for pagination.
 */
export interface Paginated<T> {
  data: T[]
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    per_page: number
    to: number | null
    total: number
    path: string
  }
}

/**
 * Laravel validation error shape returned on 422.
 */
export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

// ── Query params for GET /api/tasks ──────────────────────────────────────────

export interface TaskFilters {
  page?: number
  per_page?: number
  status?: TaskStatus | ''
  assigned_to?: number
  search?: string
}

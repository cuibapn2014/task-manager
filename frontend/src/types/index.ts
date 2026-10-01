export type Role = 'admin' | 'user'
export type TaskStatus = 'todo' | 'in_progress' | 'done'

export interface User {
  id: number
  name: string
  email: string
  role: Role
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  assigned_to: number
  assignee?: { id: number; name: string }
  due_date: string | null
  created_at: string
  updated_at: string
}

export interface TaskPayload {
  title: string
  description?: string | null
  status?: TaskStatus
  assigned_to?: number
  due_date?: string | null
}

export interface AuthResponse {
  user: User
  token: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

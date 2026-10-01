import api from './api'
import type { Task, TaskPayload, User } from '@/types'

export const taskService = {
  async list(): Promise<Task[]> {
    const { data } = await api.get<{ data: Task[] }>('/tasks')
    return data.data
  },

  async get(id: number): Promise<Task> {
    const { data } = await api.get<{ data: Task }>(`/tasks/${id}`)
    return data.data
  },

  async create(payload: TaskPayload): Promise<Task> {
    const { data } = await api.post<{ data: Task }>('/tasks', payload)
    return data.data
  },

  async update(id: number, payload: Partial<TaskPayload>): Promise<Task> {
    const { data } = await api.put<{ data: Task }>(`/tasks/${id}`, payload)
    return data.data
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/tasks/${id}`)
  },
}

export const userService = {
  async list(): Promise<User[]> {
    const { data } = await api.get<{ data: User[] }>('/users')
    return data.data
  },
}

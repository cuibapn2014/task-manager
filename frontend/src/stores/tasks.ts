import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/tasksApi'
import type { Task, TaskFilters, TaskPayload, Paginated } from '@/types/api'

export const useTaskStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const pagination = ref<Paginated<Task>['meta'] | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchTasks(filters: TaskFilters = {}): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const res = await tasksApi.list(filters)
      tasks.value = res.data
      pagination.value = res.meta
    } catch (err: unknown) {
      error.value = 'Failed to load tasks.'
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  async function createTask(payload: TaskPayload): Promise<Task> {
    const task = await tasksApi.create(payload)
    tasks.value.unshift(task)
    return task
  }

  async function updateTask(id: number, payload: Partial<TaskPayload>): Promise<void> {
    const updated = await tasksApi.update(id, payload)
    const idx = tasks.value.findIndex((t) => t.id === id)
    if (idx !== -1) tasks.value[idx] = updated
  }

  async function deleteTask(id: number): Promise<void> {
    await tasksApi.remove(id)
    tasks.value = tasks.value.filter((t) => t.id !== id)
  }

  return { tasks, pagination, loading, error, fetchTasks, createTask, updateTask, deleteTask }
})

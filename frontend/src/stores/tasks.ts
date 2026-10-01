import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/tasksApi'
import type { Task, TaskFilters, TaskStatus, TaskPayload, Paginated } from '@/types/api'

export interface TaskFilterState {
  search: string
  status: TaskStatus | ''
  assignedTo: number | undefined
  page: number
}

const STORAGE_KEY = 'task_filters'

function loadSavedFilters(): TaskFilterState {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      const parsed = JSON.parse(raw)
      return {
        search: typeof parsed.search === 'string' ? parsed.search : '',
        status: typeof parsed.status === 'string' ? parsed.status : '',
        assignedTo: typeof parsed.assignedTo === 'number' ? parsed.assignedTo : undefined,
        page: typeof parsed.page === 'number' && parsed.page > 0 ? parsed.page : 1,
      }
    }
  } catch {
    // Ignore error
  }
  return { search: '', status: '', assignedTo: undefined, page: 1 }
}

export const useTaskStore = defineStore('tasks', () => {
  const tasks = ref<Task[]>([])
  const pagination = ref<Paginated<Task>['meta'] | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const filters = ref<TaskFilterState>(loadSavedFilters())

  function setFilters(newFilters: Partial<TaskFilterState>): void {
    filters.value = { ...filters.value, ...newFilters }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(filters.value))
  }

  function clearFilters(): void {
    filters.value = { search: '', status: '', assignedTo: undefined, page: 1 }
    localStorage.removeItem(STORAGE_KEY)
  }

  async function fetchTasks(customFilters?: TaskFilters): Promise<void> {
    loading.value = true
    error.value = null
    try {
      const activeFilters: TaskFilters = customFilters ?? {
        page: filters.value.page,
        status: filters.value.status || undefined,
        assigned_to: filters.value.assignedTo,
        search: filters.value.search || undefined,
      }
      const res = await tasksApi.list(activeFilters)
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

  return {
    tasks,
    pagination,
    loading,
    error,
    filters,
    setFilters,
    clearFilters,
    fetchTasks,
    createTask,
    updateTask,
    deleteTask,
  }
})

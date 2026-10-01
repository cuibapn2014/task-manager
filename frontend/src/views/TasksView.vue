<template>
  <v-app>
    <AppBar />

    <v-main>
      <v-container class="py-8" max-width="1100">
        <!-- Page header -->
        <v-row align="center" class="mb-4">
          <v-col>
            <h1 class="text-h5 font-weight-bold">
              {{ auth.isAdmin ? 'All Tasks' : 'My Tasks' }}
            </h1>
            <p class="text-medium-emphasis text-body-2 mt-1">
              {{ taskStore.pagination?.total ?? taskStore.tasks.length }} task{{ (taskStore.pagination?.total ?? taskStore.tasks.length) !== 1 ? 's' : '' }} found
            </p>
          </v-col>
          <v-col cols="auto">
            <v-btn
              color="primary"
              variant="flat"
              prepend-icon="mdi-plus"
              rounded="lg"
              @click="openCreate"
            >
              New Task
            </v-btn>
          </v-col>
        </v-row>

        <!-- Filters & Search Bar -->
        <v-row align="center" class="mb-4">
          <v-col cols="12" sm="5" md="4">
            <v-text-field
              v-model="searchQuery"
              placeholder="Search tasks..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </v-col>

          <v-col v-if="auth.isAdmin" cols="12" sm="4" md="3">
            <v-select
              v-model="assignedToFilter"
              :items="assigneeOptions"
              item-title="name"
              item-value="id"
              label="Assignee"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            />
          </v-col>

          <v-col cols="12" sm="12" md="5">
            <v-chip-group v-model="statusFilter" selected-class="text-primary" filter>
              <v-chip value="">All</v-chip>
              <v-chip value="todo">To Do</v-chip>
              <v-chip value="in_progress">In Progress</v-chip>
              <v-chip value="done">Done</v-chip>
            </v-chip-group>
          </v-col>
        </v-row>

        <!-- Loading -->
        <v-row v-if="taskStore.loading">
          <v-col class="text-center py-16">
            <v-progress-circular indeterminate color="primary" size="56" />
          </v-col>
        </v-row>

        <!-- Error -->
        <v-alert
          v-else-if="taskStore.error"
          type="error"
          variant="tonal"
          class="mb-4"
        >
          {{ taskStore.error }}
        </v-alert>

        <!-- Empty state -->
        <v-row v-else-if="taskStore.tasks.length === 0">
          <v-col class="text-center py-16">
            <v-icon icon="mdi-clipboard-text-outline" size="64" color="medium-emphasis" />
            <p class="text-h6 text-medium-emphasis mt-4">No tasks found</p>
            <p class="text-body-2 text-medium-emphasis">Try adjusting your filters or search terms.</p>
          </v-col>
        </v-row>

        <!-- Task cards -->
        <v-row v-else>
          <v-col
            v-for="task in taskStore.tasks"
            :key="task.id"
            cols="12"
            sm="6"
            lg="4"
          >
            <v-card
              rounded="xl"
              color="surface"
              :class="['task-card h-100', { 'task-done': task.status === 'done' }]"
              elevation="2"
            >
              <v-card-text>
                <div class="d-flex align-start justify-space-between mb-2">
                  <StatusChip :status="task.status" />
                  <div class="d-flex gap-1">
                    <v-btn
                      icon="mdi-pencil"
                      size="x-small"
                      variant="text"
                      @click="openEdit(task)"
                    />
                    <v-btn
                      icon="mdi-delete"
                      size="x-small"
                      variant="text"
                      color="error"
                      :loading="deletingId === task.id"
                      @click="confirmDelete(task)"
                    />
                  </div>
                </div>

                <p
                  class="text-subtitle-1 font-weight-medium mt-2 mb-1"
                  :class="{ 'text-decoration-line-through text-medium-emphasis': task.status === 'done' }"
                >
                  {{ task.title }}
                </p>

                <p
                  v-if="task.description"
                  class="text-body-2 text-medium-emphasis description-clamp"
                >
                  {{ task.description }}
                </p>

                <v-divider class="my-3" />

                <div class="d-flex align-center justify-space-between text-caption text-medium-emphasis">
                  <span v-if="task.assignee">
                    <v-icon icon="mdi-account" size="x-small" class="mr-1" />
                    {{ task.assignee.name }}
                  </span>
                  <span v-if="task.due_date" :class="isDue(task.due_date) ? 'text-error' : ''">
                    <v-icon icon="mdi-calendar" size="x-small" class="mr-1" />
                    {{ formatDate(task.due_date) }}
                  </span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Pagination -->
        <v-row v-if="taskStore.pagination && taskStore.pagination.last_page > 1" class="mt-6" justify="center">
          <v-col cols="auto">
            <v-pagination
              v-model="page"
              :length="taskStore.pagination.last_page"
              density="comfortable"
              rounded="lg"
            />
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Task dialog -->
    <TaskDialog v-model="dialogOpen" :task="editingTask" @saved="loadTasks" />

    <!-- Delete confirm dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card rounded="lg" color="surface">
        <v-card-title class="pt-5 px-6">Delete Task?</v-card-title>
        <v-card-text class="px-6">
          <p>Are you sure you want to delete <strong>{{ taskToDelete?.title }}</strong>? This cannot be undone.</p>
        </v-card-text>
        <v-card-actions class="px-6 pb-5">
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" :loading="!!deletingId" @click="executeDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTaskStore } from '@/stores/tasks'
import { useDebounce } from '@/composables/useDebounce'
import { usersApi } from '@/api/usersApi'
import AppBar from '@/components/AppBar.vue'
import StatusChip from '@/components/StatusChip.vue'
import TaskDialog from '@/components/TaskDialog.vue'
import type { Task, TaskStatus, User } from '@/types/api'

const auth      = useAuthStore()
const taskStore = useTaskStore()

// --- State (initialized from persistent store) ---
const searchQuery       = ref(taskStore.filters.search)
const debouncedSearch   = useDebounce(searchQuery, 300)
const statusFilter      = ref<TaskStatus | ''>(taskStore.filters.status)
const assignedToFilter = ref<number | undefined>(taskStore.filters.assignedTo)
const page              = ref(taskStore.filters.page)

const assigneeOptions   = ref<User[]>([])

// --- Load data ---
async function loadTasks(): Promise<void> {
  await taskStore.fetchTasks({
    page: page.value,
    status: statusFilter.value || undefined,
    assigned_to: assignedToFilter.value,
    search: debouncedSearch.value || undefined,
  })
}

onMounted(async () => {
  await loadTasks()
  if (auth.isAdmin) {
    try {
      assigneeOptions.value = await usersApi.list()
    } catch {
      // Non-critical
    }
  }
})

// --- Watchers for debounced search and filters (reset page on change) ---
watch([debouncedSearch, statusFilter, assignedToFilter], () => {
  if (page.value !== 1) {
    page.value = 1
  } else {
    taskStore.setFilters({
      search: debouncedSearch.value,
      status: statusFilter.value,
      assignedTo: assignedToFilter.value,
      page: 1,
    })
    loadTasks()
  }
})

watch(page, (newPage) => {
  taskStore.setFilters({
    search: debouncedSearch.value,
    status: statusFilter.value,
    assignedTo: assignedToFilter.value,
    page: newPage,
  })
  loadTasks()
})

// --- Dialogs ---
const dialogOpen  = ref(false)
const editingTask = ref<Task | null>(null)

function openCreate(): void {
  editingTask.value = null
  dialogOpen.value  = true
}

function openEdit(task: Task): void {
  editingTask.value = task
  dialogOpen.value  = true
}

// --- Delete ---
const deleteDialog  = ref(false)
const taskToDelete  = ref<Task | null>(null)
const deletingId    = ref<number | null>(null)

function confirmDelete(task: Task): void {
  taskToDelete.value = task
  deleteDialog.value = true
}

async function executeDelete(): Promise<void> {
  if (!taskToDelete.value) return
  deletingId.value = taskToDelete.value.id
  try {
    await taskStore.deleteTask(taskToDelete.value.id)
  } finally {
    deletingId.value   = null
    deleteDialog.value = false
    taskToDelete.value = null
  }
}

// --- Helpers ---
function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
}

function isDue(date: string): boolean {
  return new Date(date) < new Date()
}
</script>

<style scoped>
.task-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.task-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(108, 99, 255, 0.15) !important;
}
.task-done {
  opacity: 0.7;
}
.description-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

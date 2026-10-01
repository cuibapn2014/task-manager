<template>
  <v-dialog v-model="isOpen" max-width="560" persistent>
    <v-card rounded="lg" color="surface">
      <v-card-title class="pt-5 px-6 text-h6 font-weight-bold">
        {{ isEditing ? 'Edit Task' : 'New Task' }}
      </v-card-title>

      <v-card-text class="px-6">
        <v-form ref="formRef" @submit.prevent="submit">
          <!-- Title -->
          <v-text-field
            v-model="form.title"
            label="Title"
            :rules="[required]"
            variant="outlined"
            density="comfortable"
            class="mb-3"
          />

          <!-- Description -->
          <v-textarea
            v-model="form.description"
            label="Description (optional)"
            variant="outlined"
            density="comfortable"
            rows="3"
            auto-grow
            class="mb-3"
          />

          <v-row>
            <!-- Status -->
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.status"
                label="Status"
                :items="statusOptions"
                item-title="label"
                item-value="value"
                variant="outlined"
                density="comfortable"
              />
            </v-col>

            <!-- Due Date -->
            <v-col cols="12" sm="6">
              <v-text-field
                v-model="form.due_date"
                label="Due Date (optional)"
                type="date"
                variant="outlined"
                density="comfortable"
              />
            </v-col>
          </v-row>

          <!-- Assigned To (admin only) -->
          <v-select
            v-if="auth.isAdmin"
            v-model="form.assigned_to"
            label="Assign To"
            :items="users"
            item-title="name"
            item-value="id"
            :rules="[required]"
            variant="outlined"
            density="comfortable"
            class="mb-2"
          />

          <!-- Server error -->
          <v-alert
            v-if="serverError"
            type="error"
            variant="tonal"
            density="compact"
            class="mb-2"
          >
            {{ serverError }}
          </v-alert>
        </v-form>
      </v-card-text>

      <v-card-actions class="px-6 pb-5">
        <v-spacer />
        <v-btn variant="text" @click="close">Cancel</v-btn>
        <v-btn
          color="primary"
          variant="flat"
          :loading="saving"
          @click="submit"
        >
          {{ isEditing ? 'Save' : 'Create' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTaskStore } from '@/stores/tasks'
import { userService } from '@/services/taskService'
import type { Task, TaskStatus, User } from '@/types'

const props = defineProps<{
  modelValue: boolean
  task?: Task | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void
  (e: 'saved'): void
}>()

const auth      = useAuthStore()
const taskStore = useTaskStore()

const isOpen    = computed({ get: () => props.modelValue, set: (v) => emit('update:modelValue', v) })
const isEditing = computed(() => !!props.task)

const formRef    = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const saving     = ref(false)
const serverError = ref<string | null>(null)
const users      = ref<User[]>([])

const statusOptions: { label: string; value: TaskStatus }[] = [
  { label: 'To Do',       value: 'todo'        },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Done',        value: 'done'        },
]

const defaultForm = (): { title: string; description: string; status: TaskStatus; assigned_to: number | null; due_date: string } => ({
  title:       '',
  description: '',
  status:      'todo',
  assigned_to: auth.user?.id ?? null,
  due_date:    '',
})

const form = ref(defaultForm())

// Populate form when editing, reset when creating.
watch(isOpen, async (open) => {
  serverError.value = null
  if (!open) return

  form.value = props.task
    ? {
        title:       props.task.title,
        description: props.task.description ?? '',
        status:      props.task.status,
        assigned_to: props.task.assigned_to,
        due_date:    props.task.due_date ?? '',
      }
    : defaultForm()

  if (auth.isAdmin && users.value.length === 0) {
    users.value = await userService.list()
    // Set default assigned_to for new tasks
    if (!props.task && users.value.length > 0) {
      form.value.assigned_to = users.value[0].id
    }
  }
})

function required(v: unknown): boolean | string {
  return (v !== null && v !== undefined && String(v).trim().length > 0) || 'Required.'
}

async function submit(): Promise<void> {
  const { valid } = await formRef.value!.validate()
  if (!valid) return

  saving.value     = true
  serverError.value = null

  try {
    const payload = {
      title:       form.value.title,
      description: form.value.description || null,
      status:      form.value.status,
      due_date:    form.value.due_date || null,
      ...(auth.isAdmin && form.value.assigned_to !== null ? { assigned_to: form.value.assigned_to } : {}),
    }

    if (isEditing.value && props.task) {
      await taskStore.updateTask(props.task.id, payload)
    } else {
      await taskStore.createTask(payload)
    }
    emit('saved')
    close()
  } catch (err: unknown) {
    const axiosErr = err as { response?: { data?: { message?: string } } }
    serverError.value = axiosErr?.response?.data?.message ?? 'An error occurred.'
  } finally {
    saving.value = false
  }
}

function close(): void {
  isOpen.value = false
}
</script>

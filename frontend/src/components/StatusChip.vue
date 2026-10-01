<template>
  <v-chip
    :color="color"
    size="small"
    label
    class="font-weight-medium"
  >
    <v-icon :icon="icon" size="x-small" class="mr-1" />
    {{ label }}
  </v-chip>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { TaskStatus } from '@/types'

const props = defineProps<{ status: TaskStatus }>()

const map: Record<TaskStatus, { label: string; color: string; icon: string }> = {
  todo:        { label: 'To Do',       color: 'secondary', icon: 'mdi-circle-outline'    },
  in_progress: { label: 'In Progress', color: 'warning',   icon: 'mdi-progress-clock'    },
  done:        { label: 'Done',        color: 'success',   icon: 'mdi-check-circle'      },
}

const label  = computed(() => map[props.status].label)
const color  = computed(() => map[props.status].color)
const icon   = computed(() => map[props.status].icon)
</script>

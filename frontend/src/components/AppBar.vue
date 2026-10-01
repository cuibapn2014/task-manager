<template>
  <v-app-bar elevation="0" color="surface">
    <v-app-bar-title>
      <span class="font-weight-bold text-primary">Task</span
      ><span class="font-weight-bold">Manager</span>
    </v-app-bar-title>

    <template #append>
      <v-chip
        v-if="auth.user"
        class="mr-2"
        :color="auth.isAdmin ? 'primary' : 'secondary'"
        size="small"
        label
      >
        {{ auth.user.name }} · {{ auth.isAdmin ? 'Admin' : 'User' }}
      </v-chip>
      <v-btn
        variant="tonal"
        color="error"
        size="small"
        prepend-icon="mdi-logout"
        :loading="loggingOut"
        @click="handleLogout"
      >
        Logout
      </v-btn>
    </template>
  </v-app-bar>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const loggingOut = ref(false)

async function handleLogout(): Promise<void> {
  loggingOut.value = true
  await auth.logout()
  loggingOut.value = false
  router.push({ name: 'login' })
}
</script>

<template>
  <v-main class="auth-bg">
    <v-container class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="5" lg="4">
          <!-- Brand -->
          <div class="text-center mb-8">
            <v-icon icon="mdi-check-decagram" size="48" color="primary" />
            <h1 class="text-h4 font-weight-bold mt-2">
              <span class="text-primary">Task</span>Manager
            </h1>
            <p class="text-medium-emphasis mt-1">Sign in to your workspace</p>
          </div>

          <v-card rounded="xl" color="surface" elevation="8" class="pa-2">
            <v-card-text>
              <v-form ref="formRef" @submit.prevent="handleLogin">
                <v-text-field
                  v-model="email"
                  label="Email"
                  type="email"
                  prepend-inner-icon="mdi-email-outline"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required, emailRule]"
                  class="mb-3"
                  autocomplete="email"
                />
                <v-text-field
                  v-model="password"
                  label="Password"
                  :type="showPassword ? 'text' : 'password'"
                  prepend-inner-icon="mdi-lock-outline"
                  :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                  @click:append-inner="showPassword = !showPassword"
                  variant="outlined"
                  density="comfortable"
                  :rules="[required]"
                  class="mb-1"
                  autocomplete="current-password"
                />

                <v-alert
                  v-if="errorMsg"
                  type="error"
                  variant="tonal"
                  density="compact"
                  class="mb-3"
                >
                  {{ errorMsg }}
                </v-alert>

                <v-btn
                  type="submit"
                  color="primary"
                  variant="flat"
                  block
                  size="large"
                  :loading="loading"
                  class="mt-2"
                >
                  Sign In
                </v-btn>
              </v-form>
            </v-card-text>
          </v-card>


        </v-col>
      </v-row>
    </v-container>
  </v-main>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router  = useRouter()
const auth    = useAuthStore()

const formRef      = ref<{ validate: () => Promise<{ valid: boolean }> } | null>(null)
const email        = ref('')
const password     = ref('')
const showPassword = ref(false)
const loading      = ref(false)
const errorMsg     = ref<string | null>(null)

const required  = (v: string): boolean | string => v.trim().length > 0 || 'Required.'
const emailRule = (v: string): boolean | string => /\S+@\S+\.\S+/.test(v) || 'Invalid email.'

async function handleLogin(): Promise<void> {
  const { valid } = await formRef.value!.validate()
  if (!valid) return

  loading.value  = true
  errorMsg.value = null
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'tasks' })
  } catch (err: unknown) {
    const axiosErr = err as { response?: { data?: { message?: string } } }
    errorMsg.value = axiosErr?.response?.data?.message ?? 'Login failed.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-bg {
  background: radial-gradient(ellipse at top left, #1a1040 0%, #13131F 60%);
  min-height: 100vh;
}
</style>

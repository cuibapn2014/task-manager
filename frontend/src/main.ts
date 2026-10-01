import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import vuetify from './plugins/vuetify'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(vuetify)

// Re-hydrate user from stored token before the first navigation fires.
// fetchMe() is a no-op when no token exists.
const auth = useAuthStore()
auth.fetchMe().finally(() => {
  app.mount('#app')
})

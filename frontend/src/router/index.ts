import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      redirect: '/tasks',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresGuest: true },
    },
    {
      path: '/tasks',
      name: 'tasks',
      component: () => import('@/views/TasksView.vue'),
      meta: { requiresAuth: true },
    },
    // Catch-all → back to tasks
    {
      path: '/:pathMatch(.*)*',
      redirect: '/tasks',
    },
  ],
})

/**
 * Navigation guard:
 *   - requiresAuth  → redirect to /login if not authenticated
 *   - requiresGuest → redirect to /tasks if already authenticated
 *     (prevents authenticated users landing on /login)
 */
router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }
  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return { name: 'tasks' }
  }
})

export default router

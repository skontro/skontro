import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppView from '@/views/AppView.vue'
import DashboardView from '@/views/DashboardView.vue'
import CustomerListView from '@/views/customers/CustomerListView.vue'
import CustomerDetailView from '@/views/customers/CustomerDetailView.vue'
import CustomerFormView from '@/views/customers/CustomerFormView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: AppView,
      meta: { requiresAuth: true },
      children: [
        { path: '', name: 'app', component: DashboardView },
        { path: 'customers', name: 'customers', component: CustomerListView },
        { path: 'customers/new', name: 'customer-new', component: CustomerFormView },
        { path: 'customers/:id', name: 'customer-detail', component: CustomerDetailView },
        { path: 'customers/:id/edit', name: 'customer-edit', component: CustomerFormView },
      ],
    },
    { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: RegisterView, meta: { guestOnly: true } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // First navigation: hydrate from the session cookie (handles page refresh).
  if (auth.status === 'idle') {
    await auth.fetchUser()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'app' }
  }

  return true
})

export default router

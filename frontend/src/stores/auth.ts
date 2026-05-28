import { defineStore } from 'pinia'
import { http, ensureCsrf } from '@/lib/http'
import type {
  AuthStatus,
  LoginCredentials,
  RegisterPayload,
  Tenant,
  User,
} from '@/types/auth'

interface AuthState {
  user: User | null
  tenant: Tenant | null
  status: AuthStatus
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    tenant: null,
    status: 'idle',
  }),

  getters: {
    isAuthenticated: (state): boolean => state.status === 'authenticated',
  },

  actions: {
    /**
     * Hydrate auth state from the session cookie. Called by the router guard
     * on first navigation, so a page refresh restores the session.
     */
    async fetchUser(): Promise<void> {
      this.status = 'loading'
      try {
        const { data } = await http.get('/api/v1/me')
        this.user = data.data
        this.tenant = data.data.tenant
        this.status = 'authenticated'
      } catch {
        this.user = null
        this.tenant = null
        this.status = 'guest'
      }
    },

    async login(credentials: LoginCredentials): Promise<void> {
      await ensureCsrf()
      await http.post('/api/v1/login', credentials)
      await this.fetchUser()
    },

    async register(payload: RegisterPayload): Promise<void> {
      await ensureCsrf()
      await http.post('/api/v1/register', payload)
      await this.fetchUser()
    },

    async logout(): Promise<void> {
      await http.post('/api/v1/logout')
      this.user = null
      this.tenant = null
      this.status = 'guest'
    },
  },
})

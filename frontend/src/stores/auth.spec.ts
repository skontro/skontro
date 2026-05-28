import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from './auth'
import { http } from '@/lib/http'

vi.mock('@/lib/http', () => ({
  http: { get: vi.fn(), post: vi.fn() },
  ensureCsrf: vi.fn().mockResolvedValue(undefined),
}))

const mockedHttp = http as unknown as { get: ReturnType<typeof vi.fn>; post: ReturnType<typeof vi.fn> }

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchUser sets authenticated state on success', async () => {
    const user = { id: 'u-1', name: 'A', email: 'a@b.de', role: 'owner', tenant: { id: 't-1', name: 'T', slug: 't' } }
    mockedHttp.get.mockResolvedValueOnce({ data: { data: user } })

    const auth = useAuthStore()
    await auth.fetchUser()

    expect(auth.isAuthenticated).toBe(true)
    expect(auth.user?.email).toBe('a@b.de')
    expect(auth.tenant?.name).toBe('T')
  })

  it('fetchUser sets guest state on 401', async () => {
    mockedHttp.get.mockRejectedValueOnce({ response: { status: 401 } })

    const auth = useAuthStore()
    await auth.fetchUser()

    expect(auth.isAuthenticated).toBe(false)
    expect(auth.status).toBe('guest')
    expect(auth.user).toBeNull()
  })

  it('logout clears state', async () => {
    mockedHttp.post.mockResolvedValueOnce({})
    const auth = useAuthStore()
    auth.$patch({ status: 'authenticated' })

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
    expect(auth.user).toBeNull()
    expect(auth.tenant).toBeNull()
  })

  it('login hydrates the user after posting credentials', async () => {
    const user = { id: 'u-1', name: 'A', email: 'a@b.de', role: 'member', tenant: { id: 't-1', name: 'T', slug: 't' } }
    mockedHttp.post.mockResolvedValueOnce({})
    mockedHttp.get.mockResolvedValueOnce({ data: { data: user } })

    const auth = useAuthStore()
    await auth.login({ email: 'a@b.de', password: 'secret' })

    expect(mockedHttp.post).toHaveBeenCalledWith('/api/v1/login', { email: 'a@b.de', password: 'secret' })
    expect(auth.isAuthenticated).toBe(true)
  })
})

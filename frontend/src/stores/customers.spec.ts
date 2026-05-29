import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useCustomerStore } from './customers'
import { http } from '@/lib/http'

vi.mock('@/lib/http', () => ({
  http: { get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() },
  ensureCsrf: vi.fn().mockResolvedValue(undefined),
}))

const mocked = http as unknown as Record<'get' | 'post' | 'patch' | 'delete', ReturnType<typeof vi.fn>>

describe('customer store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList populates items and meta', async () => {
    mocked.get.mockResolvedValueOnce({
      data: { data: [{ id: 'c-1', contact_name: 'A' }], meta: { total: 1, per_page: 25, current_page: 1, last_page: 1 } },
    })

    const store = useCustomerStore()
    await store.fetchList()

    expect(store.items).toHaveLength(1)
    expect(store.meta?.total).toBe(1)
  })

  it('create posts the payload and returns the customer', async () => {
    mocked.post.mockResolvedValueOnce({ data: { data: { id: 'c-2', contact_name: 'New' } } })

    const store = useCustomerStore()
    const created = await store.create({ type: 'individual', contact_name: 'New' })

    expect(mocked.post).toHaveBeenCalledWith('/api/v1/customers', { type: 'individual', contact_name: 'New' })
    expect(created.id).toBe('c-2')
  })
})

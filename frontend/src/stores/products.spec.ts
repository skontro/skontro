import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useProductStore } from './products'
import { http } from '@/lib/http'

vi.mock('@/lib/http', () => ({
  http: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
  ensureCsrf: vi.fn().mockResolvedValue(undefined),
}))

const mocked = http as unknown as Record<'get' | 'post' | 'patch', ReturnType<typeof vi.fn>>

describe('product store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('create sends cents and returns the product', async () => {
    mocked.post.mockResolvedValueOnce({
      data: { data: { id: 'p-1', name: 'X', unit_price_cents: 1999 } },
    })
    const store = useProductStore()
    const created = await store.create({
      name: 'X',
      unit_price_cents: 1999,
      vat_rate: 19,
      unit: 'Stück',
    })
    expect(mocked.post).toHaveBeenCalledWith('/api/v1/products', {
      name: 'X',
      unit_price_cents: 1999,
      vat_rate: 19,
      unit: 'Stück',
    })
    expect(created.unit_price_cents).toBe(1999)
  })
})

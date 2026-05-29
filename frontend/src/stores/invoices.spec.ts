import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useInvoiceStore } from './invoices'
import { http } from '@/lib/http'

vi.mock('@/lib/http', () => ({
  http: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
  ensureCsrf: vi.fn().mockResolvedValue(undefined),
}))

const mocked = http as unknown as Record<'get' | 'post' | 'patch', ReturnType<typeof vi.fn>>

describe('invoice store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetchList populates items and meta', async () => {
    mocked.get.mockResolvedValueOnce({
      data: {
        data: [{ id: 'i-1', state: 'draft' }],
        meta: { total: 1, per_page: 25, current_page: 1, last_page: 1 },
      },
    })

    const store = useInvoiceStore()
    await store.fetchList()

    expect(store.items).toHaveLength(1)
    expect(store.meta?.total).toBe(1)
  })

  it('createDraft posts the payload and returns the invoice', async () => {
    mocked.post.mockResolvedValueOnce({ data: { data: { id: 'i-2', state: 'draft' } } })

    const store = useInvoiceStore()
    const created = await store.createDraft({
      customer_id: 'c-1',
      lines: [
        { description: 'X', quantity: '1', unit: 'Stück', unit_price_cents: 100, vat_rate: 19 },
      ],
    })

    expect(mocked.post).toHaveBeenCalledWith(
      '/api/v1/invoices',
      expect.objectContaining({ customer_id: 'c-1' })
    )
    expect(created.id).toBe('i-2')
  })

  it('issue posts to the issue action', async () => {
    mocked.post.mockResolvedValueOnce({
      data: { data: { id: 'i-3', state: 'issued', number: 'R-2026-00001' } },
    })

    const store = useInvoiceStore()
    const issued = await store.issue('i-3')

    expect(mocked.post).toHaveBeenCalledWith('/api/v1/invoices/i-3/issue')
    expect(issued.state).toBe('issued')
  })
})

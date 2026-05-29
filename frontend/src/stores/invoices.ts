import { defineStore } from 'pinia'
import { http, ensureCsrf } from '@/lib/http'
import type { DraftLineInput, Invoice } from '@/types/invoice'
import type { CustomerListMeta } from '@/types/customer'

interface InvoiceState {
  items: Invoice[]
  meta: CustomerListMeta | null
  current: Invoice | null
  loading: boolean
}

interface DraftPayload {
  customer_id: string
  invoice_date?: string
  payment_terms_days?: number
  notes_top?: string | null
  notes_bottom?: string | null
  lines: DraftLineInput[]
}

export const useInvoiceStore = defineStore('invoices', {
  state: (): InvoiceState => ({ items: [], meta: null, current: null, loading: false }),

  actions: {
    async fetchList(params: { state?: string; page?: number } = {}): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get('/api/v1/invoices', { params })
        this.items = data.data
        this.meta = data.meta
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id: string): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get(`/api/v1/invoices/${id}`)
        this.current = data.data
      } finally {
        this.loading = false
      }
    },

    async createDraft(payload: DraftPayload): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.post('/api/v1/invoices', payload)
      return data.data
    },

    async updateDraft(id: string, payload: Partial<DraftPayload>): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.patch(`/api/v1/invoices/${id}`, payload)
      return data.data
    },

    async issue(id: string): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.post(`/api/v1/invoices/${id}/issue`)
      return data.data
    },

    async send(id: string): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.post(`/api/v1/invoices/${id}/send`)
      return data.data
    },

    async recordPayment(
      id: string,
      payload: { amount_cents: number; payment_date: string; method: string; reference?: string }
    ): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.post(`/api/v1/invoices/${id}/payments`, payload)
      return data.data
    },

    async cancel(id: string, reason: string): Promise<Invoice> {
      await ensureCsrf()
      const { data } = await http.post(`/api/v1/invoices/${id}/cancel`, { reason })
      return data.data
    },
  },
})

import { defineStore } from 'pinia'
import { http, ensureCsrf } from '@/lib/http'
import type { Customer, CustomerFormData, CustomerListMeta } from '@/types/customer'

interface CustomerState {
  items: Customer[]
  meta: CustomerListMeta | null
  current: Customer | null
  loading: boolean
}

export const useCustomerStore = defineStore('customers', {
  state: (): CustomerState => ({
    items: [],
    meta: null,
    current: null,
    loading: false,
  }),

  actions: {
    async fetchList(params: { search?: string; page?: number; sort?: string } = {}): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get('/api/v1/customers', { params })
        this.items = data.data
        this.meta = data.meta
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id: string): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get(`/api/v1/customers/${id}`)
        this.current = data.data
      } finally {
        this.loading = false
      }
    },

    async create(payload: CustomerFormData): Promise<Customer> {
      await ensureCsrf()
      const { data } = await http.post('/api/v1/customers', payload)
      return data.data
    },

    async update(id: string, payload: Partial<CustomerFormData>): Promise<Customer> {
      await ensureCsrf()
      const { data } = await http.patch(`/api/v1/customers/${id}`, payload)
      return data.data
    },

    async remove(id: string): Promise<void> {
      await ensureCsrf()
      await http.delete(`/api/v1/customers/${id}`)
    },
  },
})

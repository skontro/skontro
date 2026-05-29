import { defineStore } from 'pinia'
import { http, ensureCsrf } from '@/lib/http'
import type { Product, ProductFormData, ProductListMeta } from '@/types/product'

interface ProductState {
  items: Product[]
  meta: ProductListMeta | null
  current: Product | null
  loading: boolean
}

export const useProductStore = defineStore('products', {
  state: (): ProductState => ({
    items: [],
    meta: null,
    current: null,
    loading: false,
  }),

  actions: {
    async fetchList(
      params: { search?: string; include_archived?: number; page?: number } = {}
    ): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get('/api/v1/products', { params })
        this.items = data.data
        this.meta = data.meta
      } finally {
        this.loading = false
      }
    },

    async fetchOne(id: string): Promise<void> {
      this.loading = true
      try {
        const { data } = await http.get(`/api/v1/products/${id}`)
        this.current = data.data
      } finally {
        this.loading = false
      }
    },

    async create(payload: ProductFormData): Promise<Product> {
      await ensureCsrf()
      const { data } = await http.post('/api/v1/products', payload)
      return data.data
    },

    async update(id: string, payload: Partial<ProductFormData>): Promise<Product> {
      await ensureCsrf()
      const { data } = await http.patch(`/api/v1/products/${id}`, payload)
      return data.data
    },

    async archive(id: string): Promise<void> {
      await ensureCsrf()
      await http.post(`/api/v1/products/${id}/archive`)
    },

    async unarchive(id: string): Promise<void> {
      await ensureCsrf()
      await http.post(`/api/v1/products/${id}/unarchive`)
    },
  },
})

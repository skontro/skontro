export type CustomerType = 'company' | 'individual'

export interface CustomerAddress {
  street: string | null
  postal_code: string | null
  city: string | null
  country_code: string | null
}

export interface Customer {
  id: string
  number: string
  type: CustomerType
  company_name: string | null
  contact_name: string
  email: string | null
  phone: string | null
  address: CustomerAddress
  vat_id: string | null
  payment_terms_days: number | null
  notes: string | null
  created_at: string
  updated_at: string
}

export interface CustomerListMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface CustomerFormData {
  type: CustomerType
  company_name?: string | null
  contact_name: string
  email?: string | null
  phone?: string | null
  street?: string | null
  postal_code?: string | null
  city?: string | null
  country_code?: string | null
  vat_id?: string | null
  payment_terms_days?: number | null
  notes?: string | null
}

export type VatRate = 19 | 7 | 0

export interface Product {
  id: string
  name: string
  description: string | null
  sku: string | null
  unit_price_cents: number
  unit_price_formatted: string
  vat_rate: VatRate
  unit: string
  unit_code: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ProductListMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ProductFormData {
  name: string
  description?: string | null
  sku?: string | null
  unit_price_cents: number
  vat_rate: VatRate
  unit: string
}

export const UNITS = [
  'Stück',
  'Stunde',
  'Kilogramm',
  'Meter',
  'Quadratmeter',
  'Tag',
  'pauschal',
] as const
export const VAT_RATES: VatRate[] = [19, 7, 0]

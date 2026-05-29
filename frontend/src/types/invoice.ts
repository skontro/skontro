export type InvoiceState = 'draft' | 'issued' | 'sent' | 'partially_paid' | 'paid' | 'cancelled'
export type VatRate = 19 | 7 | 0

export interface InvoiceLine {
  description: string
  quantity: string
  unit: string
  unit_code: string
  unit_price_cents: number
  unit_price_formatted: string
  vat_rate: VatRate
  line_net_cents: number
  line_vat_cents: number
  line_net_formatted: string
}

export interface Payment {
  id: string
  amount_cents: number
  amount_formatted: string
  payment_date: string
  method: string
  reference: string | null
}

export interface Invoice {
  id: string
  number: string | null
  state: InvoiceState
  customer: { id: string; contact_name: string; company_name: string | null } | null
  invoice_date: string
  due_date: string
  payment_terms_days: number
  notes_top: string | null
  notes_bottom: string | null
  lines: InvoiceLine[]
  payments: Payment[]
  subtotal_cents: number
  total_vat_cents: number
  total_cents: number
  total_formatted: string
  paid_cents?: number
  issued_at: string | null
  cancelled_at: string | null
  cancellation_reason: string | null
}

export interface DraftLineInput {
  product_id?: string | null
  description: string
  quantity: string
  unit: string
  unit_price_cents: number
  vat_rate: VatRate
}

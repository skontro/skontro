import { describe, it, expect } from 'vitest'
import { previewTotals } from './invoiceTotals'

describe('invoice totals preview', () => {
  it('computes line-level totals matching the backend', () => {
    const t = previewTotals([
      { description: 'A', quantity: '2', unit: 'Stunde', unit_price_cents: 10000, vat_rate: 19 },
      { description: 'B', quantity: '1', unit: 'Stück', unit_price_cents: 5000, vat_rate: 7 },
    ])
    expect(t.subtotalCents).toBe(25000)
    expect(t.totalVatCents).toBe(4150) // 3800 + 350
    expect(t.totalCents).toBe(29150)
  })

  it('rounds VAT per line (BR-CO-17), matching the server', () => {
    // Three lines net 50 each at 19%: each vat rounds to 10 -> 30 total,
    // diverging from document-level (150*0.19=28.5 -> 29).
    const t = previewTotals([
      { description: 'A', quantity: '1', unit: 'Stück', unit_price_cents: 50, vat_rate: 19 },
      { description: 'B', quantity: '1', unit: 'Stück', unit_price_cents: 50, vat_rate: 19 },
      { description: 'C', quantity: '1', unit: 'Stück', unit_price_cents: 50, vat_rate: 19 },
    ])
    expect(t.totalVatCents).toBe(30) // not 29 — matches the backend
  })

  it('handles decimal quantities', () => {
    const t = previewTotals([
      { description: 'A', quantity: '2.5', unit: 'Stunde', unit_price_cents: 8000, vat_rate: 19 },
    ])
    expect(t.subtotalCents).toBe(20000)
    expect(t.totalVatCents).toBe(3800)
  })
})

import type { DraftLineInput } from '@/types/invoice'

export interface TotalsPreview {
  subtotalCents: number
  totalVatCents: number
  totalCents: number
}

/**
 * Client-side preview of invoice totals, mirroring the backend's line-level
 * VAT rounding (EN 16931 BR-CO-17): round each line's VAT to whole cents, then
 * sum. This is a preview for the editor; the server recomputes authoritatively
 * on save. Both must use the same algorithm so the preview is honest.
 *
 * Quantity arrives as a decimal string; we parse to a number only for this
 * display calculation. The authoritative computation on the server stays in
 * BigDecimal. Rounding is half-up to match the backend.
 */
export function previewTotals(lines: DraftLineInput[]): TotalsPreview {
  let subtotal = 0
  let totalVat = 0

  for (const line of lines) {
    const qty = Number.parseFloat(line.quantity.replace(',', '.')) || 0
    const lineNet = Math.round(qty * line.unit_price_cents)
    const lineVat = Math.round((lineNet * line.vat_rate) / 100)
    subtotal += lineNet
    totalVat += lineVat
  }

  return { subtotalCents: subtotal, totalVatCents: totalVat, totalCents: subtotal + totalVat }
}

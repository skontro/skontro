/**
 * Parse a user-entered EUR string (comma or dot decimal) into integer cents.
 * The form sends cents to the API, never a float, mirroring the backend rule.
 */
export function eurosToCents(input: string): number {
  const normalized = input.replace(/\s|€/g, '').replace(',', '.')
  const value = Number.parseFloat(normalized)
  if (Number.isNaN(value)) return 0
  return Math.round(value * 100)
}

/**
 * Format integer cents as a German EUR string for display.
 */
export function centsToEuros(cents: number): string {
  return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(cents / 100)
}

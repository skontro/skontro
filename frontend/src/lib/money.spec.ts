import { describe, it, expect } from 'vitest'
import { eurosToCents, centsToEuros } from './money'

describe('money conversion', () => {
  it('parses euros to integer cents', () => {
    expect(eurosToCents('19,99')).toBe(1999)
    expect(eurosToCents('19.99')).toBe(1999)
    expect(eurosToCents('5')).toBe(500)
    expect(eurosToCents('0,01')).toBe(1)
    expect(eurosToCents('1.234,56'.replace('.', ''))).toBe(123456)
  })

  it('formats cents to a EUR string', () => {
    expect(centsToEuros(1999)).toContain('19,99')
    expect(centsToEuros(0)).toContain('0,00')
  })

  it('round-trips plain amounts losslessly', () => {
    for (const cents of [0, 1, 99, 1999, 250000]) {
      const bare = centsToEuros(cents).replace(/\s|€|\./g, '')
      expect(eurosToCents(bare)).toBe(cents)
    }
  })
})

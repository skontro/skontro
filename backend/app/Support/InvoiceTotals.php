<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Immutable result of calculating an invoice's totals. All amounts are integer
 * cents. The per-rate breakdown (rate => [net, vat]) is what ZUGFeRD/XRechnung
 * needs: EN 16931 requires one tax subtotal group per VAT rate.
 */
final readonly class InvoiceTotals
{
    /**
     * @param  int  $subtotalCents  sum of line nets (cents)
     * @param  int  $totalVatCents  sum of per-line rounded VATs (cents)
     * @param  int  $totalCents  subtotal + total VAT (cents)
     * @param  array<int, array{net: int, vat: int}>  $perRate  vat_rate => {net, vat}
     */
    public function __construct(
        public int $subtotalCents,
        public int $totalVatCents,
        public int $totalCents,
        public array $perRate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal_cents' => $this->subtotalCents,
            'total_vat_cents' => $this->totalVatCents,
            'total_cents' => $this->totalCents,
            'per_rate' => collect($this->perRate)
                ->map(fn (array $v, int $rate): array => [
                    'rate' => $rate,
                    'net_cents' => $v['net'],
                    'vat_cents' => $v['vat'],
                ])
                ->values()
                ->all(),
        ];
    }
}

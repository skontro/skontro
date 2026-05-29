<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\InvoiceLineInput;
use App\Support\InvoiceTotals;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Calculates invoice totals with line-level VAT rounding per EN 16931
 * BR-CO-17: VAT is computed and rounded per line, then summed. Computing VAT
 * on the document subtotal instead produces 1–2 cent differences on multi-rate
 * invoices that fail KoSIT validation. All arithmetic is BigDecimal; no PHP
 * float is ever involved. Rounding is HALF_UP to whole cents.
 */
class InvoiceCalculator
{
    /**
     * @param  list<InvoiceLineInput>  $lines
     */
    public function calculate(array $lines): InvoiceTotals
    {
        $subtotalCents = 0;
        $totalVatCents = 0;

        /** @var array<int, array{net: int, vat: int}> $perRate */
        $perRate = [];

        foreach ($lines as $line) {
            // line net in cents: quantity * unit_price_cents, rounded to whole
            // cents HALF_UP. Quantity is a decimal, so this stays in BigDecimal.
            $lineNetCents = BigDecimal::of($line->quantity)
                ->multipliedBy($line->unitPriceCents)
                ->toScale(0, RoundingMode::HALF_UP)
                ->toInt();

            // line VAT in cents: net * rate / 100, rounded to whole cents
            // HALF_UP. THIS per-line rounding is the crux of BR-CO-17.
            $lineVatCents = BigDecimal::of($lineNetCents)
                ->multipliedBy($line->vatRate)
                ->dividedBy(100, 0, RoundingMode::HALF_UP)
                ->toInt();

            $subtotalCents += $lineNetCents;
            $totalVatCents += $lineVatCents;

            $rate = $line->vatRate;
            $perRate[$rate] ??= ['net' => 0, 'vat' => 0];
            $perRate[$rate]['net'] += $lineNetCents;
            $perRate[$rate]['vat'] += $lineVatCents;
        }

        ksort($perRate); // deterministic ordering for output/XML

        return new InvoiceTotals(
            subtotalCents: $subtotalCents,
            totalVatCents: $totalVatCents,
            totalCents: $subtotalCents + $totalVatCents,
            perRate: $perRate,
        );
    }
}

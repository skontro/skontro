<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A single line's inputs for total calculation, decoupled from the Eloquent
 * model so the calculator is pure. Quantity is a decimal string (e.g. "2.5")
 * to stay out of floating point; unit price and the computed amounts are
 * integer cents.
 */
final readonly class InvoiceLineInput
{
    public function __construct(
        public string $quantity,       // decimal string, e.g. "2.5"
        public int $unitPriceCents,    // integer cents
        public int $vatRate,           // 19 | 7 | 0
    ) {}
}

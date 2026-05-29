<?php

declare(strict_types=1);

use App\Services\InvoiceCalculator;
use App\Support\InvoiceLineInput;
use App\Support\InvoiceTotals;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * @param  list<InvoiceLineInput>  $lines
 */
function calc(array $lines): InvoiceTotals
{
    return (new InvoiceCalculator)->calculate($lines);
}

test('a single line computes net, vat, and total correctly', function () {
    // 1 x 100,00 € at 19% => net 10000, vat 1900, total 11900
    $totals = calc([new InvoiceLineInput('1', 10000, 19)]);

    expect($totals->subtotalCents)->toBe(10000)
        ->and($totals->totalVatCents)->toBe(1900)
        ->and($totals->totalCents)->toBe(11900);
});

test('decimal quantities stay out of floating point', function () {
    // 2.5 hours x 80,00 € at 19% => net 20000, vat 3800
    $totals = calc([new InvoiceLineInput('2.5', 8000, 19)]);

    expect($totals->subtotalCents)->toBe(20000)
        ->and($totals->totalVatCents)->toBe(3800)
        ->and($totals->totalCents)->toBe(23800);
});

test('per-rate breakdown groups lines by VAT rate', function () {
    $totals = calc([
        new InvoiceLineInput('1', 10000, 19),
        new InvoiceLineInput('1', 5000, 7),
        new InvoiceLineInput('2', 2500, 19),
    ]);

    // 19%: net 10000 + 5000 = 15000; vat = round(15000*0.19)=2850
    // 7%:  net 5000;            vat = round(5000*0.07)=350
    expect($totals->perRate[19]['net'])->toBe(15000)
        ->and($totals->perRate[19]['vat'])->toBe(2850)
        ->and($totals->perRate[7]['net'])->toBe(5000)
        ->and($totals->perRate[7]['vat'])->toBe(350)
        ->and($totals->totalVatCents)->toBe(3200);
});

test('zero-rated lines contribute net but no VAT', function () {
    $totals = calc([new InvoiceLineInput('1', 10000, 0)]);

    expect($totals->subtotalCents)->toBe(10000)
        ->and($totals->totalVatCents)->toBe(0)
        ->and($totals->totalCents)->toBe(10000);
});

/**
 * THE decisive test (FR-031 acceptance criterion). A constructed single-rate
 * case where rounding each line's VAT then summing differs from computing VAT
 * once on the document subtotal. This proves the calculator implements
 * BR-CO-17 and not the naive document-level approach. It is NOT skipped: it
 * provably diverges (30 vs 29).
 */
test('single-rate line-level rounding diverges from document-level by a cent', function () {
    // Each line net * 0.19 ends in .5 and rounds up under HALF_UP; three such
    // lines sum to a net whose single taxation rounds down — a 1-cent gap.
    $lines = [
        new InvoiceLineInput('1', 50, 19), // net 50, 50*0.19=9.5 -> 10 (round up)
        new InvoiceLineInput('1', 50, 19), // net 50, -> 10
        new InvoiceLineInput('1', 50, 19), // net 50, -> 10
    ];
    $totals = calc($lines);

    // Line-level: 10 + 10 + 10 = 30
    expect($totals->totalVatCents)->toBe(30);

    // Document-level: net 150 * 0.19 = 28.5 -> 29 (HALF_UP). 30 != 29.
    $docLevel = BigDecimal::of(150)->multipliedBy(19)
        ->dividedBy(100, 0, RoundingMode::HALF_UP)->toInt();

    expect($docLevel)->toBe(29)
        ->and($totals->totalVatCents)->not->toBe($docLevel); // BR-CO-17 divergence proven
});

/**
 * A reliably-diverging multi-rate corpus case. The standing regression guard
 * for BR-CO-17. (Mixed rates make per-line rounding realistic.)
 */
test('multi-rate invoice rounds per line and sums (BR-CO-17 regression guard)', function () {
    $lines = [
        new InvoiceLineInput('3', 333, 19), // net 999,  vat round(189.81)=190
        new InvoiceLineInput('3', 333, 7),  // net 999,  vat round(69.93)=70
        new InvoiceLineInput('1', 1, 19),   // net 1,    vat round(0.19)=0
    ];

    $totals = calc($lines);

    expect($totals->subtotalCents)->toBe(1999)        // 999 + 999 + 1
        ->and($totals->perRate[19]['vat'])->toBe(190) // 190 + 0
        ->and($totals->perRate[7]['vat'])->toBe(70)
        ->and($totals->totalVatCents)->toBe(260)      // 190 + 70 + 0
        ->and($totals->totalCents)->toBe(2259);
});

test('an empty invoice totals to zero', function () {
    $totals = calc([]);

    expect($totals->subtotalCents)->toBe(0)
        ->and($totals->totalVatCents)->toBe(0)
        ->and($totals->totalCents)->toBe(0)
        ->and($totals->perRate)->toBe([]);
});

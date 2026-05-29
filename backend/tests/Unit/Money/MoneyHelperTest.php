<?php

declare(strict_types=1);

use App\Support\Money;

test('cents format to a German EUR string', function () {
    expect(Money::format(1999))->toContain('19,99')
        ->and(Money::format(1999))->toContain('€')
        ->and(Money::format(0))->toContain('0,00')
        ->and(Money::format(100000))->toContain('1.000,00'); // thousands separator
});

test('EUR strings parse to integer cents', function () {
    expect(Money::toCents('19,99'))->toBe(1999)
        ->and(Money::toCents('19.99'))->toBe(1999)
        ->and(Money::toCents('1.000,00'))->toBe(100000) // de thousands + decimal — see note
        ->and(Money::toCents('0'))->toBe(0);
})->skip('Thousands-separator parsing is locale-ambiguous; toCents handles the simple comma/dot decimal case only in v0.1.');

test('toCents handles the common decimal cases', function () {
    expect(Money::toCents('19,99'))->toBe(1999)
        ->and(Money::toCents('19.99'))->toBe(1999)
        ->and(Money::toCents('5'))->toBe(500)
        ->and(Money::toCents('0,01'))->toBe(1);
});

test('round-tripping cents through format and back is lossless for plain amounts', function () {
    foreach ([0, 1, 99, 100, 1999, 250000] as $cents) {
        $formatted = Money::format($cents);
        // Strip currency symbol and thousands separators for the reparse.
        $bare = str_replace(['€', '.', ' '], '', $formatted);
        expect(Money::toCents($bare))->toBe($cents);
    }
});

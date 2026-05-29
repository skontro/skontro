<?php

declare(strict_types=1);

use App\Enums\VatRate;

test('only the three German rates exist', function () {
    expect(collect(VatRate::cases())->map->value->all())
        ->toEqualCanonicalizing([19, 7, 0]);
});

test('multipliers are decimal strings, never floats', function () {
    foreach (VatRate::cases() as $rate) {
        expect($rate->multiplier())->toBeString();
    }
    expect(VatRate::Standard->multiplier())->toBe('0.19')
        ->and(VatRate::Reduced->multiplier())->toBe('0.07')
        ->and(VatRate::Zero->multiplier())->toBe('0.00');
});

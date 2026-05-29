<?php

declare(strict_types=1);

use App\Enums\Unit;

test('every unit maps to its UN/ECE Recommendation 20 code', function () {
    expect(Unit::Piece->uneceCode())->toBe('H87')
        ->and(Unit::Hour->uneceCode())->toBe('HUR')
        ->and(Unit::Kilogram->uneceCode())->toBe('KGM')
        ->and(Unit::Meter->uneceCode())->toBe('MTR')
        ->and(Unit::SquareMeter->uneceCode())->toBe('MTK')
        ->and(Unit::Day->uneceCode())->toBe('DAY')
        ->and(Unit::LumpSum->uneceCode())->toBe('LS');
});

test('there are exactly seven supported units', function () {
    expect(Unit::cases())->toHaveCount(7);
});

test('the backed value is the German label', function () {
    expect(Unit::Piece->value)->toBe('Stück')
        ->and(Unit::LumpSum->value)->toBe('pauschal');
});

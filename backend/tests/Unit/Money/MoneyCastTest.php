<?php

declare(strict_types=1);

use App\Casts\MoneyCast;
use Brick\Money\Money as BrickMoney;
use Illuminate\Database\Eloquent\Model;

test('the cast reads integer cents as a Money object', function () {
    $cast = new MoneyCast;
    $model = new class extends Model {};

    $money = $cast->get($model, 'price', 1999, []);

    expect($money)->toBeInstanceOf(BrickMoney::class)
        ->and($money->getMinorAmount()->toInt())->toBe(1999)
        ->and($money->getCurrency()->getCurrencyCode())->toBe('EUR');
});

test('the cast writes a Money object back as integer cents', function () {
    $cast = new MoneyCast;
    $model = new class extends Model {};

    $stored = $cast->set($model, 'price', BrickMoney::ofMinor(1999, 'EUR'), []);

    expect($stored)->toBe(1999)->toBeInt();
});

test('the cast accepts integer cents directly', function () {
    $cast = new MoneyCast;
    $model = new class extends Model {};

    expect($cast->set($model, 'price', 2500, []))->toBe(2500);
});

test('the cast rejects a float, refusing to silently coerce it', function () {
    $cast = new MoneyCast;
    $model = new class extends Model {};

    // Passing a float is the exact mistake FR-026 forbids; the cast must throw,
    // not round.
    expect(fn () => $cast->set($model, 'price', 19.99, []))
        ->toThrow(InvalidArgumentException::class);
});

<?php

declare(strict_types=1);

use App\Rules\VatId;

function validateVat(string $country, string $value): array
{
    $errors = [];
    $rule = new VatId($country);
    $rule->validate('vat_id', $value, function (string $message) use (&$errors): void {
        $errors[] = $message;
    });

    return $errors;
}

test('a well-formed German VAT ID with valid checksum passes', function () {
    // DE136695976 is a commonly cited valid example (Bundeszentralamt).
    expect(validateVat('DE', 'DE136695976'))->toBeEmpty();
});

test('a German VAT ID with a bad checksum fails', function () {
    expect(validateVat('DE', 'DE136695977'))->not->toBeEmpty();
});

test('a malformed German VAT ID fails on format', function () {
    expect(validateVat('DE', 'DE12345'))->not->toBeEmpty();
});

test('country-specific formats are enforced', function () {
    expect(validateVat('AT', 'ATU12345678'))->toBeEmpty()
        ->and(validateVat('AT', 'AT12345678'))->not->toBeEmpty() // missing U
        ->and(validateVat('FR', 'FRAB123456789'))->toBeEmpty()
        ->and(validateVat('NL', 'NL123456789B01'))->toBeEmpty();
});

test('whitespace is tolerated', function () {
    expect(validateVat('DE', 'DE 136 695 976'))->toBeEmpty();
});

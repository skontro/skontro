<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a VAT ID's format for an EU country (FR-021). The country is the
 * country_code of the customer being validated. v0.1 checks format only and
 * the DE checksum; live VIES verification is deferred to v0.2.
 */
class VatId implements ValidationRule
{
    /**
     * Per-country format patterns (anchored). Source: the EU VAT number
     * format specification. Only the countries FR-021 enumerates carry a
     * specific pattern; others fall back to a permissive country-prefixed
     * pattern so all 27 members are accepted at the format level.
     *
     * @var array<string, string>
     */
    private const PATTERNS = [
        'DE' => '/^DE\d{9}$/',
        'AT' => '/^ATU\d{8}$/',
        'FR' => '/^FR[A-Z0-9]{2}\d{9}$/',
        'NL' => '/^NL\d{9}B\d{2}$/',
        'IT' => '/^IT\d{11}$/',
        'ES' => '/^ES[A-Z0-9]\d{7}[A-Z0-9]$/',
        'PL' => '/^PL\d{10}$/',
        'BE' => '/^BE0\d{9}$/',
        'DK' => '/^DK\d{8}$/',
        'SE' => '/^SE\d{12}$/',
        'FI' => '/^FI\d{8}$/',
    ];

    public function __construct(private readonly string $countryCode) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return; // empty is handled by nullable/required elsewhere
        }

        $vat = strtoupper(str_replace(' ', '', $value));
        $country = strtoupper($this->countryCode);

        $pattern = self::PATTERNS[$country] ?? '/^'.preg_quote($country, '/').'[A-Z0-9]{2,12}$/';

        if (preg_match($pattern, $vat) !== 1) {
            $fail('The :attribute is not a valid VAT ID for the selected country.');

            return;
        }

        if ($country === 'DE' && ! $this->isValidGermanChecksum($vat)) {
            $fail('The :attribute has an invalid checksum.');
        }
    }

    /**
     * German USt-IdNr checksum (ISO 7064 MOD 11,10 variant as applied by the
     * Bundeszentralamt für Steuern to the 9 digits following "DE").
     */
    private function isValidGermanChecksum(string $vat): bool
    {
        $digits = substr($vat, 2); // strip "DE"
        if (strlen($digits) !== 9) {
            return false;
        }

        $product = 10;
        for ($i = 0; $i < 8; $i++) {
            $sum = ((int) $digits[$i] + $product) % 10;
            $sum = $sum === 0 ? 10 : $sum;
            $product = (2 * $sum) % 11;
        }

        $check = (11 - $product) % 10;

        return $check === (int) $digits[8];
    }
}

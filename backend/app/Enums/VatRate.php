<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * German VAT rates (FR-024). Stored as an integer percentage.
 *   19% — standard rate, §12 (1) UStG
 *    7% — reduced rate, §12 (2) UStG
 *    0% — exempt / zero-rated, §4 UStG
 * No other rates are accepted in v0.1. Reverse-charge (§13b UStG) is deferred
 * to v0.3.
 */
enum VatRate: int
{
    case Standard = 19;
    case Reduced = 7;
    case Zero = 0;

    public function label(): string
    {
        return match ($this) {
            self::Standard => '19% (Regelsatz)',
            self::Reduced => '7% (ermäßigt)',
            self::Zero => '0% (steuerfrei)',
        };
    }

    /**
     * The rate as a decimal multiplier, e.g. 0.19, for VAT computation. Returns
     * a string to be fed into brick/money's BigDecimal arithmetic without ever
     * touching a PHP float.
     */
    public function multiplier(): string
    {
        return match ($this) {
            self::Standard => '0.19',
            self::Reduced => '0.07',
            self::Zero => '0.00',
        };
    }
}

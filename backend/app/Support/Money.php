<?php

declare(strict_types=1);

namespace App\Support;

use Brick\Money\Money as BrickMoney;

/**
 * Money helpers over brick/money. The application stores integer cents
 * everywhere; this is the single place cents become a human EUR string and
 * back. No PHP float ever appears — brick/money uses arbitrary-precision
 * BigDecimal internally.
 */
final class Money
{
    /**
     * Format integer cents as a German EUR string, e.g. 1999 -> "19,99 €".
     */
    public static function format(int $cents): string
    {
        $money = BrickMoney::ofMinor($cents, 'EUR');

        // brick/money formats with the given locale; de_DE gives comma decimal
        // and a trailing euro sign.
        return $money->formatTo('de_DE');
    }

    /**
     * Parse a user-entered EUR amount (e.g. "19,99" or "19.99") into integer
     * cents. Accepts comma or dot as the decimal separator. Throws on garbage.
     */
    public static function toCents(string $amount): int
    {
        // de_DE currency formatting separates the amount from the euro sign with
        // a narrow no-break space (U+202F); strip every space variant and the
        // euro sign, then treat a comma as the decimal separator. This lets
        // toCents losslessly re-parse exactly what format() produces.
        $normalized = str_replace(
            [' ', "\u{00A0}", "\u{202F}", '€', ','],
            ['', '', '', '', '.'],
            trim($amount)
        );

        $money = BrickMoney::of($normalized, 'EUR');

        return $money->getMinorAmount()->toInt();
    }
}

<?php

declare(strict_types=1);

namespace App\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer-cents column to a brick/money Money (EUR) on read, and back
 * to integer cents on write. The database column is always an integer; the
 * model attribute is always a Money. No float crosses the boundary.
 *
 * The set type is intentionally `mixed`: the cast accepts whatever application
 * code assigns and validates it at runtime, rejecting a float rather than
 * silently coercing it (FR-026). Declaring a narrower set type would make
 * PHPStan treat that runtime guard as dead code.
 *
 * @implements CastsAttributes<Money, mixed>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::ofMinor((int) $value, 'EUR');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->getMinorAmount()->toInt();
        }

        // An integer (or integer-like) is treated as cents directly.
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        // Anything else is a programming error — fail loudly rather than
        // silently coercing a float (the very thing FR-026 forbids).
        throw new \InvalidArgumentException(
            'MoneyCast expects integer cents or a Brick\\Money\\Money instance, got '.get_debug_type($value)
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Supported units for catalog items (FR-025), each mapped to its UN/ECE
 * Recommendation 20 code. EN 16931 requires these codes on every invoice line,
 * so the mapping lives on the unit itself and invoicing reads uneceCode()
 * directly. The backed value is the German display label; uneceCode() is the
 * machine code emitted into ZUGFeRD/XRechnung.
 */
enum Unit: string
{
    case Piece = 'Stück';
    case Hour = 'Stunde';
    case Kilogram = 'Kilogramm';
    case Meter = 'Meter';
    case SquareMeter = 'Quadratmeter';
    case Day = 'Tag';
    case LumpSum = 'pauschal';

    /**
     * UN/ECE Recommendation 20 unit code, as required by EN 16931.
     */
    public function uneceCode(): string
    {
        return match ($this) {
            self::Piece => 'H87',
            self::Hour => 'HUR',
            self::Kilogram => 'KGM',
            self::Meter => 'MTR',
            self::SquareMeter => 'MTK',
            self::Day => 'DAY',
            self::LumpSum => 'LS',
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}

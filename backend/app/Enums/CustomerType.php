<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerType: string
{
    case Company = 'company';
    case Individual = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Company',
            self::Individual => 'Individual',
        };
    }

    public function requiresCompanyName(): bool
    {
        return $this === self::Company;
    }
}

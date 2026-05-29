<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case Customer = 'customer';
    case Invoice = 'invoice';
    case Expense = 'expense';

    /**
     * The single-letter prefix used in formatted document numbers, following
     * German convention: K for Kunde (customer), R for Rechnung (invoice),
     * E for expense.
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Customer => 'K',
            self::Invoice => 'R',
            self::Expense => 'E',
        };
    }
}

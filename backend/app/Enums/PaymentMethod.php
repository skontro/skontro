<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case SepaDirectDebit = 'sepa_direct_debit';
    case CreditCard = 'credit_card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank transfer',
            self::SepaDirectDebit => 'SEPA direct debit',
            self::CreditCard => 'Credit card',
            self::Other => 'Other',
        };
    }
}

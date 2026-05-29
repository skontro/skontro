<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceState: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Issued => 'Issued',
            self::Sent => 'Sent',
            self::PartiallyPaid => 'Partially paid',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether line items may still be edited. Only a draft is mutable; once
     * issued, the invoice is a legal document and its lines are frozen (FR-032,
     * FR-033).
     */
    public function allowsLineEditing(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Whether the invoice is in a terminal state (no further transitions).
     */
    public function isTerminal(): bool
    {
        return $this === self::Paid || $this === self::Cancelled;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceState;
use App\Exceptions\InvalidTransitionException;

/**
 * Defines and enforces the invoice lifecycle (FR-032):
 *
 *   draft     -> issued, cancelled
 *   issued    -> sent, partially_paid, paid, cancelled
 *   sent      -> partially_paid, paid, cancelled
 *   partially_paid -> partially_paid, paid, cancelled
 *   paid      -> (terminal)
 *   cancelled -> (terminal)
 *
 * Any non-paid, non-cancelled state may be cancelled. paid and cancelled are
 * terminal. Note partially_paid -> partially_paid is allowed: recording a
 * further partial payment that still does not settle the invoice is a legal
 * "transition" to the same state.
 */
class InvoiceStateMachine
{
    /**
     * @var array<string, list<InvoiceState>>
     */
    private const TRANSITIONS = [
        'draft' => [InvoiceState::Issued, InvoiceState::Cancelled],
        'issued' => [InvoiceState::Sent, InvoiceState::PartiallyPaid, InvoiceState::Paid, InvoiceState::Cancelled],
        'sent' => [InvoiceState::PartiallyPaid, InvoiceState::Paid, InvoiceState::Cancelled],
        'partially_paid' => [InvoiceState::PartiallyPaid, InvoiceState::Paid, InvoiceState::Cancelled],
        'paid' => [],
        'cancelled' => [],
    ];

    /**
     * @return list<InvoiceState>
     */
    public function allowedFrom(InvoiceState $state): array
    {
        return self::TRANSITIONS[$state->value];
    }

    public function canTransition(InvoiceState $from, InvoiceState $to): bool
    {
        return in_array($to, $this->allowedFrom($from), true);
    }

    /**
     * @throws InvalidTransitionException
     */
    public function assertCanTransition(InvoiceState $from, InvoiceState $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidTransitionException($from, $to);
        }
    }
}

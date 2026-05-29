<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\InvoiceState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Raised when an invoice state transition is not permitted (FR-032). Renders
 * as HTTP 409 Conflict with the current and attempted states, so the client
 * can show a meaningful message rather than a generic error.
 */
class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly InvoiceState $from,
        public readonly InvoiceState $to,
    ) {
        parent::__construct(
            sprintf('Cannot transition invoice from %s to %s.', $from->value, $to->value)
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'current_state' => $this->from->value,
            'attempted_state' => $this->to->value,
        ], Response::HTTP_CONFLICT);
    }
}

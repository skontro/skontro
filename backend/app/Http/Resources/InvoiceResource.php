<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'number' => $this->number,
            'state' => $this->state->value,
            'customer' => CustomerResource::make($this->whenLoaded('customer')),
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'payment_terms_days' => $this->payment_terms_days,
            'service_period_start' => $this->service_period_start?->toDateString(),
            'service_period_end' => $this->service_period_end?->toDateString(),
            'notes_top' => $this->notes_top,
            'notes_bottom' => $this->notes_bottom,
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'subtotal_cents' => $this->subtotal_cents,
            'total_vat_cents' => $this->total_vat_cents,
            'total_cents' => $this->total_cents,
            'total_formatted' => Money::format((int) $this->total_cents),
            'paid_cents' => $this->whenLoaded('payments', fn (): int => $this->paidCents()),
            'issued_at' => $this->issued_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

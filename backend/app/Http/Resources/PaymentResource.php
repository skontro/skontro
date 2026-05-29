<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'amount_cents' => $this->amount_cents,
            'amount_formatted' => Money::format((int) $this->amount_cents),
            'payment_date' => $this->payment_date?->toDateString(),
            'method' => $this->method->value,
            'reference' => $this->reference,
        ];
    }
}

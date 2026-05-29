<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid, // public identifier only; internal id never leaves
            'number' => $this->number,
            'type' => $this->type->value,
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'street' => $this->street,
                'postal_code' => $this->postal_code,
                'city' => $this->city,
                'country_code' => $this->country_code?->value,
            ],
            'vat_id' => $this->vat_id,
            'payment_terms_days' => $this->payment_terms_days,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

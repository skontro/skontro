<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            // Both representations: cents for the client to compute with, and a
            // ready-formatted EUR string for display. The client never does
            // money math on a float.
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price_formatted' => Money::format((int) $this->unit_price_cents),
            'vat_rate' => $this->vat_rate->value,
            'unit' => $this->unit->value,
            'unit_code' => $this->unit->uneceCode(), // handy for later invoice work
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

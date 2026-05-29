<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceLine;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceLine
 */
class InvoiceLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'quantity' => (string) $this->quantity,
            'unit' => $this->unit->value,
            'unit_code' => $this->unit->uneceCode(),
            'unit_price_cents' => $this->unit_price_cents,
            'unit_price_formatted' => Money::format((int) $this->unit_price_cents),
            'vat_rate' => $this->vat_rate->value,
            'line_net_cents' => $this->line_net_cents,
            'line_vat_cents' => $this->line_vat_cents,
            'line_net_formatted' => Money::format((int) $this->line_net_cents),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use App\Enums\Unit;
use App\Enums\VatRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The customer must belong to the current tenant. The exists rule
            // is scoped by tenant_id so a foreign customer uuid is rejected.
            'customer_id' => ['required', 'string'],
            'invoice_date' => ['nullable', 'date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'service_period_start' => ['nullable', 'date'],
            'service_period_end' => ['nullable', 'date', 'after_or_equal:service_period_start'],
            'notes_top' => ['nullable', 'string', 'max:5000'],
            'notes_bottom' => ['nullable', 'string', 'max:5000'],

            // At least one line (FR-029).
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'string'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit' => ['required', new Enum(Unit::class)],
            'lines.*.unit_price_cents' => ['required', 'integer', 'min:0'],
            'lines.*.vat_rate' => ['required', new Enum(VatRate::class)],
        ];
    }
}

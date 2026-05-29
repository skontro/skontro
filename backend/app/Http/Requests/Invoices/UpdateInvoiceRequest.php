<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use App\Enums\Unit;
use App\Enums\VatRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateInvoiceRequest extends FormRequest
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
            'customer_id' => ['sometimes', 'string'],
            'invoice_date' => ['sometimes', 'date'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'service_period_start' => ['nullable', 'date'],
            'service_period_end' => ['nullable', 'date', 'after_or_equal:service_period_start'],
            'notes_top' => ['nullable', 'string', 'max:5000'],
            'notes_bottom' => ['nullable', 'string', 'max:5000'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'string'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:500'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'gt:0'],
            'lines.*.unit' => ['required_with:lines', new Enum(Unit::class)],
            'lines.*.unit_price_cents' => ['required_with:lines', 'integer', 'min:0'],
            'lines.*.vat_rate' => ['required_with:lines', new Enum(VatRate::class)],
        ];
    }
}

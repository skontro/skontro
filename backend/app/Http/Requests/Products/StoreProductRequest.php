<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\Unit;
use App\Enums\VatRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware (role:admin) handles authorization
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:64'],
            // Price arrives as integer cents from the client. The frontend
            // converts the EUR field to cents before sending, so the API
            // contract is unambiguous and never sees a float.
            'unit_price_cents' => ['required', 'integer', 'min:0'],
            'vat_rate' => ['required', new Enum(VatRate::class)],
            'unit' => ['required', new Enum(Unit::class)],
        ];
    }
}

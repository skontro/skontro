<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\Unit;
use App\Enums\VatRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:64'],
            'unit_price_cents' => ['sometimes', 'integer', 'min:0'],
            'vat_rate' => ['sometimes', new Enum(VatRate::class)],
            'unit' => ['sometimes', new Enum(Unit::class)],
            // is_active is NOT settable here — archiving is an explicit action
            // via the archive/unarchive endpoints, not a silent field update.
        ];
    }
}

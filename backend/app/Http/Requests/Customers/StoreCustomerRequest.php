<?php

declare(strict_types=1);

namespace App\Http\Requests\Customers;

use App\Enums\Country;
use App\Enums\CustomerType;
use App\Rules\VatId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
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
            'type' => ['required', new Enum(CustomerType::class)],
            'company_name' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn (): bool => $this->input('type') === CustomerType::Company->value),
            ],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', new Enum(Country::class)],
            'vat_id' => ['nullable', 'string', 'max:30', new VatId((string) $this->input('country_code', 'DE'))],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

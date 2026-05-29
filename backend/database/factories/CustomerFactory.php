<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Country;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCompany = $this->faker->boolean(70);

        return [
            'uuid' => (string) Str::uuid(),
            // Respect a bound tenant context (the way ResolveTenant binds it at
            // runtime, and actAsTenant() does in tests) so a customer created
            // without an explicit tenant lands in the current one; fall back to
            // a fresh tenant when no context is bound. forTenant() overrides.
            'tenant_id' => app()->bound('currentTenantId')
                ? app('currentTenantId')
                : Tenant::factory(),
            'number' => 'K-2026-'.str_pad((string) $this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'type' => $isCompany ? CustomerType::Company->value : CustomerType::Individual->value,
            'company_name' => $isCompany ? $this->faker->company().' '.$this->faker->randomElement(['GmbH', 'AG', 'KG', 'GbR']) : null,
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'street' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'country_code' => Country::Germany->value,
            'vat_id' => null,
            'payment_terms_days' => $this->faker->randomElement([null, 14, 30]),
            'notes' => null,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (): array => [
            'type' => CustomerType::Individual->value,
            'company_name' => null,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (): array => [
            'type' => CustomerType::Company->value,
            'company_name' => $this->faker->company().' GmbH',
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }
}

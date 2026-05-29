<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Unit;
use App\Enums\VatRate;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->randomElement([
                'Beratungsstunde', 'Webentwicklung', 'Projektmanagement',
                'Wartungspauschale', 'Schulung', 'Lizenz (jährlich)',
            ]),
            'description' => $this->faker->optional()->sentence(),
            'sku' => $this->faker->optional()->bothify('SKU-####'),
            'unit_price_cents' => $this->faker->numberBetween(500, 50000),
            'vat_rate' => $this->faker->randomElement([VatRate::Standard->value, VatRate::Reduced->value, VatRate::Zero->value]),
            'unit' => $this->faker->randomElement(array_map(fn (Unit $u) => $u->value, Unit::cases())),
            'is_active' => true,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function service(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Beratungsstunde',
            'unit' => Unit::Hour->value,
            'vat_rate' => VatRate::Standard->value,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }
}

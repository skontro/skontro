<?php

declare(strict_types=1);

namespace Tests\Support\Models;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantScopedTestModel>
 */
class TenantScopedTestModelFactory extends Factory
{
    protected $model = TenantScopedTestModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'label' => $this->faker->word(),
        ];
    }
}

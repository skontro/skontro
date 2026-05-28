<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => Role::Member->value,
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => ['role' => Role::Owner->value]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => Role::Admin->value]);
    }

    public function member(): static
    {
        return $this->state(fn (): array => ['role' => Role::Member->value]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }
}

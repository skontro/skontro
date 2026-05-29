<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceState;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenant = Tenant::factory();

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => $tenant,
            'customer_id' => Customer::factory(),
            'number' => null,
            'state' => InvoiceState::Draft->value,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'payment_terms_days' => 14,
            'notes_top' => null,
            'notes_bottom' => null,
            'subtotal_cents' => 0,
            'total_vat_cents' => 0,
            'total_cents' => 0,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => ['tenant_id' => $tenant->id]);
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'state' => InvoiceState::Issued->value,
            'number' => 'R-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'issued_at' => now(),
        ]);
    }
}

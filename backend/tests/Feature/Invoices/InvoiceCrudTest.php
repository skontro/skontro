<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function adminWithTenantContext(): User
{
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);

    return $admin;
}

test('an admin creates a draft invoice with lines and computed totals', function () {
    $admin = adminWithTenantContext();
    $customer = Customer::factory()->forTenant($admin->tenant)->create();

    $response = actingAs($admin)->postJson('/api/v1/invoices', [
        'customer_id' => $customer->uuid,
        'lines' => [
            ['description' => 'Consulting', 'quantity' => 2, 'unit' => 'Stunde', 'unit_price_cents' => 10000, 'vat_rate' => 19],
            ['description' => 'Booklet', 'quantity' => 1, 'unit' => 'Stück', 'unit_price_cents' => 5000, 'vat_rate' => 7],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.state', 'draft')
        ->assertJsonPath('data.subtotal_cents', 25000)   // 20000 + 5000
        ->assertJsonPath('data.total_vat_cents', 4150)   // 3800 + 350
        ->assertJsonPath('data.total_cents', 29150);

    // A draft has no number yet.
    expect($response->json('data.number'))->toBeNull();
});

test('creating an invoice requires at least one line', function () {
    $admin = adminWithTenantContext();
    $customer = Customer::factory()->forTenant($admin->tenant)->create();

    actingAs($admin)->postJson('/api/v1/invoices', [
        'customer_id' => $customer->uuid,
        'lines' => [],
    ])->assertStatus(422)->assertJsonValidationErrors('lines');
});

test('a member cannot create an invoice', function () {
    $member = User::factory()->member()->create();
    $customer = Customer::factory()->forTenant($member->tenant)->create();

    actingAs($member)->postJson('/api/v1/invoices', [
        'customer_id' => $customer->uuid,
        'lines' => [['description' => 'X', 'quantity' => 1, 'unit' => 'Stück', 'unit_price_cents' => 100, 'vat_rate' => 19]],
    ])->assertForbidden();
});

test('due date is derived from payment-terms precedence', function () {
    $admin = adminWithTenantContext();
    $customer = Customer::factory()->forTenant($admin->tenant)->create(['payment_terms_days' => 30]);

    // No invoice-level terms => customer's 30 days.
    $response = actingAs($admin)->postJson('/api/v1/invoices', [
        'customer_id' => $customer->uuid,
        'invoice_date' => '2026-01-01',
        'lines' => [['description' => 'X', 'quantity' => 1, 'unit' => 'Stück', 'unit_price_cents' => 100, 'vat_rate' => 19]],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.payment_terms_days', 30)
        ->assertJsonPath('data.due_date', '2026-01-31');
});

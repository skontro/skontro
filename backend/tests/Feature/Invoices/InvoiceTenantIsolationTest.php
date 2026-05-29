<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('requesting another tenant\'s invoice returns 404, not 403', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $foreign = Invoice::factory()->forTenant($other->tenant)->create([
        'customer_id' => Customer::factory()->forTenant($other->tenant)->create()->id,
    ]);

    actingAs($admin)->getJson("/api/v1/invoices/{$foreign->uuid}")->assertNotFound();
});

test('you cannot issue another tenant\'s invoice', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $foreign = Invoice::factory()->forTenant($other->tenant)->create([
        'customer_id' => Customer::factory()->forTenant($other->tenant)->create()->id,
    ]);

    actingAs($admin)->postJson("/api/v1/invoices/{$foreign->uuid}/issue")->assertNotFound();
});

test('the list never includes another tenant\'s invoices', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    Invoice::factory()->forTenant($admin->tenant)->count(2)->create([
        'customer_id' => Customer::factory()->forTenant($admin->tenant)->create()->id,
    ]);
    Invoice::factory()->forTenant($other->tenant)->count(4)->create([
        'customer_id' => Customer::factory()->forTenant($other->tenant)->create()->id,
    ]);

    actingAs($admin)->getJson('/api/v1/invoices')->assertJsonCount(2, 'data');
});

test('you cannot attach another tenant\'s customer to your invoice', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $foreignCustomer = Customer::factory()->forTenant($other->tenant)->create();

    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);

    // The foreign customer uuid is not found within the admin's tenant scope.
    actingAs($admin)->postJson('/api/v1/invoices', [
        'customer_id' => $foreignCustomer->uuid,
        'lines' => [['description' => 'X', 'quantity' => 1, 'unit' => 'Stück', 'unit_price_cents' => 100, 'vat_rate' => 19]],
    ])->assertNotFound();
});

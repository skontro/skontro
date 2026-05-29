<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('an admin can create a customer and it gets a sequential number', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/api/v1/customers', [
        'type' => 'company',
        'company_name' => 'Müller Bau GmbH',
        'contact_name' => 'Angela Müller',
        'email' => 'angela@mueller-bau.de',
        'country_code' => 'DE',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.company_name', 'Müller Bau GmbH')
        ->assertJsonPath('data.number', 'K-'.now()->format('Y').'-00001');

    expect($response->json('data'))->not->toHaveKey('tenant_id'); // internal field not exposed
});

test('a member cannot create a customer', function () {
    $member = User::factory()->member()->create();

    actingAs($member)->postJson('/api/v1/customers', [
        'type' => 'individual',
        'contact_name' => 'Test',
    ])->assertForbidden();
});

test('a company customer requires a company name', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)->postJson('/api/v1/customers', [
        'type' => 'company',
        'contact_name' => 'Someone',
    ])->assertStatus(422)->assertJsonValidationErrors('company_name');
});

test('an invalid VAT id is rejected', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)->postJson('/api/v1/customers', [
        'type' => 'company',
        'company_name' => 'Test GmbH',
        'contact_name' => 'Someone',
        'country_code' => 'DE',
        'vat_id' => 'DE000000000',
    ])->assertStatus(422)->assertJsonValidationErrors('vat_id');
});

test('the list is paginated and searchable', function () {
    $admin = User::factory()->admin()->create();
    actingAs($admin);

    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);

    Customer::factory()->forTenant($admin->tenant)->count(30)->create();
    Customer::factory()->forTenant($admin->tenant)->create(['contact_name' => 'Findable Person', 'number' => 'K-2026-99999']);

    actingAs($admin)->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 25)
        ->assertJsonCount(25, 'data');

    actingAs($admin)->getJson('/api/v1/customers?search=Findable')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.contact_name', 'Findable Person');
});

test('the customer number is immutable on update', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $customer = Customer::factory()->forTenant($admin->tenant)->create(['number' => 'K-2026-00001']);

    actingAs($admin)->patchJson("/api/v1/customers/{$customer->uuid}", [
        'number' => 'K-2026-09999',
        'contact_name' => 'Renamed',
    ])->assertOk()->assertJsonPath('data.contact_name', 'Renamed');

    // Number unchanged despite being submitted.
    expect($customer->fresh()->number)->toBe('K-2026-00001');
});

test('soft delete removes from list but restore brings it back', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $customer = Customer::factory()->forTenant($admin->tenant)->create();

    actingAs($admin)->deleteJson("/api/v1/customers/{$customer->uuid}")->assertNoContent();
    actingAs($admin)->getJson('/api/v1/customers')->assertJsonCount(0, 'data');

    actingAs($admin)->postJson("/api/v1/customers/{$customer->uuid}/restore")->assertOk();
    actingAs($admin)->getJson('/api/v1/customers')->assertJsonCount(1, 'data');
});

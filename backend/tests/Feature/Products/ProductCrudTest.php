<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('an admin can create a product', function () {
    $admin = User::factory()->admin()->create();

    $response = actingAs($admin)->postJson('/api/v1/products', [
        'name' => 'Beratungsstunde',
        'unit_price_cents' => 12000,
        'vat_rate' => 19,
        'unit' => 'Stunde',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Beratungsstunde')
        ->assertJsonPath('data.unit_price_cents', 12000)
        ->assertJsonPath('data.unit_code', 'HUR'); // UN/ECE code surfaced

    expect($response->json('data.unit_price_formatted'))->toContain('120,00');
});

test('a member cannot create a product', function () {
    $member = User::factory()->member()->create();

    actingAs($member)->postJson('/api/v1/products', [
        'name' => 'X', 'unit_price_cents' => 100, 'vat_rate' => 19, 'unit' => 'Stück',
    ])->assertForbidden();
});

test('an invalid VAT rate is rejected', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)->postJson('/api/v1/products', [
        'name' => 'X', 'unit_price_cents' => 100, 'vat_rate' => 5, 'unit' => 'Stück',
    ])->assertStatus(422)->assertJsonValidationErrors('vat_rate');
});

test('an unknown unit is rejected', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)->postJson('/api/v1/products', [
        'name' => 'X', 'unit_price_cents' => 100, 'vat_rate' => 19, 'unit' => 'Furlong',
    ])->assertStatus(422)->assertJsonValidationErrors('unit');
});

test('the list excludes archived products by default and includes them on request', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);

    Product::factory()->forTenant($admin->tenant)->count(2)->create();
    Product::factory()->forTenant($admin->tenant)->archived()->create();

    actingAs($admin)->getJson('/api/v1/products')->assertJsonCount(2, 'data');
    actingAs($admin)->getJson('/api/v1/products?include_archived=1')->assertJsonCount(3, 'data');
});

test('archive and unarchive toggle visibility', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $product = Product::factory()->forTenant($admin->tenant)->create();

    actingAs($admin)->postJson("/api/v1/products/{$product->uuid}/archive")
        ->assertOk()->assertJsonPath('data.is_active', false);
    actingAs($admin)->getJson('/api/v1/products')->assertJsonCount(0, 'data');

    actingAs($admin)->postJson("/api/v1/products/{$product->uuid}/unarchive")
        ->assertOk()->assertJsonPath('data.is_active', true);
    actingAs($admin)->getJson('/api/v1/products')->assertJsonCount(1, 'data');
});

test('there is no delete endpoint for products', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $product = Product::factory()->forTenant($admin->tenant)->create();

    // Products archive, never delete — the route does not exist.
    actingAs($admin)->deleteJson("/api/v1/products/{$product->uuid}")
        ->assertStatus(405); // Method Not Allowed (no DELETE route)
});

test('updating the price stores integer cents', function () {
    $admin = User::factory()->admin()->create();
    app()->instance('currentTenant', $admin->tenant);
    app()->instance('currentTenantId', $admin->tenant->id);
    $product = Product::factory()->forTenant($admin->tenant)->create(['unit_price_cents' => 1000]);

    actingAs($admin)->patchJson("/api/v1/products/{$product->uuid}", ['unit_price_cents' => 2550])
        ->assertOk()->assertJsonPath('data.unit_price_cents', 2550);

    expect($product->fresh()->unit_price_cents)->toBe(2550)->toBeInt();
});

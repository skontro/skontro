<?php

declare(strict_types=1);

use App\Enums\Unit;
use App\Enums\VatRate;
use App\Models\Product;
use App\Models\Tenant;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// actAsTenant() and clearTenantContext() are defined globally in tests/Pest.php.

beforeEach(function () {
    clearTenantContext();
});

test('a product casts its enums and exposes price as Money', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $product = Product::factory()->create([
        'unit_price_cents' => 1999,
        'vat_rate' => VatRate::Standard->value,
        'unit' => Unit::Hour->value,
    ]);

    expect($product->vat_rate)->toBe(VatRate::Standard)
        ->and($product->unit)->toBe(Unit::Hour)
        ->and($product->price)->toBeInstanceOf(Money::class)
        ->and($product->price->getMinorAmount()->toInt())->toBe(1999);
});

test('the unit price column stores integer cents, never a float', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $product = Product::factory()->create(['unit_price_cents' => 1999]);

    // The raw DB value is an integer.
    $raw = DB::table('products')->where('id', $product->id)->value('unit_price_cents');
    expect($raw)->toBe(1999)->toBeInt();

    // The Postgres column type is an integer family, not floating point.
    $type = DB::selectOne(
        "select data_type from information_schema.columns
         where table_name = 'products' and column_name = 'unit_price_cents'"
    );
    expect($type->data_type)->toContain('int'); // bigint
});

test('setting price from a Money writes integer cents', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $product = Product::factory()->make();
    $product->price = Money::ofMinor(2500, 'EUR');
    $product->tenant_id = $tenant->id;
    $product->save();

    expect($product->fresh()->unit_price_cents)->toBe(2500);
});

test('products are tenant-scoped — no cross-tenant leakage', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Product::factory()->forTenant($tenantA)->count(3)->create();
    Product::factory()->forTenant($tenantB)->count(2)->create();

    actAsTenant($tenantA);
    expect(Product::count())->toBe(3);

    actAsTenant($tenantB);
    expect(Product::count())->toBe(2);
});

test('the active scope excludes archived products', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    Product::factory()->forTenant($tenant)->count(2)->create();
    Product::factory()->forTenant($tenant)->archived()->create();

    expect(Product::count())->toBe(3)         // all, including archived
        ->and(Product::active()->count())->toBe(2); // pickerable only
});

test('search matches name and sku case-insensitively', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    Product::factory()->forTenant($tenant)->create(['name' => 'Webentwicklung', 'sku' => 'SKU-0001']);
    Product::factory()->forTenant($tenant)->create(['name' => 'Schulung', 'sku' => 'SKU-0002']);

    expect(Product::search('web')->count())->toBe(1)
        ->and(Product::search('WEB')->count())->toBe(1)
        ->and(Product::search('SKU-0002')->count())->toBe(1)
        ->and(Product::search('missing')->count())->toBe(0);
});

<?php

declare(strict_types=1);

use App\Enums\Country;
use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    clearTenantContext();
});

test('a customer belongs to a tenant and casts its enums', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $customer = Customer::factory()->company()->create(['number' => 'K-2026-00001']);

    expect($customer->type)->toBe(CustomerType::Company)
        ->and($customer->country_code)->toBe(Country::Germany)
        ->and($customer->tenant_id)->toBe($tenant->id);
});

test('customers are tenant-scoped — no cross-tenant leakage at the model layer', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Customer::factory()->forTenant($tenantA)->count(3)->create();
    Customer::factory()->forTenant($tenantB)->count(2)->create();

    actAsTenant($tenantA);

    expect(Customer::count())->toBe(3);

    actAsTenant($tenantB);

    expect(Customer::count())->toBe(2);
});

test('search matches name, number, email, and vat id case-insensitively', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    Customer::factory()->create(['contact_name' => 'Angela Müller', 'number' => 'K-2026-00001']);
    Customer::factory()->create(['contact_name' => 'Boris Schmidt', 'number' => 'K-2026-00002']);

    expect(Customer::search('müller')->count())->toBe(1)
        ->and(Customer::search('MÜLLER')->count())->toBe(1)
        ->and(Customer::search('K-2026-00002')->count())->toBe(1)
        ->and(Customer::search('nonexistent')->count())->toBe(0);
});

test('soft delete hides customers from default queries but retains the row', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $customer = Customer::factory()->create();
    $customer->delete();

    expect(Customer::count())->toBe(0)
        ->and(Customer::withTrashed()->count())->toBe(1);
});

test('the customer number is unique within a tenant but not across tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Customer::factory()->forTenant($tenantA)->create(['number' => 'K-2026-00001']);

    // Same number under a different tenant is allowed.
    Customer::factory()->forTenant($tenantB)->create(['number' => 'K-2026-00001']);

    // A duplicate under the same tenant is rejected by the DB.
    actAsTenant($tenantA);
    expect(fn () => Customer::factory()->forTenant($tenantA)->create(['number' => 'K-2026-00001']))
        ->toThrow(QueryException::class);
});

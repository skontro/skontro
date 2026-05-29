<?php

declare(strict_types=1);

use App\Exceptions\TenantMismatchException;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Models\TenantScopedTestModel;

uses(RefreshDatabase::class);

beforeEach(function () {
    clearTenantContext();
});

test('queries are scoped to the bound tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    // Create B's records without scope (directly), simulating another tenant's data.
    TenantScopedTestModel::factory()->count(3)->create(['tenant_id' => $tenantB->id]);

    actAsTenant($tenantA);
    TenantScopedTestModel::create(['label' => 'a-one']);
    TenantScopedTestModel::create(['label' => 'a-two']);

    // Under A's context, only A's records are visible.
    expect(TenantScopedTestModel::count())->toBe(2)
        ->and(TenantScopedTestModel::pluck('label')->all())->toEqualCanonicalizing(['a-one', 'a-two']);
});

test('no cross-tenant read leakage on all()', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    foreach (range(1, 3) as $i) {
        TenantScopedTestModel::factory()->create(['tenant_id' => $tenantA->id, 'label' => "a{$i}"]);
        TenantScopedTestModel::factory()->create(['tenant_id' => $tenantB->id, 'label' => "b{$i}"]);
    }

    actAsTenant($tenantA);

    $labels = TenantScopedTestModel::all()->pluck('label')->all();

    expect($labels)->toHaveCount(3)
        ->each(fn ($label) => $label->toStartWith('a'));
});

test('tenant_id is auto-stamped on create from context', function () {
    $tenant = Tenant::factory()->create();
    actAsTenant($tenant);

    $model = TenantScopedTestModel::create(['label' => 'stamped']);

    expect($model->tenant_id)->toBe($tenant->id);
    $this->assertDatabaseHas('tenant_scoped_test_models', [
        'label' => 'stamped',
        'tenant_id' => $tenant->id,
    ]);
});

test('scope is a no-op when no tenant is bound', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    TenantScopedTestModel::factory()->create(['tenant_id' => $tenantA->id]);
    TenantScopedTestModel::factory()->create(['tenant_id' => $tenantB->id]);

    // No actAsTenant() call: trusted server-side code sees everything.
    expect(TenantScopedTestModel::count())->toBe(2);
});

test('withoutTenantScope is the only way to see across tenants', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    TenantScopedTestModel::factory()->create(['tenant_id' => $tenantA->id]);
    TenantScopedTestModel::factory()->create(['tenant_id' => $tenantB->id]);

    actAsTenant($tenantA);

    expect(TenantScopedTestModel::count())->toBe(1)
        ->and(TenantScopedTestModel::withoutTenantScope()->count())->toBe(2);
});

test('tenant_id cannot be reassigned after creation', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    actAsTenant($tenantA);
    $model = TenantScopedTestModel::create(['label' => 'immutable']);

    expect(fn () => $model->update(['tenant_id' => $tenantB->id]))
        ->toThrow(TenantMismatchException::class);

    // And the record did not move.
    expect(TenantScopedTestModel::withoutTenantScope()->find($model->id)->tenant_id)
        ->toBe($tenantA->id);
});

test('the global scope class is actually registered on the model', function () {
    $model = new TenantScopedTestModel;

    expect($model->getGlobalScopes())->toHaveKey(TenantScope::class);
});

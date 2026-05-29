<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('me resolves only the caller\'s own tenant', function () {
    $tenantA = Tenant::factory()->create(['name' => 'Tenant A GmbH']);
    $tenantB = Tenant::factory()->create(['name' => 'Tenant B GmbH']);

    $ownerA = User::factory()->owner()->forTenant($tenantA)->create();
    User::factory()->owner()->forTenant($tenantB)->create();

    actingAs($ownerA)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.tenant.id', $tenantA->uuid)
        ->assertJsonPath('data.tenant.name', 'Tenant A GmbH');
});

test('registration always creates a fresh tenant and ignores any tenant_id input', function () {
    $existing = Tenant::factory()->create();

    postJson('/api/v1/register', [
        'company_name' => 'Brand New GmbH',
        'name' => 'New Owner',
        'email' => 'new@brandnew.de',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
        // An attacker-supplied tenant_id must be ignored — the endpoint has
        // no parameter that lets a caller choose a tenant.
        'tenant_id' => $existing->id,
    ])->assertCreated();

    // The new owner belongs to a brand-new tenant, not the injected one.
    $newOwner = User::where('email', 'new@brandnew.de')->firstOrFail();
    expect($newOwner->tenant_id)->not->toBe($existing->id)
        ->and(Tenant::count())->toBe(2);
});

// The 404-not-403 cross-tenant resource contract is now implemented and
// proven in tests/Feature/Customers/CustomerTenantIsolationTest.php.

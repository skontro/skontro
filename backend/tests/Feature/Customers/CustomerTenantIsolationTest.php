<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * The 404-not-403 contract (FR-012, FR-017). The previous milestone left this
 * as a todo() because there was no resource to enforce it on. Here it is, real.
 *
 * A 403 would confirm the resource exists; 404 reveals nothing. Because
 * Customer uses BelongsToTenant, implicit route-model binding runs through the
 * tenant scope, so another tenant's record is simply not found — the 404 is
 * automatic, with no ownership check written in the controller.
 */
test('requesting another tenant\'s customer returns 404, not 403', function () {
    $owner = User::factory()->owner()->create();
    $otherOwner = User::factory()->owner()->create();

    // A customer belonging to the OTHER tenant.
    $foreign = Customer::factory()->forTenant($otherOwner->tenant)->create();

    actingAs($owner)
        ->getJson("/api/v1/customers/{$foreign->uuid}")
        ->assertNotFound(); // 404, not 403 — existence is not leaked
});

test('you cannot update another tenant\'s customer', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $foreign = Customer::factory()->forTenant($otherAdmin->tenant)->create();

    actingAs($admin)
        ->patchJson("/api/v1/customers/{$foreign->uuid}", ['contact_name' => 'Hijacked'])
        ->assertNotFound();

    expect($foreign->fresh()->contact_name)->not->toBe('Hijacked');
});

test('you cannot delete another tenant\'s customer', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $foreign = Customer::factory()->forTenant($otherAdmin->tenant)->create();

    actingAs($admin)
        ->deleteJson("/api/v1/customers/{$foreign->uuid}")
        ->assertNotFound();

    expect(Customer::withoutTenantScope()->find($foreign->id))->not->toBeNull();
});

test('the list never includes another tenant\'s customers', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    Customer::factory()->forTenant($admin->tenant)->count(2)->create();
    Customer::factory()->forTenant($otherAdmin->tenant)->count(5)->create();

    actingAs($admin)->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('unauthenticated requests are rejected', function () {
    \Pest\Laravel\getJson('/api/v1/customers')->assertUnauthorized();
});

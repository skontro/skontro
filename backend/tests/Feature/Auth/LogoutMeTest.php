<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('me returns the authenticated user and their tenant', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.tenant.id', $user->tenant->uuid);
});

test('me is unauthenticated without a session', function () {
    getJson('/api/v1/me')->assertUnauthorized();
});

test('logout ends the session', function () {
    $user = User::factory()->create();

    actingAs($user)->postJson('/api/v1/logout')->assertNoContent();

    // logout() tears down the web (session) guard and invalidates the session.
    // We assert on the web guard directly: actingAs() pins the user on the
    // sanctum guard for the whole test, so checking that guard would only
    // reflect the harness, not the logout we are verifying.
    expect(Auth::guard('web')->check())->toBeFalse();
});

<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('registration creates one tenant and one owner', function () {
    $response = postJson('/api/v1/register', [
        'company_name' => 'Müller Bau GmbH',
        'name' => 'Zeeshan Raja',
        'email' => 'owner@mueller-bau.de',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'owner@mueller-bau.de')
        ->assertJsonPath('data.role', 'owner')
        ->assertJsonPath('data.tenant.name', 'Müller Bau GmbH');

    expect(Tenant::count())->toBe(1)
        ->and(User::count())->toBe(1)
        ->and(User::first()->role)->toBe(Role::Owner);
});

test('registration never returns the password', function () {
    $response = postJson('/api/v1/register', [
        'company_name' => 'Test GmbH',
        'name' => 'Test User',
        'email' => 'test@example.de',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ]);

    $response->assertCreated();
    $data = $response->json('data');

    // No secrets leak, and the exposed id is the public UUID — never the
    // internal auto-increment id.
    expect($data)->not->toHaveKey('password')
        ->and($data)->not->toHaveKey('remember_token')
        ->and($data['id'])->toBe(User::firstOrFail()->uuid)
        ->and($data['id'])->not->toBe(User::firstOrFail()->id);
});

test('registration rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.de']);

    postJson('/api/v1/register', [
        'company_name' => 'Test GmbH',
        'name' => 'Test User',
        'email' => 'taken@example.de',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

test('registration rejects a weak password', function () {
    postJson('/api/v1/register', [
        'company_name' => 'Test GmbH',
        'name' => 'Test User',
        'email' => 'new@example.de',
        'password' => '123',
        'password_confirmation' => '123',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

test('registration requires a company name', function () {
    postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'new@example.de',
        'password' => 'correct-horse-battery-staple',
        'password_confirmation' => 'correct-horse-battery-staple',
    ])->assertStatus(422)->assertJsonValidationErrors('company_name');
});

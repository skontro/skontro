<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.de',
        'password' => Hash::make('correct-horse-battery-staple'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'user@example.de',
        'password' => 'correct-horse-battery-staple',
    ])->assertOk()->assertJsonPath('data.email', 'user@example.de');

    $this->assertAuthenticatedAs($user);
});

test('invalid credentials are rejected', function () {
    User::factory()->create([
        'email' => 'user@example.de',
        'password' => Hash::make('correct-horse-battery-staple'),
    ]);

    postJson('/api/v1/login', [
        'email' => 'user@example.de',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    $this->assertGuest();
});

test('the failure message does not reveal whether the email exists', function () {
    User::factory()->create(['email' => 'real@example.de', 'password' => Hash::make('secret-secret-secret')]);

    $existing = postJson('/api/v1/login', [
        'email' => 'real@example.de',
        'password' => 'wrong-password',
    ]);

    $missing = postJson('/api/v1/login', [
        'email' => 'nobody@example.de',
        'password' => 'wrong-password',
    ]);

    expect($existing->json('errors.email'))->toBe($missing->json('errors.email'));
});

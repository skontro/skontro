<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('role is stored as a string and read back as a Role enum', function () {
    $user = User::factory()->admin()->create();

    expect($user->fresh()->role)->toBe(Role::Admin);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'admin',
    ]);
});

test('hasRole uses ordinal comparison', function () {
    $owner = User::factory()->owner()->create();
    $member = User::factory()->member()->create();

    expect($owner->hasRole(Role::Member))->toBeTrue()
        ->and($member->hasRole(Role::Owner))->toBeFalse();
});

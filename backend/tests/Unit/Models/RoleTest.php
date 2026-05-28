<?php

declare(strict_types=1);

use App\Enums\Role;

test('role ranks are ordered owner > admin > member', function () {
    expect(Role::Owner->rank())->toBeGreaterThan(Role::Admin->rank())
        ->and(Role::Admin->rank())->toBeGreaterThan(Role::Member->rank());
});

test('atLeast includes lower roles but not higher ones', function () {
    expect(Role::Owner->atLeast(Role::Member))->toBeTrue()
        ->and(Role::Owner->atLeast(Role::Admin))->toBeTrue()
        ->and(Role::Owner->atLeast(Role::Owner))->toBeTrue()
        ->and(Role::Member->atLeast(Role::Owner))->toBeFalse()
        ->and(Role::Member->atLeast(Role::Admin))->toBeFalse()
        ->and(Role::Admin->atLeast(Role::Owner))->toBeFalse();
});

test('every role has a non-empty label', function () {
    foreach (Role::cases() as $role) {
        expect($role->label())->not->toBe('');
    }
});

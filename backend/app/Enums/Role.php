<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Human-readable label for display in the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Administrator',
            self::Member => 'Member',
        };
    }

    /**
     * Ordinal rank for permission comparisons. Higher rank includes the
     * capabilities of every lower rank, so a gate checks
     * `$user->role->rank() >= $required->rank()`.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Member => 1,
        };
    }

    /**
     * Whether this role meets or exceeds the required role.
     */
    public function atLeast(Role $required): bool
    {
        return $this->rank() >= $required->rank();
    }
}
